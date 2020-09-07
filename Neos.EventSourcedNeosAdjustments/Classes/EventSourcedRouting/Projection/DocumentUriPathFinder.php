<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Exception\InvalidShortcutException;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentSubgraphUriProcessor;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\ValueObject\DocumentNodeInfo;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Psr\Http\Message\UriInterface;

/**
 * @Flow\Scope("singleton")
 */
final class DocumentUriPathFinder
{
    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @var ?ContentStreamIdentifier
     */
    private $liveContentStreamIdentifierRuntimeCache;

    /**
     * @var NodeName[] (indexed by the corresponding host)
     */
    private $siteNodeNameRuntimeCache = [];

    /**
     * @Flow\Inject
     * @var ContentSubgraphUriProcessor
     */
    protected $contentSubgraphUriProcessor;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    public function injectEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->dbal = $entityManager->getConnection();
    }

    public function matchUriPath(string $uriPath, DimensionSpacePoint $dimensionSpacePoint): MatchResult
    {
        $nodeInfo = $this->getNodeInfoForSiteNodeNameAndUriPath($this->getCurrentSiteNodeName(), $uriPath, $dimensionSpacePoint);
        $nodeAddress = new NodeAddress(
            $this->getLiveContentStreamIdentifier(),
            $dimensionSpacePoint,
            $nodeInfo->getNodeAggregateIdentifier(),
            WorkspaceName::forLive()
        );
        return new MatchResult($nodeAddress->serializeForUri(), $nodeInfo->getRouteTags());
    }

    public function resolveNodeAddress(NodeAddress $nodeAddress, string $uriSuffix): ResolveResult
    {
        $nodeInfo = $this->getNodeInfoForNodeAddress($nodeAddress);
        $shortcutRecursionLevel = 0;
        while ($nodeInfo->isShortcut()) {
            if (++ $shortcutRecursionLevel > 50) {
                throw new InvalidShortcutException(sprintf('Shortcut recursion level reached after %d levels', $shortcutRecursionLevel), 1599035282);
            }
            switch ($nodeInfo->getShortcutMode()) {
                case 'parentNode':
                    $nodeInfo = $this->getParentNodeInfo($nodeInfo);
                    continue 2;
                case 'firstChildNode':
                    try {
                        $nodeInfo = $this->getFirstChildNodeInfo($nodeInfo);
                    } catch (\Exception $e) {
                        throw new InvalidShortcutException(sprintf('Failed to fetch firstChildNode in Node "%s": %s', $nodeAddress, $e->getMessage()), 1599043861, $e);
                    }
                    continue 2;
                case 'selectedTarget':
                    try {
                        $targetUri = $nodeInfo->getShortcutTarget();
                    } catch (\Exception $e) {
                        throw new InvalidShortcutException(sprintf('Invalid shortcut target in Node "%s": %s', $nodeAddress, $e->getMessage()), 1599043489, $e);
                    }
                    if ($targetUri->getScheme() === 'node') {
                        $targetNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($targetUri->getHost());
                        try {
                            $nodeInfo = $this->getNodeInfoForNodeAddress($nodeAddress->withNodeAggregateIdentifier($targetNodeAggregateIdentifier));
                        } catch (\Exception $e) {
                            throw new InvalidShortcutException(sprintf('Failed to load selectedTarget node in Node "%s": %s', $nodeAddress, $e->getMessage()), 1599043803, $e);
                        }
                        continue 2;
                    }
                    if ($targetUri->getScheme() === 'asset') {
                        $asset = $this->assetRepository->findByIdentifier($targetUri->getHost());
                        if ($asset === null) {
                            throw new InvalidShortcutException(sprintf('Failed to load selectedTarget asset in Node "%s", probably it was deleted', $nodeAddress), 1599314109);
                        }
                        $assetUri = $this->resourceManager->getPublicPersistentResourceUri($asset->getResource());
                        if (!$assetUri) {
                            throw new InvalidShortcutException(sprintf('Failed to resolve asset URI in Node "%s", probably it was deleted', $nodeAddress), 1599314203);
                        }
                        return new ResolveResult($assetUri);
                    }
                    // TODO support shortcuts to assets (asset://<id>)
                    // shortcut to (external) URI
                    $uriConstraints = $this->uriConstraintsFromUri($targetUri);
                    return new ResolveResult('', $uriConstraints);
                default:
                    throw new InvalidShortcutException(sprintf('Unsupported shortcut mode "%s" in Node "%s"', $nodeInfo->getShortcutMode(), $nodeAddress), 1598194032);
            }
        }
        if (!$nodeAddress->getNodeAggregateIdentifier()->equals($nodeInfo->getNodeAggregateIdentifier())) {
            $nodeAddress = $nodeAddress->withNodeAggregateIdentifier($nodeInfo->getNodeAggregateIdentifier());
        }

        $uriConstraints = $this->contentSubgraphUriProcessor->resolveDimensionUriConstraints($nodeAddress);

        if ((string)$nodeInfo->getSiteNodeName() !== (string)$this->getCurrentSiteNodeName()) {
            /** @var Site $site */
            foreach ($this->siteRepository->findOnline() as $site) {
                if ($site->getNodeName() === (string)$nodeInfo->getSiteNodeName()) {
                    $uriConstraints = $this->applyDomainToUriConstraints($uriConstraints, $site->getPrimaryDomain());
                    break;
                }
            }
        }

        if (!empty($uriSuffix) && $nodeInfo->hasUriPath()) {
            $uriConstraints = $uriConstraints->withPathSuffix($uriSuffix);
        }
        return new ResolveResult($nodeInfo->getUriPath(), $uriConstraints, $nodeInfo->getRouteTags());
    }

    private function getCurrentSiteNodeName(): NodeName
    {
        // TODO pass in host (only possible for RoutePart:matchWithParameters() right now)
        $host = $this->getCurrentHost();
        if (!isset($this->siteNodeNameRuntimeCache[$host])) {
            $site = null;
            if (!empty($host)) {
                $activeDomain = $this->domainRepository->findOneByHost($host, true);
                if ($activeDomain !== null) {
                    $site = $activeDomain->getSite();
                }
            }
            if ($site === null) {
                $site = $this->siteRepository->findFirstOnline();
            }
            $this->siteNodeNameRuntimeCache[$host] = NodeName::fromString($site->getNodeName());
        }
        return $this->siteNodeNameRuntimeCache[$host];
    }

    private function getCurrentHost(): string
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if ($requestHandler instanceof HttpRequestHandlerInterface) {
            return $requestHandler->getComponentContext()->getHttpRequest()->getUri()->getHost();
        }
        return '';
    }

    private function getLiveContentStreamIdentifier(): ContentStreamIdentifier
    {
        if ($this->liveContentStreamIdentifierRuntimeCache === null) {
            $this->liveContentStreamIdentifierRuntimeCache = ContentStreamIdentifier::fromString($this->dbal->fetchColumn('SELECT contentStreamIdentifier FROM ' . DocumentUriPathProjector::TABLE_NAME_LIVE_CONTENT_STREAMS));
        }
        return $this->liveContentStreamIdentifierRuntimeCache;
    }

    private function getNodeInfoForNodeAddress(NodeAddress  $nodeAddress): DocumentNodeInfo
    {
        $row = $this->dbal->fetchAssoc('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacepointHash = :dimensionSpacepointHash AND nodeAggregateIdentifier = :nodeAggregateIdentifier AND disabled = 0', [
            'dimensionSpacepointHash' => $nodeAddress->getDimensionSpacePoint()->getHash(),
            'nodeAggregateIdentifier' => $nodeAddress->getNodeAggregateIdentifier(),
        ]);
        return $this->databaseRowToDocumentNodeInfo($row);
    }

    private function getParentNodeInfo(DocumentNodeInfo $nodeInfo): DocumentNodeInfo
    {
        $row = $this->dbal->fetchAssoc('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacepointHash = :dimensionSpacepointHash AND nodeAggregateIdentifier = :nodeAggregateIdentifier AND disabled = 0', [
            'dimensionSpacepointHash' => $nodeInfo->getDimensionSpacePointHash(),
            'nodeAggregateIdentifier' => $nodeInfo->getParentNodeAggregateIdentifier(),
        ]);
        return $this->databaseRowToDocumentNodeInfo($row);
    }

    private function getFirstChildNodeInfo(DocumentNodeInfo $nodeInfo): DocumentNodeInfo
    {
        $row = $this->dbal->fetchAssoc('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacepointHash = :dimensionSpacepointHash AND parentNodeAggregateIdentifier = :parentNodeAggregateIdentifier AND precedingNodeAggregateIdentifier IS NULL AND disabled = 0', [
            'dimensionSpacepointHash' => $nodeInfo->getDimensionSpacePointHash(),
            'parentNodeAggregateIdentifier' => $nodeInfo->getNodeAggregateIdentifier(),
        ]);
        return $this->databaseRowToDocumentNodeInfo($row);
    }

    private function getNodeInfoForSiteNodeNameAndUriPath(NodeName $siteNodeName, string $uriPath, DimensionSpacePoint $dimensionSpacePoint): DocumentNodeInfo
    {
        $row = $this->dbal->fetchAssoc('SELECT * FROM ' . DocumentUriPathProjector::TABLE_NAME_DOCUMENT_URIS . ' WHERE dimensionSpacepointHash = :dimensionSpacepointHash AND siteNodeName = :siteNodeName AND uriPath = :uriPath AND disabled = 0', [
            'dimensionSpacepointHash' => $dimensionSpacePoint->getHash(),
            'siteNodeName' => $siteNodeName,
            'uriPath' => $uriPath,
        ]);
        return $this->databaseRowToDocumentNodeInfo($row);
    }

    /**
     * @param array|false|null $row
     * @return DocumentNodeInfo
     */
    private function databaseRowToDocumentNodeInfo($row): DocumentNodeInfo
    {
        if (!is_array($row)) {
            // TODO
            throw new \InvalidArgumentException('TODO');
        }
        return DocumentNodeInfo::fromDatabaseRow($row);
    }

    private function uriConstraintsFromUri(UriInterface $uri): UriConstraints
    {
        $uriConstraints = UriConstraints::create()
            ->withScheme($uri->getScheme())
            ->withHost($uri->getHost());
        if (!empty($uri->getPath())) {
            $uriConstraints = $uriConstraints->withPath($uri->getPath());
        }
        if ($uri->getPort() !== null) {
            $uriConstraints = $uriConstraints->withPort($uri->getPort());
        } else {
            $uriConstraints = $uriConstraints->withPort($uri->getScheme() === 'https' ? 443 : 80);
        }
        if (!empty($uri->getQuery())) {
            $uriConstraints = $uriConstraints->withQueryString($uri->getQuery());
        }
        return $uriConstraints;
    }

    private function applyDomainToUriConstraints(UriConstraints $uriConstraints, ?Domain $domain): UriConstraints
    {
        if ($domain === null) {
            return $uriConstraints;
        }
        $uriConstraints = $uriConstraints->withHost($domain->getHostname());
        if (!empty($domain->getScheme())) {
            $uriConstraints = $uriConstraints->withScheme($domain->getScheme());
        }
        if (!empty($domain->getPort())) {
            $uriConstraints = $uriConstraints->withPort($domain->getPort());
        }
        return $uriConstraints;
    }
}

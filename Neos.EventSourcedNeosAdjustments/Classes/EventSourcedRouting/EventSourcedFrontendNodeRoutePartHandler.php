<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Exception\InvalidShortcutException;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentSubgraphUriProcessor;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Projection\DocumentUriPathFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Mvc\Routing\AbstractRoutePart;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\DynamicRoutePartInterface;
use Neos\Flow\Mvc\Routing\ParameterAwareRoutePartInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface;
use Psr\Http\Message\UriInterface;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 *
 * @Flow\Scope("singleton")
 */
final class EventSourcedFrontendNodeRoutePartHandler extends AbstractRoutePart implements DynamicRoutePartInterface, ParameterAwareRoutePartInterface, FrontendNodeRoutePartHandlerInterface
{
    private string $splitString = '';

    /**
     * @var NodeName[] (indexed by the corresponding host)
     */
    private array $siteNodeNameRuntimeCache = [];

    /**
     * @Flow\Inject
     * @var DocumentUriPathFinder
     */
    protected $documentUriPathFinder;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

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
     * @var ContentSubgraphUriProcessor
     */
    protected $contentSubgraphUriProcessor;

    /**
     * @param mixed $requestPath
     * @param RouteParameters $parameters
     * @return bool|MatchResult
     */
    public function matchWithParameters(&$requestPath, RouteParameters $parameters)
    {
        if (!is_string($requestPath)) {
            return false;
        }
        // TODO verify parameters / use "host" parameter
        if (!$parameters->has('dimensionSpacePoint')) {
            return false;
        }

        $uriPathSegmentOffset = $parameters->getValue('uriPathSegmentOffset') ?? 0;
        $remainingRequestPath = $this->truncateRequestPathAndReturnRemainder($requestPath, $uriPathSegmentOffset);
        /** @var DimensionSpacePoint $dimensionSpacePoint */
        $dimensionSpacePoint = $parameters->getValue('dimensionSpacePoint');

        try {
            $matchResult = $this->matchUriPath($requestPath, $dimensionSpacePoint);
        } catch (\Exception $exception) {
            // TODO log exception
            return false;
        }
        $requestPath = $remainingRequestPath;
        return $matchResult;
    }

    /**
     * @param array $routeValues
     * @return ResolveResult|bool
     */
    public function resolve(array &$routeValues)
    {
        if ($this->name === null || $this->name === '' || !\array_key_exists($this->name, $routeValues)) {
            return false;
        }

        $nodeAddress = $routeValues[$this->name];
        if (!$nodeAddress instanceof NodeAddress) {
            return false;
        }

        try {
            $resolveResult = $this->resolveNodeAddress($nodeAddress);
        } catch (\Exception $exception) {
            // TODO log exception
            return false;
        }

        unset($routeValues[$this->name]);
        return $resolveResult;
    }

    /**
     * @param NodeAddress $nodeAddress
     * @return ResolveResult
     * @throws Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     * @throws InvalidShortcutException
     */
    private function resolveNodeAddress(NodeAddress $nodeAddress): ResolveResult
    {
        $nodeInfo = $this->documentUriPathFinder->getNodeInfoForNodeAddress($nodeAddress);
        $shortcutRecursionLevel = 0;
        while ($nodeInfo->isShortcut()) {
            if (++ $shortcutRecursionLevel > 50) {
                throw new InvalidShortcutException(sprintf('Shortcut recursion level reached after %d levels', $shortcutRecursionLevel), 1599035282);
            }
            switch ($nodeInfo->getShortcutMode()) {
                case 'parentNode':
                    $nodeInfo = $this->documentUriPathFinder->getParentNodeInfo($nodeInfo);
                    continue 2;
                case 'firstChildNode':
                    try {
                        $nodeInfo = $this->documentUriPathFinder->getFirstChildNodeInfo($nodeInfo);
                    } catch (\Exception $e) {
                        throw new InvalidShortcutException(sprintf('Failed to fetch firstChildNode in Node "%s": %s', $nodeAddress, $e->getMessage()), 1599043861, $e);
                    }
                    continue 2;
                case 'selectedTarget':
                    try {
                        $targetUri = $nodeInfo->getShortcutTargetUri();
                    } catch (\Exception $e) {
                        throw new InvalidShortcutException(sprintf('Invalid shortcut target in Node "%s": %s', $nodeAddress, $e->getMessage()), 1599043489, $e);
                    }
                    if ($targetUri->getScheme() === 'node') {
                        $targetNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($targetUri->getHost());
                        try {
                            $nodeInfo = $this->documentUriPathFinder->getNodeInfoForNodeAddress($nodeAddress->withNodeAggregateIdentifier($targetNodeAggregateIdentifier));
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

        if (!empty($this->options['uriSuffix']) && $nodeInfo->hasUriPath()) {
            $uriConstraints = $uriConstraints->withPathSuffix($this->options['uriSuffix']);
        }
        return new ResolveResult($nodeInfo->getUriPath(), $uriConstraints, $nodeInfo->getRouteTags());
    }


    private function matchUriPath(string $uriPath, DimensionSpacePoint $dimensionSpacePoint): MatchResult
    {
        $nodeInfo = $this->documentUriPathFinder->getNodeInfoForSiteNodeNameAndUriPath($this->getCurrentSiteNodeName(), $uriPath, $dimensionSpacePoint);
        $nodeAddress = new NodeAddress(
            $this->documentUriPathFinder->getLiveContentStreamIdentifier(),
            $dimensionSpacePoint,
            $nodeInfo->getNodeAggregateIdentifier(),
            WorkspaceName::forLive()
        );
        return new MatchResult($nodeAddress->serializeForUri(), $nodeInfo->getRouteTags());
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

    private function truncateRequestPathAndReturnRemainder(string &$requestPath, int $uriPathSegmentOffset): string
    {
        $uriPathSegments = array_slice(explode('/', $requestPath), $uriPathSegmentOffset);
        $requestPath = implode('/', $uriPathSegments);
        if (!empty($this->options['uriSuffix'])) {
            $suffixPosition = strpos($requestPath, $this->options['uriSuffix']);
            if ($suffixPosition === false) {
                return '';
            }
            $requestPath = substr($requestPath, 0, $suffixPosition);
        }
        if ($this->splitString === '' || $this->splitString === '/') {
            return '';
        }
        $splitStringPosition = strpos($requestPath, $this->splitString);
        if ($splitStringPosition === false) {
            return '';
        }
        $fullRequestPath = $requestPath;
        $requestPath = substr($requestPath, 0, $splitStringPosition);

        return substr($fullRequestPath, $splitStringPosition);
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

    public function setSplitString($splitString): void
    {
        $this->splitString = $splitString;
    }

    public function match(&$routePath)
    {
        throw new \BadMethodCallException('match() is not supported by this Route Part Handler, use "matchWithParameters" instead', 1568287772);
    }
}

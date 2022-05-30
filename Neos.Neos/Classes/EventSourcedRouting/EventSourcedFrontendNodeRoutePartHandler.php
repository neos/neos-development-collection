<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\EventSourcedRouting;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeAddressCannotBeSerializedException;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\Domain\Model\DimensionSpacePointCacheEntryIdentifier;
use Neos\Neos\Domain\Service\NodeShortcutResolver;
use Neos\Neos\EventSourcedRouting\Exception\InvalidShortcutException;
use Neos\Neos\EventSourcedRouting\Http\ContentSubgraphUriProcessor;
use Neos\Neos\EventSourcedRouting\Projection\DocumentUriPathFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Mvc\Routing\AbstractRoutePart;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\DynamicRoutePartInterface;
use Neos\Flow\Mvc\Routing\ParameterAwareRoutePartInterface;
use Neos\Neos\Controller\Exception\NodeNotFoundException;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface;
use Psr\Http\Message\UriInterface;

/** @codingStandardsIgnoreStart */
use Neos\Neos\EventSourcedRouting\Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException;
/** @codingStandardsIgnoreEnd */

/**
 * A route part handler for finding nodes in the website's frontend.
 * Uses a special projection {@see DocumentUriPathFinder}, and does NOT use the graph projection in any way.
 *
 * @Flow\Scope("singleton")
 */
final class EventSourcedFrontendNodeRoutePartHandler extends AbstractRoutePart implements
    DynamicRoutePartInterface,
    ParameterAwareRoutePartInterface,
    FrontendNodeRoutePartHandlerInterface
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
     * @var ContentSubgraphUriProcessor
     */
    protected $contentSubgraphUriProcessor;

    /**
     * @Flow\Inject
     * @var NodeShortcutResolver
     */
    protected $nodeShortcutResolver;

    /**
     * @param mixed $requestPath
     * @param RouteParameters $parameters
     * @return bool|MatchResult
     * @throws NodeAddressCannotBeSerializedException
     */
    public function matchWithParameters(&$requestPath, RouteParameters $parameters)
    {
        if (!is_string($requestPath)) {
            return false;
        }
        if (!$parameters->has('dimensionSpacePointCacheEntryIdentifier') || !$parameters->has('requestUriHost')) {
            return false;
        }
        /** @var string $requestUriHost */
        $requestUriHost = $parameters->getValue('requestUriHost');

        /** @var int $uriPathSegmentOffset */
        $uriPathSegmentOffset = $parameters->getValue('uriPathSegmentOffset') ?? 0;
        $remainingRequestPath = $this->truncateRequestPathAndReturnRemainder($requestPath, $uriPathSegmentOffset);
        $dimensionSpacePointCacheEntryIdentifier = $parameters->getValue('dimensionSpacePointCacheEntryIdentifier');
        assert($dimensionSpacePointCacheEntryIdentifier instanceof DimensionSpacePointCacheEntryIdentifier);
        $dimensionSpacePoint = $dimensionSpacePointCacheEntryIdentifier->dimensionSpacePoint;

        try {
            $matchResult = $this->matchUriPath(
                $requestPath,
                $dimensionSpacePoint,
                $requestUriHost
            );
        } catch (NodeNotFoundException $exception) {
            // we silently swallow the Node Not Found case, as you'll see this in the server log if it interests you
            // (and other routes could still handle this).
            return false;
        }
        $requestPath = $remainingRequestPath;
        return $matchResult;
    }

    /**
     * @param array<string,mixed> &$routeValues
     * @throws InvalidContentDimensionValueUriProcessorException
     */
    public function resolveWithParameters(array &$routeValues, RouteParameters $parameters): ResolveResult|false
    {
        if ($this->name === null || $this->name === '' || !\array_key_exists($this->name, $routeValues)) {
            return false;
        }
        if (!$parameters->has('requestUriHost')) {
            return false;
        }
        /** @var string $requestUriHost */
        $requestUriHost = $parameters->getValue('requestUriHost');

        $nodeAddress = $routeValues[$this->name];
        if (!$nodeAddress instanceof NodeAddress) {
            return false;
        }

        try {
            $resolveResult = $this->resolveNodeAddress($nodeAddress, $requestUriHost);
        } catch (NodeNotFoundException | InvalidShortcutException $exception) {
            // TODO log exception
            return false;
        }

        unset($routeValues[$this->name]);
        return $resolveResult;
    }

    /**
     * @param NodeAddress $nodeAddress
     * @param string $host
     * @return ResolveResult
     * @throws Http\ContentDimensionLinking\Exception\InvalidContentDimensionValueUriProcessorException
     * @throws NodeNotFoundException | InvalidShortcutException
     */
    private function resolveNodeAddress(NodeAddress $nodeAddress, string $host): ResolveResult
    {
        $nodeInfo = $this->documentUriPathFinder->getByIdAndDimensionSpacePointHash(
            $nodeAddress->nodeAggregateIdentifier,
            $nodeAddress->dimensionSpacePoint->hash
        );
        if ($nodeInfo->isDisabled()) {
            throw new NodeNotFoundException(sprintf(
                'The resolved node for address %s is disabled',
                $nodeAddress
            ), 1599668357);
        }
        if ($nodeInfo->isShortcut()) {
            $nodeInfo = $this->nodeShortcutResolver->resolveNode($nodeInfo);
            if ($nodeInfo instanceof UriInterface) {
                return $this->buildResolveResultFromUri($nodeInfo);
            }
            $nodeAddress = $nodeAddress->withNodeAggregateIdentifier($nodeInfo->getNodeAggregateIdentifier());
        }
        $uriConstraints = $this->contentSubgraphUriProcessor->resolveDimensionUriConstraints($nodeAddress);

        if ((string)$nodeInfo->getSiteNodeName() !== (string)$this->getCurrentSiteNodeName($host)) {
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


    /**
     * @param string $uriPath
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param string $host
     * @return MatchResult
     * @throws NodeNotFoundException | NodeAddressCannotBeSerializedException
     */
    private function matchUriPath(string $uriPath, DimensionSpacePoint $dimensionSpacePoint, string $host): MatchResult
    {
        $nodeInfo = $this->documentUriPathFinder->getEnabledBySiteNodeNameUriPathAndDimensionSpacePointHash(
            $this->getCurrentSiteNodeName($host),
            $uriPath,
            $dimensionSpacePoint->hash
        );
        $nodeAddress = new NodeAddress(
            $this->documentUriPathFinder->getLiveContentStreamIdentifier(),
            $dimensionSpacePoint,
            $nodeInfo->getNodeAggregateIdentifier(),
            WorkspaceName::forLive()
        );
        return new MatchResult($nodeAddress->serializeForUri(), $nodeInfo->getRouteTags());
    }

    private function getCurrentSiteNodeName(string $host): NodeName
    {
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
                if ($site === null) {
                    throw new \RuntimeException('TODO: No site found. Please create one.');
                }
            }
            $this->siteNodeNameRuntimeCache[$host] = NodeName::fromString($site->getNodeName());
        }
        return $this->siteNodeNameRuntimeCache[$host];
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


    private function buildResolveResultFromUri(UriInterface $uri): ResolveResult
    {
        $uriConstraints = UriConstraints::create();
        if (!empty($uri->getScheme())) {
            $uriConstraints = $uriConstraints->withScheme($uri->getScheme());
        }
        if (!empty($uri->getHost())) {
            $uriConstraints = $uriConstraints->withHost($uri->getHost());
        }
        if ($uri->getPort() !== null) {
            $uriConstraints = $uriConstraints->withPort($uri->getPort());
        } elseif (!empty($uri->getScheme())) {
            $uriConstraints = $uriConstraints->withPort($uri->getScheme() === 'https' ? 443 : 80);
        }
        if (!empty($uri->getQuery())) {
            $uriConstraints = $uriConstraints->withQueryString($uri->getQuery());
        }
        if (!empty($uri->getFragment())) {
            $uriConstraints = $uriConstraints->withFragment($uri->getFragment());
        }
        return new ResolveResult($uri->getPath(), $uriConstraints);
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

    public function match(&$routePath): bool
    {
        return false;
        /*
        throw new \BadMethodCallException(
            'match() is not supported by this Route Part Handler, use "matchWithParameters" instead',
            1568287772
        );*/
    }

    /**
     * @param array<int|string,mixed> $routeValues
     */
    public function resolve(array &$routeValues): bool
    {
        return false;
        /*
        throw new \BadMethodCallException(
            'resolve() is not supported by this Route Part Handler, use "resolveWithParameters" instead',
            1611600169
        );*/
    }
}

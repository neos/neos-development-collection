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

namespace Neos\Neos\FrontendRouting;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\AbstractRoutePart;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\DynamicRoutePartInterface;
use Neos\Flow\Mvc\Routing\ParameterAwareRoutePartInterface;
use Neos\Flow\Mvc\Routing\RoutingMiddleware;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\CrossSiteLinking\CrossSiteLinkerInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\DelegatingResolver;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\RequestToDimensionSpacePointContext;
use Neos\Neos\FrontendRouting\Exception\InvalidShortcutException;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;
use Neos\Neos\FrontendRouting\Exception\ResolvedSiteNotFoundException;
use Neos\Neos\FrontendRouting\Exception\TargetSiteNotFoundException;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathFinder;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionFailedException;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionMiddleware;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Psr\Http\Message\UriInterface;

/**
 * A route part handler for finding nodes in the website's frontend. Like every RoutePartHandler,
 * this handles both directions:
 * - from URL to NodeAddress (via {@see EventSourcedFrontendNodeRoutePartHandler::matchWithParameters})
 * - from NodeAddress to URL (via {@see EventSourcedFrontendNodeRoutePartHandler::resolveWithParameters})
 *
 * For performance reasons, this uses a special projection {@see DocumentUriPathFinder}, and
 * does NOT use the graph projection in any way.
 *
 *
 * ## Match Direction (URL to NodeAddress)
 *
 * This is usually simply triggered ONCE per request, before the controller starts working.
 * The RoutePartHandler is invoked from {@see RoutingMiddleware} (which handles the routing).
 *
 * The overall process is as follows:
 *
 * ```
 *  (*) = Extension Point               ┌───────────────────────────────────────────────┐
 * ┌──────────────┐                     │   EventSourcedFrontendNodeRoutePartHandler    │
 * │SiteDetection │                     │ ┌─────────────────────┐                       │
 * │Middleware (*)│────────────────────▶│ │DimensionResolver (*)│─────▶ Finding the    ─┼─▶NodeAddress
 * └──────────────┘ current site        │ └─────────────────────┘       NodeId          │
 *                                      └───────────────────────────────────────────────┘
 *                  current Content                              current
 *                  Repository                                   DimensionSpacePoint
 * ```
 *
 *
 * ### {@see SiteDetectionMiddleware}: Multi-Site Support
 * and Multiple Content Repository Support
 *
 * The Dimension Resolving configuration might be site-specific, f.e. one site maps a subdomain to a different language;
 * and another site which wants to use the UriPathSegment.
 * Additionally, we want to support using different content repositories for different sites
 * (e.g. to have different NodeTypes configured, or differing dimension configuration).
 *
 * Thus, the {@see DimensionResolverInterface} and the frontend routing in general needs the result of the site
 * detection as input.
 *
 * Because of this, the site detection is done before the routing; inside the {@see SiteDetectionMiddleware},
 * which runs the routing.
 * **Feel free to replace the Site Detection with your own custom Middleware (it's very little code).**
 *
 * The Site Detection is done at **every** request.
 *
 *
 * ### {@see DimensionResolverInterface}: Custom Dimension Resolving
 *
 * Especially the {@see DimensionSpacePoint} matching must be very extensible, because
 * people might want to map domains, subdomains, URL slugs, ... to different dimensions; and
 * maybe even handle every dimension individually.
 *
 * This is why the {@see EventSourcedFrontendNodeRoutePartHandler} calls the {@see DelegatingResolver},
 * which calls potentially multiple {@see DimensionResolverInterface}s.
 *
 * **For details on how to customize the Dimension Resolving, please see {@see DimensionResolverInterface}.**
 *
 * Because the Dimension Resolving runs inside the RoutePartHandler, this is all cached (via the Routing Cache).
 *
 *
 * ### Reading the Uri Path Segment and finding the node
 *
 * This is the core capability of this class (the {@see EventSourcedFrontendNodeRoutePartHandler}).
 *
 *
 * ### Result of the Routing
 *
 * The **result** of the {@see EventSourcedFrontendNodeRoutePartHandler::matchWithParameters} call is a
 * {@see NodeAddress} (wrapped in a {@see MatchResult}); so to build the NodeAddress, we need:
 * - the {@see WorkspaceName} (which is always **live** in our case)
 * - the {@see ContentStreamId} of the Live workspace
 * - The {@see DimensionSpacePoint} we want to see the page in (i.e. in language=de)
 *   - resolved by {@see DimensionResolverInterface}
 * - The {@see NodeAggregateId} (of the Document Node we want to show)
 *   - resolved by {@see EventSourcedFrontendNodeRoutePartHandler}
 *
 *
 * ## Resolve Direction (NodeAddress to URL)
 *
 * ```
 *                ┌────────────────────────────────────────────────────────────────────────┐
 *                │                EventSourcedFrontendNodeRoutePartHandler                │
 *                │                   ┌─────────────────────┐      ┌─────────────────────┐ │
 * NodeAddress────┼▶ Finding the ────▶│ CrossSiteLinker (*) │─────▶│DimensionResolver (*)│─┼──▶ URL
 *                │  URL              └─────────────────────┘      └─────────────────────┘ │
 *                └────────────────────────────────────────────────────────────────────────┘
 * ```
 *
 * First, the URL path is resolved by checking the {@see DocumentUriPathFinder} projection.
 *
 * Then, the {@see CrossSiteLinkerInterface} is responsible for adjusting the built URL in case it needs to
 * be generated for a different Site. It is basically a global singleton, but can be replaced globally
 * if needed.
 *
 * Then, the {@see DimensionResolverInterface} of the target site is called for adjusting the URL.
 *
 * @Flow\Scope("singleton")
 */
final class EventSourcedFrontendNodeRoutePartHandler extends AbstractRoutePart implements
    DynamicRoutePartInterface,
    ParameterAwareRoutePartInterface,
    FrontendNodeRoutePartHandlerInterface
{
    private string $splitString = '';

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected NodeShortcutResolver $nodeShortcutResolver;

    #[Flow\Inject]
    protected DelegatingResolver $delegatingResolver;

    #[Flow\Inject]
    protected CrossSiteLinkerInterface $crossSiteLinker;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    /**
     * Incoming URLs
     *
     * @param mixed $requestPath
     * @param RouteParameters $parameters
     * @return bool|MatchResult
     */
    public function matchWithParameters(&$requestPath, RouteParameters $parameters)
    {
        if (!is_string($requestPath)) {
            return false;
        }

        try {
            $siteDetectionResult = SiteDetectionResult::fromRouteParameters($parameters);
        } catch (SiteDetectionFailedException) {
            return false;
        }
        $resolvedSite = $this->siteRepository->findOneByNodeName($siteDetectionResult->siteNodeName);

        if ($resolvedSite === null) {
            throw new ResolvedSiteNotFoundException(sprintf('No site found for siteNodeName "%s"', $siteDetectionResult->siteNodeName->value));
        }

        $remainingRequestPath = $this->truncateRequestPathAndReturnRemainder($requestPath, $resolvedSite->getConfiguration()->uriPathSuffix);

        $dimensionResolvingResult = $this->delegatingResolver->fromRequestToDimensionSpacePoint(
            RequestToDimensionSpacePointContext::fromUriPathAndRouteParametersAndResolvedSite(
                $requestPath,
                $parameters,
                $resolvedSite,
            )
        );
        $dimensionSpacePoint = $dimensionResolvingResult->resolvedDimensionSpacePoint;
        // TODO Validate for full context
        // TODO validate dsp == complete (ContentDimensionZookeeper::getAllowedDimensionSubspace()->contains()...)
        // if incomplete -> no match + log

        $contentRepository = $this->contentRepositoryRegistry->get($resolvedSite->getConfiguration()->contentRepositoryId);

        try {
            $matchResult = $this->matchUriPath(
                $dimensionResolvingResult->remainingUriPath,
                $dimensionSpacePoint,
                $siteDetectionResult,
                $contentRepository
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
     * @param string $uriPath
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return MatchResult
     * @throws NodeNotFoundException
     */
    private function matchUriPath(
        string $uriPath,
        DimensionSpacePoint $dimensionSpacePoint,
        SiteDetectionResult $siteDetectionResult,
        ContentRepository $contentRepository
    ): MatchResult {
        $uriPath = trim($uriPath, '/');
        $documentUriPathFinder = $contentRepository->projectionState(DocumentUriPathFinder::class);
        $nodeInfo = $documentUriPathFinder->getEnabledBySiteNodeNameUriPathAndDimensionSpacePointHash(
            $siteDetectionResult->siteNodeName,
            $uriPath,
            $dimensionSpacePoint->hash
        );
        $nodeAddress = NodeAddress::create(
            $contentRepository->id,
            WorkspaceName::forLive(),
            $dimensionSpacePoint,
            $nodeInfo->getNodeAggregateId(),
        );
        return new MatchResult($nodeAddress->toJson(), $nodeInfo->getRouteTags());
    }

    /**
     * Outgoing URLs (link generation)
     *
     * @param array<string,mixed> &$routeValues
     */
    public function resolveWithParameters(array &$routeValues, RouteParameters $parameters): ResolveResult|false
    {
        if ($this->name === null || $this->name === '' || !\array_key_exists($this->name, $routeValues)) {
            return false;
        }
        $currentRequestSiteDetectionResult = SiteDetectionResult::fromRouteParameters($parameters);

        $nodeAddress = $routeValues[$this->name];
        if (!$nodeAddress instanceof NodeAddress) {
            return false;
        }

        try {
            $resolveResult = $this->resolveNodeAddress($nodeAddress, $currentRequestSiteDetectionResult->siteNodeName);
        } catch (NodeNotFoundException | TargetSiteNotFoundException | InvalidShortcutException $exception) {
            // TODO log exception ... yes todo
            return false;
        }

        unset($routeValues[$this->name]);
        return $resolveResult;
    }

    /**
     * Resolves a node address for uri building.
     *
     * NOTE: The resolving of nodes is also done for disabled/hidden nodes.
     *       To disallow showing a node actually disabled/hidden itself has to be ensured in matching a request path,
     *       not in building one.
     *
     * @throws InvalidShortcutException
     * @throws NodeNotFoundException
     * @throws TargetSiteNotFoundException
     */
    private function resolveNodeAddress(
        NodeAddress $nodeAddress,
        SiteNodeName $currentRequestSiteNodeName
    ): ResolveResult {
        $contentRepository = $this->contentRepositoryRegistry->get(
            $nodeAddress->contentRepositoryId
        );
        $documentUriPathFinder = $contentRepository->projectionState(DocumentUriPathFinder::class);
        $nodeInfo = $documentUriPathFinder->getByIdAndDimensionSpacePointHash(
            $nodeAddress->aggregateId,
            $nodeAddress->dimensionSpacePoint->hash
        );

        if ($nodeInfo->isShortcut()) {
            $nodeInfo = $this->nodeShortcutResolver->resolveNode($nodeInfo, $contentRepository);
            if ($nodeInfo instanceof UriInterface) {
                return $this->buildResolveResultFromUri($nodeInfo);
            }
        }

        $targetSite = $this->siteRepository->findOneByNodeName($nodeInfo->getSiteNodeName()->value);

        if ($targetSite === null) {
            throw new TargetSiteNotFoundException(sprintf('No site found for siteNodeName "%s"', $nodeInfo->getSiteNodeName()->value));
        }

        $uriConstraints = UriConstraints::create();
        if (!$targetSite->getNodeName()->equals($currentRequestSiteNodeName)) {
            $uriConstraints = $this->crossSiteLinker->applyCrossSiteUriConstraints(
                $targetSite,
                $uriConstraints
            );
        }

        $uriConstraints = $this->delegatingResolver->fromDimensionSpacePointToUriConstraints(
            $nodeAddress->dimensionSpacePoint,
            $nodeInfo,
            $targetSite,
            $uriConstraints
        );

        if ($targetSite->getConfiguration()->uriPathSuffix !== '' && $nodeInfo->hasUriPath()) {
            $uriConstraints = $uriConstraints->withPathSuffix($targetSite->getConfiguration()->uriPathSuffix);
        }

        return new ResolveResult($nodeInfo->getUriPath(), $uriConstraints, $nodeInfo->getRouteTags());
    }


    private function truncateRequestPathAndReturnRemainder(string &$requestPath, string $uriPathSuffix): string
    {
        if ($uriPathSuffix !== '') {
            $suffixPosition = strpos($requestPath, $uriPathSuffix);
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

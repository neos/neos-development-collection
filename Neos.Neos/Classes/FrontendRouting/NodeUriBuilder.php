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

use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Helper\UriHelper;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Psr\Http\Message\UriInterface;

/**
 * Neos abstraction to simplify node uri building.
 *
 * Builds URIs to nodes, taking workspace (live / shared / user) into account.
 * Can also be used in order to render "preview" URLs to nodes, that are not
 * in the live workspace (in the Neos Backend and shared workspaces)
 *
 * Internally a Flow route is configured using the {@see EventSourcedFrontendNodeRoutePartHandler}.
 * Streamlines the uri building to not having to interact with the {@see UriBuilder} or having to serialize the node address.
 *
 * @api except its constructor
 */
#[Flow\Proxy(false)]
final readonly class NodeUriBuilder
{
    /**
     * Please inject and use the {@see NodeUriBuilderFactory} to acquire this uri builder
     *
     *     #[Flow\Inject]
     *     protected NodeUriBuilderFactory $nodeUriBuilderFactory;
     *
     *     $this->nodeUriBuilderFactory->forRequest($someHttpRequest);
     *
     * @internal must not be manually instantiated but its factory must be used
     */
    public function __construct(
        private RouterInterface $router,
        /**
         * The base uri either set by using Neos.Flow.http.baseUri or inferred from the current request.
         * Note that hard coding the base uri in the settings will not work for multi sites and is only to be used as escape hatch for running Neos in a sub-directory
         */
        private UriInterface $baseUri,
        /**
         * This prefix could be used to append to all uris a prefix via `SCRIPT_NAME`, but this feature is currently not well tested and considered experimental
         */
        private string $uriPathPrefix,
        /**
         * The currently active http attributes that are used to influence the routing. The Neos frontend route part handler requires the {@see SiteDetectionResult} to be serialized in here.
         */
        private RouteParameters $routeParameters
    ) {
    }

    /**
     * Returns a human-readable host relative uri for nodes in the live workspace.
     *
     * As the human-readable uris are only routed for nodes of the live workspace {@see EventSourcedFrontendNodeRoutePartHandler}
     * Absolute preview uris are build for other workspaces {@see previewUriFor}
     *
     * Cross-linking nodes
     * -------------------
     *
     * Cross linking to a node happens when the site determined based on the current
     * route parameters (through the host and sites domain) does not belong to the linked node.
     * In this case the domain from the node's site might be used to build a host absolute uri {@see CrossSiteLinkerInterface}.
     *
     * Host relative urls are build by default for non cross-linked nodes.
     *
     * Shortcut nodes
     * --------------
     *
     * Resolving a uri for a shortcut node will result in the url pointing to the shortcut target (node, asset or external URI).
     *
     * Supported options
     * -----------------
     *
     * These options will not be considered when building a preview uri {@see previewUriFor}
     *
     * - forceAbsolute:
     *   Absolute urls for non cross-linked nodes can be enforced via {@see Options::withForceAbsolute()}.
     *   In which case the base uri determined by the request is used as host
     *   instead of a possibly configured site domain's host.
     *
     * - format:
     *   A custom format can be specified via {@see Options::withCustomFormat()}
     *
     * - routingArguments:
     *   Custom routing arguments can be specified via {@see Options::withCustomRoutingArguments()}
     *
     * Note that appending additional query parameters can be done
     * via {@see UriHelper::uriWithAdditionalQueryParameters()}:
     *
     *   UriHelper::withAdditionalQueryParameters(
     *     $this->nodeUriBuilder->uriFor(...),
     *     ['q' => 'search term']
     *   );
     *
     * @api
     * @throws NoMatchingRouteException
     *   The exception is thrown for various unlike cases in which uri building fails:
     *   - the node is disabled in the live workspace (a preview url should be built instead)
     *   - the default route definitions are misconfigured
     *   - the custom uri building options don't macht a route
     *   - the shortcut points to an invalid target
     *   - the live node address cannot be found in the projection
     *   Please consult the logs for further information.
     */
    public function uriFor(NodeAddress $nodeAddress, Options $options = null): UriInterface
    {
        $options ??= Options::createEmpty();

        if (!$nodeAddress->workspaceName->isLive()) {
            // we cannot build a human-readable uri using the showAction as
            // the DocumentUriPathProjection only handles the live workspace
            // now we fall back to building an absolute preview uri ignoring all possible options, because they are not applicable.
            // (e.g. otherwise one would need to define a custom json route also for the previewAction which is unlikely considered and untested)
            return $this->previewUriFor($nodeAddress);
        }

        $routeValues = $options->routingArguments;
        $routeValues['node'] = $nodeAddress;
        $routeValues['@action'] = strtolower('show');
        $routeValues['@controller'] = strtolower('Frontend\Node');
        $routeValues['@package'] = strtolower('Neos.Neos');

        if ($options->format !== '') {
            $routeValues['@format'] = $options->format;
        }

        return $this->router->resolve(
            new ResolveContext(
                $this->baseUri,
                $routeValues,
                $options->forceAbsolute,
                $this->uriPathPrefix,
                $this->routeParameters
            )
        );
    }

    /**
     * Returns a host absolute uri with json encoded node address as query parameter.
     *
     * Any node address regarding of content repository, or workspace can be linked to.
     * Live node address will still be encoded as query parameter and not resolved
     * as human friendly url, for that {@see uriFor} must be used.
     *
     * @api
     * @throws NoMatchingRouteException in the unlike case the preview route definition is misconfigured
     */
    public function previewUriFor(NodeAddress $nodeAddress): UriInterface
    {
        $routeValues = [];
        $routeValues['@action'] = strtolower('preview');
        $routeValues['@controller'] = strtolower('Frontend\Node');
        $routeValues['@package'] = strtolower('Neos.Neos');

        $previewActionUri = $this->router->resolve(
            new ResolveContext(
                $this->baseUri,
                $routeValues,
                true,
                $this->uriPathPrefix,
                $this->routeParameters
            )
        );
        return UriHelper::uriWithAdditionalQueryParameters(
            $previewActionUri,
            ['node' => $nodeAddress->toJson()]
        );
    }
}

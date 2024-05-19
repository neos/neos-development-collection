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

namespace Neos\Neos\FrontendRouting\NodeUri;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Psr\Http\Message\UriInterface;

final class NodeUriBuilder
{
    /**
     * @internal
     *
     * This context ($baseUri and $routeParameters) can be inferred from the current request.
     *
     * For generating node uris in cli context, you can leverage `fromBaseUri` and pass in the desired base uri,
     * Wich will be used for when generating host absolute uris.
     * If the base uri does not contain a host, absolute uris which would contain the host of the current request
     * like from `absoluteUriFor`, will be generated without host.
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly UriInterface $baseUri,
        private readonly RouteParameters $routeParameters
    ) {
    }

    /**
     * Return human readable host relative uris if the cr of the current request matches the one of the specified node.
     * For cross-links to another cr the resulting uri be absolute and contain the host of the other site's domain.
     *
     * absolute true:
     * Return human readable absolute uris with host, independent if the node is cross linked or of the current request.
     * For nodes of the current cr the passed base uri will be used as host. For cross-linked nodes the host will be derived by the site's domain.
     *
     * As the human readable uris are only routed for nodes of the live workspace (see DocumentUriProjection)
     * This method requires the node to be passed to be in the live workspace and will throw otherwise.
     *
     * @throws NoMatchingRouteException
     */
    public function uriFor(NodeAddress $nodeAddress, Options $options = null): UriInterface
    {
        if (!$nodeAddress->workspaceName->isLive()) {
            return $this->previewUriFor($nodeAddress, $options);
        }

        $routeValues = $options?->routingArguments ?? [];
        $routeValues['node'] = $nodeAddress;
        $routeValues['@action'] = strtolower('show');
        $routeValues['@controller'] = strtolower('Frontend\Node');
        $routeValues['@package'] = strtolower('Neos.Neos');

        if ($options?->format !== null && $options->format !== '') {
            $routeValues['@format'] = $options->format;
        }

        return $this->router->resolve(
            new ResolveContext(
                $this->baseUri,
                $routeValues,
                $options?->forceAbsolute ?? false,
                ltrim($this->baseUri->getPath(), '\/'),
                $this->routeParameters
            )
        );
    }

    /**
     * Returns a host relative uri with fully qualified node as query parameter encoded.
     */
    public function previewUriFor(NodeAddress $nodeAddress, Options $options = null): UriInterface
    {
        $routeValues = $options?->routingArguments ?? [];
        $routeValues['node'] = $nodeAddress->toUriString();
        $routeValues['@action'] = strtolower('preview');
        $routeValues['@controller'] = strtolower('Frontend\Node');
        $routeValues['@package'] = strtolower('Neos.Neos');

        if ($options?->format !== null && $options->format !== '') {
            $routeValues['@format'] = $options->format;
        }

        return $this->router->resolve(
            new ResolveContext(
                $this->baseUri,
                $routeValues,
                $options?->forceAbsolute ?? false,
                ltrim($this->baseUri->getPath(), '\/'),
                $this->routeParameters
            )
        );
    }
}

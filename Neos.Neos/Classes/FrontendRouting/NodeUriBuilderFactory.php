<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

#[Flow\Scope('singleton')]
final class NodeUriBuilderFactory
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function forRequest(ServerRequestInterface $request): NodeUriBuilder
    {
        $baseUri = RequestInformationHelper::generateBaseUri($request);
        $routeParameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();
        return new NodeUriBuilder($this->router, $baseUri, $routeParameters);
    }

    public function forBaseUri(UriInterface $baseUri): NodeUriBuilder
    {
        // todo???
        // $siteDetectionResult = SiteDetectionResult::fromRequest(new ServerRequest(method: 'GET', uri: $baseUri));
        // $routeParameters = $siteDetectionResult->storeInRouteParameters(RouteParameters::createEmpty());
        return new NodeUriBuilder($this->router, $baseUri, RouteParameters::createEmpty());
    }
}

<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Flow\Scope('singleton')]
final class NodeUriBuilderFactory
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function forRequest(ServerRequestInterface $request): NodeUriBuilder
    {
        // TODO Flows base uri configuration is currently ignored
        $baseUri = RequestInformationHelper::generateBaseUri($request);
        $routeParameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();
        return new NodeUriBuilder($this->router, $baseUri, $routeParameters);
    }
}

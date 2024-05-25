<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\NodeUri;

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Flow\Scope('singleton')]
final class NodeUriBuilderFactory
{
    /**
     * The possibly configured Flow base URI, see {@see \Neos\Flow\Http\BaseUriProvider}
     * @var string|null
     */
    #[Flow\InjectConfiguration(package: 'Neos.Flow', path: 'http.baseUri')]
    protected $configuredBaseUri;

    #[Flow\Inject]
    protected RouterInterface $router;

    /**
     * @api
     */
    public function forRequest(ServerRequestInterface $request): NodeUriBuilder
    {
        $baseUri = $this->configuredBaseUri !== null
            ? new Uri($this->configuredBaseUri)
            : RequestInformationHelper::generateBaseUri($request);

        $routeParameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();

        $uriPathPrefix = RequestInformationHelper::getScriptRequestPath($request);
        $uriPathPrefix = ltrim($uriPathPrefix, '/');

        return new NodeUriBuilder($this->router, $baseUri, $uriPathPrefix, $routeParameters);
    }
}

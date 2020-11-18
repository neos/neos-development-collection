<?php
declare(strict_types=1);
namespace Neos\Neos\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Simple HTTP middleware that makes the host of the currently active server request available to the routing framework
 * This allows the FrontendRoutePartHandler (and other handlers) to react on the current request when matching/resolving routes
 */
final class RequestUriHostMiddleware implements MiddlewareInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $parameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS) ?? RouteParameters::createEmpty();
        $request = $request->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, $parameters->withParameter('requestUriHost', $request->getUri()->getHost()));
        return $next->handle($request);
    }
}

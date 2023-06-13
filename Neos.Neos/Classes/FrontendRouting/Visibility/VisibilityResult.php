<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Visibility;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Psr\Http\Message\ServerRequestInterface;

/**
 * {@see VisibilityResult::fromRequest()}.
 *
 * @Flow\Proxy(false)
 */
final class VisibilityResult
{
    private const ROUTINGPARAMETER_VISIBILITY = 'visibility';

    private function __construct(
        protected readonly bool $routeResolvingOfDisabledNodesAllowed,
    ) {
    }

    public static function createRouteResolvingOfDisabledNodesAllowed()
    {
        return new self(true);
    }

    public static function createRouteResolvingOfDisabledNodesDenied()
    {
        return new self(false);
    }

    /**
     * @param ServerRequestInterface $request
     * @return static
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $routeParameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();

        return self::fromRouteParameters($routeParameters);
    }

    public static function fromRouteParameters(RouteParameters $routeParameters): self
    {
        return new self(
            $routeParameters->getValue(self::ROUTINGPARAMETER_VISIBILITY) === true
        );
    }

    public function storeInRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $parameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS)
            ?? RouteParameters::createEmpty();
        $parameters = self::storeInRouteParameters($parameters);
        return $request->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, $parameters);
    }

    public function storeInRouteParameters(RouteParameters $routeParameters): RouteParameters
    {
        return $routeParameters
            ->withParameter(
                self::ROUTINGPARAMETER_VISIBILITY,
                $this->routeResolvingOfDisabledNodesAllowed
            );
    }

    public function isRouteResolvingOfDisabledNodesAllowed(): bool
    {
        return $this->routeResolvingOfDisabledNodesAllowed;
    }
}

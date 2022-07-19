<?php
declare(strict_types=1);
namespace Neos\Neos\FrontendRouting\DimensionResolution;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Annotations as Flow;

/**
 * See {@see DimensionResolverInterface} for documentation.
 *
 * @Flow\Proxy(false)
 * @api
 */
final class DimensionResolverContext
{
    private string $uriPath;
    private string $remainingUriPath = '';
    private RouteParameters $routeParameters;
    private DimensionSpacePoint $resolvedDimensionSpacePoint;

    private function __construct(string $uriPath, RouteParameters $routeParameters)
    {
        $this->uriPath = $uriPath;
        $this->remainingUriPath = $uriPath;
        $this->routeParameters = $routeParameters;
        $this->resolvedDimensionSpacePoint = DimensionSpacePoint::fromArray([]);
    }

    public static function fromUriPathAndRouteParameters(string $uriPath, RouteParameters $routeParameters): self
    {
        return new self($uriPath, $routeParameters);
    }

    public function withAddedDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePointToAdd)
    {
        $newInstance = clone $this;

        $coordinatesSoFar = $this->resolvedDimensionSpacePoint->coordinates;
        foreach ($dimensionSpacePointToAdd->coordinates as $dimensionName => $dimensionValue) {
            $coordinatesSoFar[$dimensionName] = $dimensionValue;
        }

        $newInstance->resolvedDimensionSpacePoint = DimensionSpacePoint::fromArray($coordinatesSoFar);
        return $newInstance;
    }

    public function withRemainingUriPath(string $remainingUriPath): self
    {
        $newInstance = clone $this;
        $newInstance->remainingUriPath = $remainingUriPath;
        return $newInstance;
    }

    public function routeParameters(): RouteParameters
    {
        return $this->routeParameters;
    }

    public function uriPath(): string
    {
        return $this->uriPath;
    }

    public function remainingUriPath(): string
    {
        return $this->remainingUriPath;
    }

    public function resolvedDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->resolvedDimensionSpacePoint;
    }

}

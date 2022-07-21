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
final class RequestToDimensionSpacePointContext
{
    private function __construct(
        public readonly string $initialUriPath,
        public readonly RouteParameters $routeParameters,
        public readonly string $remainingUriPath,
        public readonly DimensionSpacePoint $resolvedDimensionSpacePoint,
    ) {
    }

    public static function fromUriPathAndRouteParameters(string $initialUriPath, RouteParameters $routeParameters): self
    {
        return new self(
            $initialUriPath,
            $routeParameters,
            $initialUriPath,
            DimensionSpacePoint::fromArray([])
        );
    }

    public function withAddedDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePointToAdd): self
    {
        $coordinatesSoFar = $this->resolvedDimensionSpacePoint->coordinates;
        foreach ($dimensionSpacePointToAdd->coordinates as $dimensionName => $dimensionValue) {
            $coordinatesSoFar[$dimensionName] = $dimensionValue;
        }

        return new self(
            $this->initialUriPath,
            $this->routeParameters,
            $this->remainingUriPath,
            DimensionSpacePoint::fromArray($coordinatesSoFar)
        );
    }

    public function withRemainingUriPath(string $remainingUriPath): self
    {
        return new self(
            $this->initialUriPath,
            $this->routeParameters,
            $remainingUriPath,
            $this->resolvedDimensionSpacePoint
        );
    }
}

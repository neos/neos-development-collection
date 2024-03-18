<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Neos\Domain\Model\Site;

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
        public readonly Site $resolvedSite,
    ) {
    }

    public static function fromUriPathAndRouteParametersAndResolvedSite(string $initialUriPath, RouteParameters $routeParameters, Site $resolvedSite): self
    {
        return new self(
            $initialUriPath,
            $routeParameters,
            $initialUriPath,
            DimensionSpacePoint::createWithoutDimensions(),
            $resolvedSite,
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
            DimensionSpacePoint::fromArray($coordinatesSoFar),
            $this->resolvedSite,
        );
    }

    public function withRemainingUriPath(string $remainingUriPath): self
    {
        return new self(
            $this->initialUriPath,
            $this->routeParameters,
            $remainingUriPath,
            $this->resolvedDimensionSpacePoint,
            $this->resolvedSite,
        );
    }
}

<?php
declare(strict_types=1);
namespace Neos\Neos\EventSourcedRouting\ContentDimensionResolver;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class ContentDimensionResolverContext
{
    private string $uriPath;
    private string $remainingUriPath;
    private RouteParameters $routeParameters;
    private array $dimensionSpacePointCoordinates = [];

    private function __construct(string $uriPath, RouteParameters $routeParameters)
    {
        $this->uriPath = $uriPath;
        $this->remainingUriPath = $uriPath;
        $this->routeParameters = $routeParameters;
    }

    public static function fromUriPathAndRouteParameters(string $uriPath, RouteParameters $routeParameters): self
    {
        return new self($uriPath, $routeParameters);
    }

    // .... -> extra helper method.
    public function addDimensionSpacePointCoordinate(ContentDimensionIdentifier $dimensionIdentifier, ContentDimensionValue $dimensionValue): self
    {
        $dimensionSpacePointCoordinates = $this->dimensionSpacePointCoordinates;
        $dimensionSpacePointCoordinates[$dimensionIdentifier->identifier] = $dimensionValue->value;
        $newInstance = clone $this;
        $newInstance->dimensionSpacePointCoordinates = $dimensionSpacePointCoordinates;
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

    public function dimensionSpacePoint(): DimensionSpacePoint
    {
        // TODO validate dsp == complete (ContentDimensionZookeeper::getAllowedDimensionSubspace()->contains()...)
        return DimensionSpacePoint::fromArray($this->dimensionSpacePointCoordinates);
    }
}

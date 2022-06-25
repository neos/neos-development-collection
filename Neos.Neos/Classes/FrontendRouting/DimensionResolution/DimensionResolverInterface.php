<?php
declare(strict_types=1);
namespace Neos\Neos\FrontendRouting\DimensionResolution;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 * Common interface for content dimension resolvers, that can:
 *
 * * Determine the DimensionSpacePoint for an incoming request (using the ContentDimensionResolverContext DTO in order to make this chainable)
 * * Apply URI constraints according to the given DimensionSpacePoint (e.g. add a path prefix for the resolved content dimensions)
 *
 *
 * Composable...
 * -> do not read Settings or any other global state here. If you need global state, ensure this is
 * injected via {@see DimensionResolverFactory}.
 *
 * Creation via {@see DimensionResolverFactory}:
 */
interface DimensionResolverInterface
{
    /**
     * @param DimensionResolverContext $context
     * @return DimensionResolverContext Note: This can contain an "incomplete" dimension space point... TODO
     */
    public function resolveDimensionSpacePoint(DimensionResolverContext $context): DimensionResolverContext;

    public function resolveDimensionUriConstraints(UriConstraints $uriConstraints, DimensionSpacePoint $dimensionSpacePoint, SiteDetectionResult $currentSite): UriConstraints;
}

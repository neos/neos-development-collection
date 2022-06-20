<?php
declare(strict_types=1);
namespace Neos\Neos\EventSourcedRouting\ContentDimensionResolver;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;

/**
 * Common interface for content dimension resolvers that can:
 * * Determine the DimensionSpacePoint for an incoming request (using the ContentDimensionResolverContext DTO in order to make this chainable)
 * * Apply URI constraints according to the given DimensionSpacePoint (e.g. add a path prefix for the resolved content dimensions)
 *
 *
 * Composable...
 * -> do not read Settings or any other global state here. If you need global state, ensure this is
 * injected via {@see ContentDimensionResolverFactory}.
 *
 * Creation via {@see ContentDimensionResolverFactory}:
 */
interface ContentDimensionResolverInterface
{
    // TODO: part of interface??
    public function __construct(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $dimensionResolverOptions);

    /**
     * @param ContentDimensionResolverContext $context
     * @return ContentDimensionResolverContext Note: This can contain an "incomplete" dimension space point... TODO
     */
    public function resolveDimensionSpacePoint(ContentDimensionResolverContext $context): ContentDimensionResolverContext;

    public function resolveDimensionUriConstraints(UriConstraints $uriConstraints, DimensionSpacePoint $dimensionSpacePoint): UriConstraints;
}

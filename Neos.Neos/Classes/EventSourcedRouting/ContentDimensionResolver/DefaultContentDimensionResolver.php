<?php
declare(strict_types=1);
namespace Neos\Neos\EventSourcedRouting\ContentDimensionResolver;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

final class DefaultContentDimensionResolver implements ContentDimensionResolverInterface
{
    private ContentDimensionSourceInterface $dimensionSource;
    private ObjectManagerInterface $objectManager;

    public function __construct(ContentDimensionSourceInterface $dimensionSource, ObjectManagerInterface $objectManager)
    {
        $this->dimensionSource = $dimensionSource;
        $this->objectManager = $objectManager;
    }

    public function resolveDimensionSpacePoint(ContentDimensionResolverContext $context): ContentDimensionResolverContext
    {
        foreach ($this->dimensionSource->getContentDimensionsOrderedByPriority() as $rawDimensionIdentifier => $contentDimension) {
            $processor = new UriPathSegmentContentDimensionValueResolver($contentDimension);
            $context = $processor->resolveDimensionSpacePoint($context);
        }
        return $context;
    }

    public function resolveDimensionUriConstraints(UriConstraints $uriConstraints, DimensionSpacePoint $dimensionSpacePoint): UriConstraints
    {
        foreach ($this->dimensionSource->getContentDimensionsOrderedByPriority() as $rawDimensionIdentifier => $contentDimension) {
            $processor = new UriPathSegmentContentDimensionValueResolver($contentDimension);
            $uriConstraints = $processor->resolveDimensionUriConstraints($uriConstraints, $dimensionSpacePoint);
        }
        return $uriConstraints;
    }
}

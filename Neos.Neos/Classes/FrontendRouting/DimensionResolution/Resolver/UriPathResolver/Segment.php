<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;

/**
 * @Flow\Proxy(false)
 */
final class Segment
{
    private function __construct(
        public readonly ContentDimensionIdentifier $dimensionIdentifier,
        public readonly string $defaultDimensionValue,
        public readonly SegmentMapping $uriPathSegmentMapping,
    )
    {
    }

    public static function create(
        ContentDimensionIdentifier $dimensionIdentifier,
        string $defaultDimensionValue,
        SegmentMapping $uriPathSegmentMapping,
    ): self
    {
        return new self(
            $dimensionIdentifier,
            $defaultDimensionValue,
            $uriPathSegmentMapping,
        );
    }
}

<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;

/**
 * @Flow\Proxy(false)
 */
final class Segment
{
    private function __construct(
        public readonly ContentDimensionId $dimensionIdentifier,
        public readonly SegmentMapping $uriPathSegmentMapping,
    ) {
    }

    public static function create(
        ContentDimensionId $dimensionIdentifier,
        SegmentMapping $uriPathSegmentMapping,
    ): self {
        return new self(
            $dimensionIdentifier,
            $uriPathSegmentMapping,
        );
    }
}

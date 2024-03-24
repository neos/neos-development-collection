<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;

use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final readonly class Segment
{
    private function __construct(
        public ContentDimensionId $dimensionIdentifier,
        public SegmentMapping $uriPathSegmentMapping,
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

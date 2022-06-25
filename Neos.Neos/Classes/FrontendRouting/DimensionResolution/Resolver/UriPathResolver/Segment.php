<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;


use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionIdentifier;

class Segment
{
    private function __construct(
        public readonly ContentDimensionIdentifier $dimensionIdentifier,
        public readonly string                     $defaultDimensionValue,
        public readonly SegmentMapping             $uriPathSegmentMapping,
        public readonly ?string                    $position = null
    ) {}
}

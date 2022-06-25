<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;


use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;

class SegmentMappingElement
{
    public function __construct(
        public readonly ContentDimensionValue $contentDimensionValue,
        public readonly string $uriPathSegmentValue
    ) {}
}

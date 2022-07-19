<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;

/**
 * @Flow\Proxy(false)
 */
final class SegmentMappingElement
{
    private function __construct(
        public readonly ContentDimensionValue $dimensionValue,
        public readonly string $uriPathSegmentValue
    )
    {
    }

    public static function create(ContentDimensionValue $dimensionValue, string $uriPathSegmentValue): self
    {
        if (str_contains($uriPathSegmentValue, '/')) {
            throw new UriPathResolverConfigurationException('Segment is not allowed to contain "/"');
        }
        return new self($dimensionValue, $uriPathSegmentValue);
    }
}

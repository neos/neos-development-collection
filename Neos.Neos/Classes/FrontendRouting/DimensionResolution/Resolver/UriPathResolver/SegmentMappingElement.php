<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;

use Neos\ContentRepository\Core\Dimension\ContentDimensionValue;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final readonly class SegmentMappingElement
{
    private function __construct(
        public ContentDimensionValue $dimensionValue,
        public string $uriPathSegmentValue
    ) {
    }

    public static function create(ContentDimensionValue $dimensionValue, string $uriPathSegmentValue): self
    {
        if (str_contains($uriPathSegmentValue, '/')) {
            throw new UriPathResolverConfigurationException('Segment is not allowed to contain "/"');
        }
        return new self($dimensionValue, $uriPathSegmentValue);
    }
}

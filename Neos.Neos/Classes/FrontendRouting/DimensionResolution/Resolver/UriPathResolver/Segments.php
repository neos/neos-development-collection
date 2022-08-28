<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Dimension\ContentDimensionIdentifier;
use Neos\Utility\PositionalArraySorter;

/**
 * @Flow\Proxy(false)
 */
final class Segments
{
    /**
     * @var Segment[]
     */
    public readonly array $segments;

    private function __construct(Segment ...$segments)
    {
        $this->segments = $segments;
    }

    public static function create(Segment ...$segments): self
    {
        return new self(...$segments);
    }

    /**
     * @param array<string,mixed> $arr
     * @return static
     */
    public static function fromArray(array $arr): self
    {
        // TODO:
        $arr = (new PositionalArraySorter($arr))->toArray();

        $segments = array_map(function (array $segArr) {
            return Segment::create(
                new ContentDimensionIdentifier($segArr['dimensionIdentifier']),
                SegmentMapping::fromArray($segArr['dimensionValueMapping'] ?? []),
            );
        }, $arr);

        return new self(...$segments);
    }
}

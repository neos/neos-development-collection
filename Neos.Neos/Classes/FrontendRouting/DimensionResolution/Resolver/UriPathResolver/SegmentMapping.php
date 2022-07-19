<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;


use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;

/**
 * @Flow\Proxy(false)
 */
final class SegmentMapping implements \IteratorAggregate, \Countable
{
    /**
     * @var SegmentMappingElement[]
     */
    private array $elements;

    private function __construct(SegmentMappingElement...$elements)
    {
        $this->elements = $elements;
    }

    public static function fromArray(array $dimensionValueMapping): self
    {
        $elements = [];
        foreach ($dimensionValueMapping as $dimensionValueStr => $uriPathSegment) {
            $dimensionValue = new ContentDimensionValue($dimensionValueStr);
            $elements[] = SegmentMappingElement::create($dimensionValue, $uriPathSegment);
        }

        return new self(...$elements);
    }

    /**
     * @return SegmentMappingElement[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->elements);
    }

    public function count(): int
    {
        return count($this->elements);
    }
}

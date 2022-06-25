<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolver;


class SegmentMapping implements \IteratorAggregate
{
    /**
     * @var SegmentMappingElement[]
     */
    private array $elements;

    /**
     * @return SegmentMappingElement[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->elements);
    }
}

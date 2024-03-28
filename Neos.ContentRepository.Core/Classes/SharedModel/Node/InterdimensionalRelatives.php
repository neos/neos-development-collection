<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;

/**
 * A collection of interdimensional relatives.
 *
 * Whenever an edge operation is to be performed by an event (i.e. new edges are created, e.g. when moving or varying nodes),
 * the tuple $parentNodeAggregateId and $succeedingSiblingId is required per dimension space point.
 * This tuple is encapsulated in this object
 *
 * @implements \IteratorAggregate<InterdimensionalRelative>
 *
 * @api part of events, can be evaluated by custom projections
 */
final readonly class InterdimensionalRelatives implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var array<InterdimensionalRelative>
     */
    public array $items;

    public function __construct(InterdimensionalRelative ...$items)
    {
        $this->items = $items;
    }

    /**
     * @param array<int,array<string,mixed>> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(...array_map(
            fn (array $itemValues): InterdimensionalRelative => InterdimensionalRelative::fromArray($itemValues),
            $values
        ));
    }

    public static function fromDimensionSpacePointSetWithoutSucceedingSiblings(
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeAggregateId $parentNodeAggregateId
    ): self {
        return new self(...array_map(
            fn (DimensionSpacePoint $dimensionSpacePoint): InterdimensionalRelative
            => new InterdimensionalRelative($dimensionSpacePoint, $parentNodeAggregateId, null),
            iterator_to_array($dimensionSpacePointSet),
        ));
    }

    public function getRelativeForDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): ?InterdimensionalRelative
    {
        foreach ($this->items as $relative) {
            if ($relative->dimensionSpacePoint->equals($dimensionSpacePoint)) {
                return $relative;
            }
        }

        return null;
    }

    public function toDimensionSpacePointSet(): DimensionSpacePointSet
    {
        return new DimensionSpacePointSet(array_map(
            fn (InterdimensionalRelative $relative): DimensionSpacePoint => $relative->dimensionSpacePoint,
            $this->items
        ));
    }

    /**
     * @return \Traversable<InterdimensionalRelative>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return InterdimensionalRelative[]
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

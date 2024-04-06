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

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * A collection of interdimensional siblings.
 *
 * Whenever an edge operation is to be performed by an event and the order of siblings is relevant
 * (e.g. when moving or varying nodes), the $succeedingSiblingId is required per dimension space point.
 * The assignment of succeeding sibling to dimension space point is encapsulated in this collection object
 *
 * @implements \IteratorAggregate<InterdimensionalSibling>
 *
 * @api part of events, can be evaluated by custom projections
 */
final readonly class InterdimensionalSiblings implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var array<int,InterdimensionalSibling>
     */
    public array $items;

    public function __construct(InterdimensionalSibling ...$items)
    {
        $this->items = array_values($items);
    }

    /**
     * @param array<int,array<string,mixed>> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(...array_map(
            fn (array $itemValues): InterdimensionalSibling => InterdimensionalSibling::fromArray($itemValues),
            $values
        ));
    }

    public static function fromDimensionSpacePointSetWithoutSucceedingSiblings(
        DimensionSpacePointSet $dimensionSpacePointSet,
    ): self {
        return new self(...array_map(
            fn (DimensionSpacePoint $dimensionSpacePoint): InterdimensionalSibling
                => new InterdimensionalSibling($dimensionSpacePoint, null),
            iterator_to_array($dimensionSpacePointSet),
        ));
    }

    public static function fromDimensionSpacePointSetWithSingleSucceedingSiblings(
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeAggregateId $succeedingSiblingId,
    ): self {
        return new self(...array_map(
            fn (DimensionSpacePoint $dimensionSpacePoint): InterdimensionalSibling
                => new InterdimensionalSibling($dimensionSpacePoint, $succeedingSiblingId),
            iterator_to_array($dimensionSpacePointSet),
        ));
    }

    public function getSucceedingSiblingIdForDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): ?NodeAggregateId
    {
        foreach ($this->items as $sibling) {
            if ($sibling->dimensionSpacePoint->equals($dimensionSpacePoint)) {
                return $sibling->nodeAggregateId;
            }
        }

        return null;
    }

    public function toDimensionSpacePointSet(): DimensionSpacePointSet
    {
        return new DimensionSpacePointSet(array_map(
            fn (InterdimensionalSibling $relative): DimensionSpacePoint => $relative->dimensionSpacePoint,
            $this->items
        ));
    }

    /**
     * @return \Traversable<InterdimensionalSibling>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return InterdimensionalSibling[]
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

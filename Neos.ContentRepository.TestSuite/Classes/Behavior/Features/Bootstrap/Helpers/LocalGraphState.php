<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The local graph state describing a single node aggregate and its direct connections
 * Comparison is done on complete node state as node identity may not be guaranteed by graph adapters
 * @implements \IteratorAggregate<string,?LocalSubgraphState>
 */
final readonly class LocalGraphState implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<string,?LocalSubgraphState> $items indexed by dimension space point hash
     */
    private function __construct(
        public array $items
    ) {
    }

    public static function fromNodeAggregateIdContentGraphAndDimensionSpacePointSet(
        NodeAggregateId $nodeAggregateId,
        ContentGraphInterface $contentGraph,
        DimensionSpacePointSet $dimensionSpacePointSet,
    ): self {
        $items = [];
        foreach ($dimensionSpacePointSet as $dimensionSpacePoint) {
            $items[$dimensionSpacePoint->hash] = LocalSubgraphState::tryForNodeAggregateIdAndSubgraph(
                nodeAggregateId: $nodeAggregateId,
                subgraph: $contentGraph->getSubgraph(
                    dimensionSpacePoint: $dimensionSpacePoint,
                    visibilityConstraints: VisibilityConstraints::createEmpty(),
                ),
            );
        }

        return new self($items);
    }

    public function diff(self $other, ?WorkspaceName $expectedWorkspaceName = null): ?self
    {
        $difference = array_merge(
            array_diff_key($this->items, $other->items), // missing items
            array_diff_key($other->items, $this->items), // additional items
            array_filter(
                array_keys(array_intersect_key($this->items, $other->items)),
                fn (string $key): bool => match (true) {
                    $this->items[$key] === null && $other->items[$key] === null => false,
                    $this->items[$key] === null && $other->items[$key] !== null => $other->items[$key]->diff($this->items[$key], $expectedWorkspaceName) !== null,
                    $this->items[$key] !== null && $other->items[$key] === null,
                        $this->items[$key] !== null && $other->items[$key] !== null => $this->items[$key]->diff($other->items[$key], $expectedWorkspaceName) !== null,
                }
            ), // differing items
        );

        return $difference === []
            ? null
            : new self($difference);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<string,?LocalSubgraphState>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

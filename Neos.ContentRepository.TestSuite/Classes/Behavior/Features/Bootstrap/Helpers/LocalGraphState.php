<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The local graph state describing a single node aggregate and its direct connections
 * Comparison is done on complete node state as node identity may not be guaranteed by graph adapters
 * @implements \IteratorAggregate<string,?LocalSubgraphState>
 */
#[Flow\Proxy(false)]
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

    public function diff(self $other, ?WorkspaceName $expectedWorkspaceName = null): ?LocalGraphStateDiff
    {
        $missingItems = array_map(
            fn (?LocalSubgraphState $item): LocalSubgraphStateDiff => $item ? LocalSubgraphStateDiff::fromLocalSubgraphState($item) : null,
            array_diff_key($this->items, $other->items),
        );
        $additionalItems = array_map(
            fn (?LocalSubgraphState $item): LocalSubgraphStateDiff => $item ? LocalSubgraphStateDiff::fromLocalSubgraphState($item) : null,
            array_diff_key($other->items, $this->items),
        );
        $differingItems = [];
        foreach (array_intersect_key($this->items, $other->items) as $key => $commonItem) {
            if ($other->items[$key] !== null) {
                $diff = $other->items[$key]->diff($this->items[$key], $expectedWorkspaceName);
                if ($diff !== null) {
                    $differingItems[$key] = $diff;
                }
            } elseif ($this->items[$key] !== null) {
                // this item was removed
                $differingItems[$key] = null;
            }
        }
        $difference = array_merge(
            $missingItems,
            $additionalItems,
            $differingItems,
        );

        return $difference === []
            ? null
            : LocalGraphStateDiff::create(array_filter($difference));
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

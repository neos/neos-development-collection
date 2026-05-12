<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The graph state describing all node aggregates and their direct connections within a workspace
 * @implements \IteratorAggregate<string,LocalGraphState> indexed by node aggregate id
 */
final class GraphState implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<string,LocalGraphState> $items indexed by node aggregate id
     */
    private function __construct(
        public array $items
    ) {
    }

    public static function forNodeAggregateIdsWorkSpaceNameAndContentRepository(
        NodeAggregateIds $nodeAggregateIds,
        ContentGraphInterface $contentGraph,
        DimensionSpacePointSet $dimensionSpacePointSet,
    ): self {
        return new self(array_map(
            fn (NodeAggregateId $nodeAggregateId): LocalGraphState => LocalGraphState::fromNodeAggregateIdContentGraphAndDimensionSpacePointSet(
                $nodeAggregateId,
                $contentGraph,
                $dimensionSpacePointSet,
            ),
            iterator_to_array($nodeAggregateIds),
        ));
    }

    public function registerItem(NodeAggregateId $nodeAggregateId, LocalGraphState $item): void
    {
        $this->items[$nodeAggregateId->value] = $item;
    }

    /**
     * @param ?WorkspaceName $expectedWorkspaceName if the diff should expect a certain workspace name for evaluating the other state
     * @return ?self A graph state containing all differing elements or nothing if nothing is changed
     */
    public function diff(?self $other, ?WorkspaceName $expectedWorkspaceName = null): ?GraphStateDiff
    {
        if ($other === null) {
            // the whole state is the diff
            return $this;
        }

        $missingItems = array_map(
            fn (LocalGraphState $item): LocalGraphStateDiff => LocalGraphStateDiff::fromLocalGraphState($item),
            array_diff_key($this->items, $other->items),
        );
        $additionalItems = array_map(
            fn (LocalGraphState $item): LocalGraphStateDiff => LocalGraphStateDiff::fromLocalGraphState($item),
            array_diff_key($other->items, $this->items),
        );
        $differingItems = [];
        foreach (array_intersect_key($this->items, $other->items) as $key => $commonItem) {
            $diff = $other->items[$key]->diff($this->items[$key], $expectedWorkspaceName);
            if ($diff !== null) {
                $differingItems[$key] = $diff;
            }
        }
        $difference = array_merge(
            $missingItems,
            $additionalItems,
            $differingItems,
        );

        return $difference === []
            ? null
            : GraphStateDiff::create($difference);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<string,LocalGraphState>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

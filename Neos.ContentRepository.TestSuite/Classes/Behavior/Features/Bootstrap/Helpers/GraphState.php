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
    public function diff(?self $other, ?WorkspaceName $expectedWorkspaceName = null): ?self
    {
        if ($other === null) {
            // the whole state is the diff
            return $this;
        }

        $difference = array_merge(
            array_diff_key($this->items, $other->items), // missing items
            array_diff_key($other->items, $this->items), // additional items
            array_filter(
                array_keys(array_intersect_key($this->items, $other->items)),
                fn (string $key): bool => $this->items[$key]->diff($other->items[$key], $expectedWorkspaceName) !== null,
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
     * @return array<string,LocalGraphState>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

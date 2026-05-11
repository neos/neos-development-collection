<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;

/**
 * The difference between two local subgraph states, described via the respective read models
 */
final readonly class LocalSubgraphStateDiff
{
    private function __construct(
        public ?Node $node,
        public ?Node $parent,
        public ?Nodes $children,
        public ?Nodes $precedingSiblings,
        public ?Nodes $succeedingSiblings,
        public ?References $references,
        public ?References $backReferences,
    ) {
    }

    public static function tryCreate(
        ?Node $node,
        ?Node $parent,
        ?Nodes $children,
        ?Nodes $precedingSiblings,
        ?Nodes $succeedingSiblings,
        ?References $references,
        ?References $backReferences,
    ): ?self {
        if (
            $node === null
            && $parent === null
            && $children === null
            && $precedingSiblings === null
            && $succeedingSiblings === null
            && $references === null
            && $backReferences === null
        ) {
            return null;
        }

        return new self(
            node: $node,
            parent: $parent,
            children: $children,
            precedingSiblings: $precedingSiblings,
            succeedingSiblings: $succeedingSiblings,
            references: $references,
            backReferences: $backReferences
        );
    }

    public static function fromLocalSubgraphState(LocalSubgraphState $localSubgraphState): self
    {
        return new self(
            node: $localSubgraphState->node,
            parent: $localSubgraphState->parent,
            children: $localSubgraphState->children,
            precedingSiblings: $localSubgraphState->precedingSiblings,
            succeedingSiblings: $localSubgraphState->succeedingSiblings,
            references: $localSubgraphState->references,
            backReferences: $localSubgraphState->backReferences,
        );
    }
}

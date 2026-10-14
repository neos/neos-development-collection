<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\Flow\Annotations as Flow;

/**
 * The difference between two local subgraph states, described via the respective read models
 */
#[Flow\Proxy(false)]
final readonly class LocalSubgraphStateDiff implements \JsonSerializable
{
    private function __construct(
        public ?NodeDiff $node,
        public ?NodeDiff $parent,
        public ?NodesDiff $children,
        public ?NodesDiff $precedingSiblings,
        public ?NodesDiff $succeedingSiblings,
        public ?ReferencesDiff $references,
        public ?ReferencesDiff $backReferences,
    ) {
    }

    public static function tryCreate(
        ?NodeDiff $node,
        ?NodeDiff $parent,
        ?NodesDiff $children,
        ?NodesDiff $precedingSiblings,
        ?NodesDiff $succeedingSiblings,
        ?ReferencesDiff $references,
        ?ReferencesDiff $backReferences,
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
            backReferences: $backReferences,
        );
    }

    public static function fromLocalSubgraphState(LocalSubgraphState $localSubgraphState): self
    {
        return new self(
            node: NodeDiff::forAnAdditionalNode($localSubgraphState->node),
            parent: $localSubgraphState->parent ? NodeDiff::forAnAdditionalNode($localSubgraphState->parent) : null,
            children: NodesDiff::tryForAnAdditionalNode($localSubgraphState->children),
            precedingSiblings: NodesDiff::tryForAnAdditionalNode($localSubgraphState->precedingSiblings),
            succeedingSiblings: NodesDiff::tryForAnAdditionalNode($localSubgraphState->succeedingSiblings),
            references: ReferencesDiff::tryForAnAdditionalNode($localSubgraphState->references),
            backReferences: ReferencesDiff::tryForAnAdditionalNode($localSubgraphState->backReferences),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'node' => $this->node,
                'parent' => $this->parent,
                'children' => $this->children,
                'precedingSiblings' => $this->precedingSiblings,
                'succeedingSiblings' => $this->succeedingSiblings,
                'references' => $this->references,
                'backReferences' => $this->backReferences,
            ],
            fn(mixed $value): bool => $value !== null
        );
    }
}

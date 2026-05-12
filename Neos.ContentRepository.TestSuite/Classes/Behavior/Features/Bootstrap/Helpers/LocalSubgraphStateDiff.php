<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;

/**
 * The difference between two local subgraph states, described via the respective read models
 */
final readonly class LocalSubgraphStateDiff implements \JsonSerializable
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

    private function serializeNode(Node $node): array
    {
        return [
            'contentRepositoryId' => $node->contentRepositoryId,
            'workspaceName' => $node->workspaceName,
            'dimensionSpacePoint' => $node->dimensionSpacePoint,
            'aggregateId' => $node->aggregateId,
            'originDimensionSpacePoint' => $node->originDimensionSpacePoint,
            'classification' => $node->classification,
            'nodeTypeName' => $node->nodeTypeName,
            'properties' => $node->properties->serialized()->values,
            'name' => $node->name,
            'tags' => $node->tags,
            'timestamps' => [
                'created' => $node->timestamps->created->format(DATE_ATOM),
                'originalCreated' => $node->timestamps->originalCreated->format(DATE_ATOM),
                'lastModified' => $node->timestamps->lastModified?->format(DATE_ATOM),
                'originalLastModified' => $node->timestamps->originalLastModified?->format(DATE_ATOM),
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function serializeNodes(Nodes $nodes): array
    {
        return $nodes->map(
            fn (Node $node): array => $this->serializeNode($node)
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function serializeReference(Reference $reference): array
    {
        return [
            'node' => $this->serializeNode($reference->node),
            'name' => $reference->name,
            'properties' => $reference->properties?->serialized()->values,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function serializeReferences(References $references): array
    {
        return array_map(
            fn (Reference $reference): array => $this->serializeReference($reference),
            iterator_to_array($references),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'node' => $this->node ? $this->serializeNode($this->node) : null,
                'parent' => $this->parent ? $this->serializeNode($this->parent) : null,
                'children' => $this->children ? $this->serializeNodes($this->children) : null,
                'precedingSiblings' => $this->precedingSiblings ? $this->serializeNodes($this->precedingSiblings) : null,
                'succeedingSiblings' => $this->succeedingSiblings ? $this->serializeNodes($this->succeedingSiblings) : null,
                'references' => $this->references ? $this->serializeReferences($this->references) : null,
                'backReferences' => $this->backReferences ? $this->serializeReferences($this->backReferences) : null,
            ],
            fn(mixed $value): bool => $value !== null
        );
    }
}

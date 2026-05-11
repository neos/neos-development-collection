<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The local subgraph state describing a node and its direct connections
 * Comparison is done on complete node state as node identity may not be guaranteed by graph adapters
 */
final readonly class LocalSubgraphState implements \JsonSerializable
{
    private function __construct(
        public Node $node,
        public ?Node $parent,
        public Nodes $children,
        public Nodes $precedingSiblings,
        public Nodes $succeedingSiblings,
        public References $references,
        public References $backReferences,
    ) {
    }

    public static function tryForNodeAggregateIdAndSubgraph(
        NodeAggregateId $nodeAggregateId,
        ContentSubgraphInterface $subgraph,
    ): ?self {
        if (($node = $subgraph->findNodeById($nodeAggregateId)) === null) {
            return null;
        }
        return new self(
            node: $node,
            parent: $subgraph->findParentNode($nodeAggregateId),
            children: $subgraph->findChildNodes($nodeAggregateId, FindChildNodesFilter::create()),
            precedingSiblings: $subgraph->findPrecedingSiblingNodes($nodeAggregateId, FindPrecedingSiblingNodesFilter::create()),
            succeedingSiblings: $subgraph->findSucceedingSiblingNodes($nodeAggregateId, FindSucceedingSiblingNodesFilter::create()),
            references: $subgraph->findReferences($nodeAggregateId, FindReferencesFilter::create()),
            backReferences: $subgraph->findBackReferences($nodeAggregateId, FindBackReferencesFilter::create()),
        );
    }

    /**
     * @return ?self A local subgraph state containing all differing elements or null if nothing is changed
     */
    public function diff(?self $other, ?WorkspaceName $expectedWorkspaceName = null): ?LocalSubgraphStateDiff
    {
        if ($other === null) {
            // the whole state is the diff
            return LocalSubgraphStateDiff::fromLocalSubgraphState($this);
        }

        return LocalSubgraphStateDiff::tryCreate(
            node: $this->diffNode($this->node, $other->node, $expectedWorkspaceName),
            parent: match (true) {
                $this->parent === null && $other->parent === null => null,
                $this->parent == null && $other->parent !== null => throw new \Exception('Cannot compare root node to node'),
                $this->parent !== null && $other->parent === null => throw new \Exception('Cannot compare node to root node'),
                default =>  $this->diffNode($this->parent, $other->parent, $expectedWorkspaceName),
            },
            children: $this->diffNodes($this->children, $other->children, $expectedWorkspaceName),
            precedingSiblings: $this->diffNodes($this->precedingSiblings, $other->precedingSiblings, $expectedWorkspaceName),
            succeedingSiblings: $this->diffNodes($this->succeedingSiblings, $other->succeedingSiblings, $expectedWorkspaceName),
            references: $this->diffReferences($this->references, $other->references, $expectedWorkspaceName),
            backReferences: $this->diffReferences($this->backReferences, $other->backReferences, $expectedWorkspaceName),
        );
    }

    private function diffNode(Node $node, Node $compared, ?WorkspaceName $expectedWorkspaceName): ?Node
    {
        return
            $node->aggregateId->equals($compared->aggregateId)
                && $compared->workspaceName->equals($expectedWorkspaceName ?: $node->workspaceName)
                && $node->dimensionSpacePoint->equals($compared->dimensionSpacePoint)
                && $node->originDimensionSpacePoint->equals($compared->originDimensionSpacePoint)
                && $node->nodeTypeName->equals($compared->nodeTypeName)
                && $node->tags->equals($compared->tags)
                && match (true) {
                    $node->name === null && $compared->name === null => true,
                    $node->name === null && $compared->name !== null,
                        $node->name !== null && $compared->name === null => false,
                    $node->name !== null && $compared->name !== null => $node->name->equals($compared->name),
                }
                && $node->contentRepositoryId->equals($compared->contentRepositoryId)
                && $node->timestamps->equals($compared->timestamps)
                && $node->classification->equals($compared->classification)
                && $node->properties->serialized()->values == $compared->properties->serialized()->values
                    // we explicitly ignore visibility constraints as they have no meaning for the CR whatsoever
                    ? null
                    : $compared;
    }

    private function diffNodes(Nodes $nodes, Nodes $compared, ?WorkspaceName $expectedWorkspaceName): ?Nodes
    {
        if (count($nodes) !== count($compared)) {
            // there is either an additional or a missing node
            return $compared;
        }
        foreach ($nodes as $i => $node) {
            $nodeToCompare = $compared[$i] ?? null;
            if (!$nodeToCompare) {
                // node is missing
                return $compared;
            }
            if ($this->diffNode($node, $nodeToCompare, $expectedWorkspaceName)) {
                // node is different
                return $compared;
            }
        }

        return null;
    }

    private function diffReferences(References $references, References $compared, ?WorkspaceName $expectedWorkspaceName): ?References
    {
        if (count($references) !== count($compared)) {
            return $compared;
        }

        foreach ($references as $i => $reference) {
            $referenceToCompare = $compared[$i] ?? null;
            if (!$referenceToCompare) {
                // reference is missing
                return $compared;
            }
            if ($this->diffReference($reference, $referenceToCompare, $expectedWorkspaceName)) {
                // reference is different
                return $compared;
            }
        }

        return null;
    }

    private function diffReference(Reference $reference, Reference $compared, ?WorkspaceName $expectedWorkspaceName): ?Reference
    {
        if (
            $this->diffNode($reference->node, $compared->node, $expectedWorkspaceName)
            || !$reference->name->equals($compared->name)
            || $reference->properties?->serialized()->values != $compared->properties?->serialized()->values
        ) {
            return $compared;
        }

        return null;
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
        return [
            'node' => $this->serializeNode($this->node),
            'parent' => $this->parent ? $this->serializeNode($this->parent) : null,
            'children' => $this->serializeNodes($this->children),
            'precedingSiblings' => $this->serializeNodes($this->precedingSiblings),
            'succeedingSiblings' => $this->serializeNodes($this->succeedingSiblings),
            'references' => $this->serializeReferences($this->references),
            'backReferences' => $this->serializeReferences($this->backReferences),
        ];
    }
}

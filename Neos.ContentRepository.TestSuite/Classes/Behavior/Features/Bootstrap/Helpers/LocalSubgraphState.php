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
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The local subgraph state describing a node and its direct connections
 * Comparison is done on complete node state as node identity may not be guaranteed by graph adapters
 */
#[Flow\Proxy(false)]
final readonly class LocalSubgraphState
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
     * @return ?LocalSubgraphStateDiff A local subgraph state diff containing all differing elements or null if nothing is changed
     */
    public function diff(?self $other, ?WorkspaceName $expectedWorkspaceName = null): ?LocalSubgraphStateDiff
    {
        if ($other === null) {
            // the whole state is the diff
            return LocalSubgraphStateDiff::fromLocalSubgraphState($this);
        }

        return LocalSubgraphStateDiff::tryCreate(
            node: NodeDiff::tryFromNodeComparison($other->node, $this->node, $expectedWorkspaceName),
            parent: match (true) {
                $this->parent === null && $other->parent === null => null,
                $this->parent == null && $other->parent !== null => throw new \Exception('Cannot compare root node to node'),
                $this->parent !== null && $other->parent === null => throw new \Exception('Cannot compare node to root node'),
                /** @phpstan-ignore argument.type (we alredy established that both are not null) */
                default => NodeDiff::tryFromNodeComparison($other->parent, $this->parent, $expectedWorkspaceName),
            },
            children: NodesDiff::tryFromNodesComparison($other->children, $this->children, $expectedWorkspaceName),
            precedingSiblings: NodesDiff::tryFromNodesComparison($other->precedingSiblings, $this->precedingSiblings, $expectedWorkspaceName),
            succeedingSiblings: NodesDiff::tryFromNodesComparison($other->succeedingSiblings, $this->succeedingSiblings, $expectedWorkspaceName),
            references: ReferencesDiff::tryFromReferencesComparison($other->references, $this->references, $expectedWorkspaceName),
            backReferences: ReferencesDiff::tryFromReferencesComparison($other->backReferences, $this->backReferences, $expectedWorkspaceName),
        );
    }
}

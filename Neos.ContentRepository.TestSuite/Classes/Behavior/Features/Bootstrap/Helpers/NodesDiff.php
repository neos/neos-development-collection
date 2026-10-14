<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The difference between two nodes read models
 */
#[Flow\Proxy(false)]
final readonly class NodesDiff implements \JsonSerializable
{
    /**
     * @param array<int,NodeDiff|NodeAggregateId> $nodes Complete node diffs for added nodes, arbitrary ones for modified ones, NodeAggregateId for unmodified ones
     */
    private function __construct(
        public ?array $nodes,
        public ?NodeAggregateIds $removedNodes,
    ) {
    }

    /**
     * @param array<int,NodeDiff|NodeAggregateId> $nodes
     */
    public static function tryCreate(
        ?array $nodes = null,
        ?NodeAggregateIds $removedNodes = null,
    ): ?self {
        if (
            $nodes === null
            && $removedNodes === null
        ) {
            return null;
        }

        return new self(
            nodes: $nodes,
            removedNodes: $removedNodes,
        );
    }

    public static function tryForAnAdditionalNode(Nodes $nodes): ?self
    {
        return self::tryCreate(
            nodes: $nodes->isEmpty()
                ? null
                : $nodes->map(
                    fn (Node $node): NodeDiff => NodeDiff::forAnAdditionalNode($node),
                ),
        );
    }

    public static function tryFromNodesComparison(
        Nodes $nodesToCompare,
        Nodes $referenceNodes,
        ?WorkspaceName $expectedWorkspaceName,
    ): ?self {
        $nodes = [];
        foreach ($nodesToCompare as $nodeToCompare) {
            $referenceNode = null;
            foreach ($referenceNodes as $availableReferenceNode) {
                if ($availableReferenceNode->aggregateId->equals($nodeToCompare->aggregateId)) {
                    $referenceNode = $availableReferenceNode;
                    break;
                }
            }
            if ($referenceNode) {
                $nodes[] = NodeDiff::tryFromNodeComparison($nodeToCompare, $referenceNode, $expectedWorkspaceName)
                    ?: $nodeToCompare->aggregateId;
            } else {
                $nodes[] = NodeDiff::forAnAdditionalNode($nodeToCompare);
            }
        }

        $removedNodes = [];
        foreach ($referenceNodes as $availableReferenceNode) {
            $nodeToCompare = null;
            foreach ($nodesToCompare as $availableNodeToCompare) {
                if ($availableNodeToCompare->aggregateId->equals($availableReferenceNode->aggregateId)) {
                    $nodeToCompare = $availableReferenceNode;
                    break;
                }
            }
            if ($nodeToCompare === null) {
                $removedNodes[] = $availableReferenceNode->aggregateId;
            }
        }

        // if nothing changed, then each node is only represented as its aggregate id and the order is the same
        $nodesIfNothingChanged = array_values(iterator_to_array($referenceNodes->toNodeAggregateIds()));

        if (
            $nodes == $nodesIfNothingChanged
            && $removedNodes === []
        ) {
            return null;
        }

        return new self(
            nodes: $nodes,
            removedNodes: $removedNodes === [] ? null : NodeAggregateIds::fromArray($removedNodes),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            get_object_vars($this),
            fn (mixed $value): bool => $value !== null,
        );
    }
}

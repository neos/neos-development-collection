<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Service\NodeDuplication;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtrees;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * Model about a non materialized node.
 *
 * The "to be created" node is not yet available - (and may never be created in case of an error).
 *
 * The transient node makes it possible, to still be able to enforce constraints {@see self::requireNodeTypeConstraintsImposedByGrandparentToBeMet()}
 * and get information {@see self::$aggregateId} about a node.
 *
 * For example the transient node can be passed as fictional $parentNode.
 * To create child transient nodes of the $parentNode use {@see self::forRegularChildNode()} and {@see self::forTetheredChildNode()}
 *
 * An initial transient node can be created with {@see self::forEntry}
 *
 * @Flow\Proxy(false)
 */
final readonly class TransientNodeCopy
{
    private function __construct(
        public NodeAggregateId $aggregateId,
        public WorkspaceName $workspaceName,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        /**
         * @var NodeAggregateIdMapping An assignment of "old" to "new" NodeAggregateIds
         */
        private NodeAggregateIdMapping $nodeAggregateIdMapping,
        /**
         * @var NodeAggregateIdsByNodePaths Deterministic NodeAggregate ids for the creation of tethered nodes (empty if there are no tethered nodes)
         */
        public NodeAggregateIdsByNodePaths $tetheredNodeAggregateIds
    ) {
    }

    public static function forEntry(
        Subtree $subtree,
        WorkspaceName $targetWorkspaceName,
        OriginDimensionSpacePoint $targetOriginDimensionSpacePoint,
        NodeAggregateIdMapping $nodeAggregateIdMapping
    ): self {
        return new self(
            $nodeAggregateIdMapping->getNewNodeAggregateId($subtree->node->aggregateId) ?? NodeAggregateId::create(),
            $targetWorkspaceName,
            $targetOriginDimensionSpacePoint,
            $nodeAggregateIdMapping,
            self::getTetheredDescendantNodeAggregateIds(
                $subtree->children,
                $nodeAggregateIdMapping,
                NodePath::createEmpty(),
                NodeAggregateIdsByNodePaths::createEmpty()
            )
        );
    }

    public function forTetheredChildNode(Subtree $subtree): self
    {
        $nodeName = $subtree->node->name;
        if (!$subtree->node->classification->isTethered() || $nodeName === null) {
            throw new \InvalidArgumentException(sprintf('Node "%s" must be tethered if given to "forTetheredChildNode".', $subtree->node->aggregateId->value));
        }

        $nodeAggregateId = $this->tetheredNodeAggregateIds->getNodeAggregateId(NodePath::fromNodeNames($nodeName));

        if ($nodeAggregateId === null) {
            throw new \InvalidArgumentException(sprintf('Name "%s" doesnt seem to be a point to a tethered node of "%s", could not determine deterministic node aggregate id.', $nodeName->value, $this->aggregateId->value));
        }

        return new self(
            $nodeAggregateId,
            $this->workspaceName,
            $this->originDimensionSpacePoint,
            $this->nodeAggregateIdMapping,
            self::getTetheredDescendantNodeAggregateIds(
                $subtree->children,
                $this->nodeAggregateIdMapping,
                NodePath::createEmpty(),
                // we don't have to keep the relative $this->tetheredNodeAggregateIds for the current $nodName as we will just recalculate them from the subtree
                NodeAggregateIdsByNodePaths::createEmpty()
            ),
        );
    }

    public function forRegularChildNode(Subtree $subtree): self
    {
        return new self(
            $this->nodeAggregateIdMapping->getNewNodeAggregateId(
                $subtree->node->aggregateId
            ) ?? NodeAggregateId::create(),
            $this->workspaceName,
            $this->originDimensionSpacePoint,
            $this->nodeAggregateIdMapping,
            self::getTetheredDescendantNodeAggregateIds(
                $subtree->children,
                $this->nodeAggregateIdMapping,
                NodePath::createEmpty(),
                NodeAggregateIdsByNodePaths::createEmpty()
            ),
        );
    }

    private static function getTetheredDescendantNodeAggregateIds(Subtrees $subtreeChildren, NodeAggregateIdMapping $nodeAggregateIdMapping, NodePath $nodePath, NodeAggregateIdsByNodePaths $tetheredNodeAggregateIds): NodeAggregateIdsByNodePaths
    {
        foreach ($subtreeChildren as $childSubtree) {
            if (!$childSubtree->node->classification->isTethered() || !$childSubtree->node->name) {
                continue;
            }

            $deterministicCopyAggregateId = $nodeAggregateIdMapping->getNewNodeAggregateId($childSubtree->node->aggregateId) ?? NodeAggregateId::create();

            $childNodePath = $nodePath->appendPathSegment($childSubtree->node->name);

            $tetheredNodeAggregateIds = $tetheredNodeAggregateIds->add(
                $childNodePath,
                $deterministicCopyAggregateId
            );

            $tetheredNodeAggregateIds = self::getTetheredDescendantNodeAggregateIds($childSubtree->children, $nodeAggregateIdMapping, $childNodePath, $tetheredNodeAggregateIds);
        }

        return $tetheredNodeAggregateIds;
    }
}

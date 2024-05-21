<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeDuplication\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeAggregateIdMapping;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeSubtreeSnapshot;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * CopyNodesRecursively command
 *
 * Creates a new node aggregate with a new node with the given `nodeAggregateId` and `nodeId`.
 * The node will be appended as child node of the given `parentNodeId` which must cover the given
 * `dimensionSpacePoint`.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class CopyNodesRecursively implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdToPublishOrDiscardInterface,
    RebasableToOtherWorkspaceInterface
{
    /**
     * @param WorkspaceName $workspaceName The name of the workspace this command is to be handled in
     * @param NodeSubtreeSnapshot $nodeTreeToInsert The snapshot of nodes to copy {@see CopyNodesRecursively::createFromSubgraphAndStartNode()}
     * @param OriginDimensionSpacePoint $targetDimensionSpacePoint the dimension space point which is the target of the copy
     * @param NodeAggregateId $targetParentNodeAggregateId Node aggregate id of the target node's parent. If not given, the node will be added as the parent's first child
     * @param NodeAggregateId|null $targetSucceedingSiblingNodeAggregateId Node aggregate id of the target node's succeeding sibling (optional)
     * @param NodeName|null $targetNodeName the root node name of the root-inserted-node
     * @param NodeAggregateIdMapping $nodeAggregateIdMapping An assignment of "old" to "new" NodeAggregateIds ({@see NodeAggregateIdMapping})
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public NodeSubtreeSnapshot $nodeTreeToInsert,
        public OriginDimensionSpacePoint $targetDimensionSpacePoint,
        public NodeAggregateId $targetParentNodeAggregateId,
        public ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId,
        public ?NodeName $targetNodeName,
        public NodeAggregateIdMapping $nodeAggregateIdMapping
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName The name of the workspace this command is to be handled in
     * @param NodeSubtreeSnapshot $nodeTreeToInsert The snapshot of nodes to copy {@see CopyNodesRecursively::createFromSubgraphAndStartNode()}
     * @param OriginDimensionSpacePoint $targetDimensionSpacePoint the dimension space point which is the target of the copy
     * @param NodeAggregateId $targetParentNodeAggregateId Node aggregate id of the target node's parent. If not given, the node will be added as the parent's first child
     * @param NodeAggregateId|null $targetSucceedingSiblingNodeAggregateId Node aggregate id of the target node's succeeding sibling (optional)
     * @param NodeAggregateIdMapping $nodeAggregateIdMapping An assignment of "old" to "new" NodeAggregateIds ({@see NodeAggregateIdMapping})
     */
    public static function create(WorkspaceName $workspaceName, NodeSubtreeSnapshot $nodeTreeToInsert, OriginDimensionSpacePoint $targetDimensionSpacePoint, NodeAggregateId $targetParentNodeAggregateId, ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId, NodeAggregateIdMapping $nodeAggregateIdMapping): self
    {
        return new self($workspaceName, $nodeTreeToInsert, $targetDimensionSpacePoint, $targetParentNodeAggregateId, $targetSucceedingSiblingNodeAggregateId, null, $nodeAggregateIdMapping);
    }

    /**
     * @todo (could be an extra method) reference start node by address instead of passing it
     */
    public static function createFromSubgraphAndStartNode(
        ContentSubgraphInterface $subgraph,
        WorkspaceName $workspaceName,
        Node $startNode,
        OriginDimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $targetParentNodeAggregateId,
        ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId
    ): self {
        $nodeSubtreeSnapshot = NodeSubtreeSnapshot::fromSubgraphAndStartNode($subgraph, $startNode);

        return new self(
            $workspaceName,
            $nodeSubtreeSnapshot,
            $dimensionSpacePoint,
            $targetParentNodeAggregateId,
            $targetSucceedingSiblingNodeAggregateId,
            null,
            NodeAggregateIdMapping::generateForNodeSubtreeSnapshot($nodeSubtreeSnapshot)
        );
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            WorkspaceName::fromString($array['workspaceName']),
            NodeSubtreeSnapshot::fromArray($array['nodeTreeToInsert']),
            OriginDimensionSpacePoint::fromArray($array['targetDimensionSpacePoint']),
            NodeAggregateId::fromString($array['targetParentNodeAggregateId']),
            isset($array['targetSucceedingSiblingNodeAggregateId'])
                ? NodeAggregateId::fromString($array['targetSucceedingSiblingNodeAggregateId'])
                : null,
            isset($array['targetNodeName']) ? NodeName::fromString($array['targetNodeName']) : null,
            NodeAggregateIdMapping::fromArray($array['nodeAggregateIdMapping'])
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        $targetNodeAggregateId = $this->nodeAggregateIdMapping->getNewNodeAggregateId(
            $this->nodeTreeToInsert->nodeAggregateId
        );
        return (
            !is_null($targetNodeAggregateId)
                && $this->targetDimensionSpacePoint->equals($nodeIdToPublish->dimensionSpacePoint)
                && $targetNodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
        );
    }

    public function withNodeAggregateIdMapping(
        NodeAggregateIdMapping $nodeAggregateIdMapping
    ): self {
        return new self(
            $this->workspaceName,
            $this->nodeTreeToInsert,
            $this->targetDimensionSpacePoint,
            $this->targetParentNodeAggregateId,
            $this->targetSucceedingSiblingNodeAggregateId,
            $this->targetNodeName,
            $nodeAggregateIdMapping
        );
    }

    /**
     * The target node's optional name.
     *
     * @deprecated the concept regarding node-names for non-tethered nodes is outdated.
     */
    public function withTargetNodeName(NodeName $targetNodeName): self
    {
        return new self(
            $this->workspaceName,
            $this->nodeTreeToInsert,
            $this->targetDimensionSpacePoint,
            $this->targetParentNodeAggregateId,
            $this->targetSucceedingSiblingNodeAggregateId,
            $targetNodeName,
            $this->nodeAggregateIdMapping
        );
    }

    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): self {
        return new self(
            $targetWorkspaceName,
            $this->nodeTreeToInsert,
            $this->targetDimensionSpacePoint,
            $this->targetParentNodeAggregateId,
            $this->targetSucceedingSiblingNodeAggregateId,
            $this->targetNodeName,
            $this->nodeAggregateIdMapping
        );
    }
}

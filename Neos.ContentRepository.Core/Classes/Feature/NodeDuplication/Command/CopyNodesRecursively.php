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
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeAggregateIdMapping;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeSubtreeSnapshot;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * CopyNodesRecursively command
 *
 * Creates a new node aggregate with a new node with the given `nodeAggregateId` and `nodeId`.
 * The node will be appended as child node of the given `parentNodeId` which must cover the given
 * `dimensionSpacePoint`.
 *
 * @api commands are the write-API of the ContentRepository
 */
final class CopyNodesRecursively implements
    CommandInterface,
    \JsonSerializable,
    MatchableWithNodeIdToPublishOrDiscardInterface,
    RebasableToOtherContentStreamsInterface
{
    /**
     * @param ContentStreamId $contentStreamId The id of the content stream this command is to be handled in
     * @param NodeSubtreeSnapshot $nodeTreeToInsert The snapshot of nodes to copy {@see CopyNodesRecursively::createFromSubgraphAndStartNode()}
     * @param OriginDimensionSpacePoint $targetDimensionSpacePoint the dimension space point which is the target of the copy
     * @param NodeAggregateId $targetParentNodeAggregateId Node aggregate id of the target node's parent. If not given, the node will be added as the parent's first child
     * @param NodeAggregateId|null $targetSucceedingSiblingNodeAggregateId Node aggregate id of the target node's succeeding sibling (optional)
     * @param NodeName|null $targetNodeName the root node name of the root-inserted-node
     * @param NodeAggregateIdMapping $nodeAggregateIdMapping An assignment of "old" to "new" NodeAggregateIds ({@see NodeAggregateIdMapping})
     */
    private function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeSubtreeSnapshot $nodeTreeToInsert,
        public readonly OriginDimensionSpacePoint $targetDimensionSpacePoint,
        public readonly NodeAggregateId $targetParentNodeAggregateId,
        public readonly ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId,
        public readonly ?NodeName $targetNodeName,
        public readonly NodeAggregateIdMapping $nodeAggregateIdMapping
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId The id of the content stream this command is to be handled in
     * @param NodeSubtreeSnapshot $nodeTreeToInsert The snapshot of nodes to copy {@see CopyNodesRecursively::createFromSubgraphAndStartNode()}
     * @param OriginDimensionSpacePoint $targetDimensionSpacePoint the dimension space point which is the target of the copy
     * @param NodeAggregateId $targetParentNodeAggregateId Node aggregate id of the target node's parent. If not given, the node will be added as the parent's first child
     * @param NodeAggregateId|null $targetSucceedingSiblingNodeAggregateId Node aggregate id of the target node's succeeding sibling (optional)
     * @param NodeName|null $targetNodeName the root node name of the root-inserted-node
     * @param NodeAggregateIdMapping $nodeAggregateIdMapping An assignment of "old" to "new" NodeAggregateIds ({@see NodeAggregateIdMapping})
     */
    public static function create(ContentStreamId $contentStreamId, NodeSubtreeSnapshot $nodeTreeToInsert, OriginDimensionSpacePoint $targetDimensionSpacePoint, NodeAggregateId $targetParentNodeAggregateId, ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId, ?NodeName $targetNodeName, NodeAggregateIdMapping $nodeAggregateIdMapping): self
    {
        return new self($contentStreamId, $nodeTreeToInsert, $targetDimensionSpacePoint, $targetParentNodeAggregateId, $targetSucceedingSiblingNodeAggregateId, $targetNodeName, $nodeAggregateIdMapping);
    }

    /**
     * @todo (could be an extra method) reference start node by address instead of passing it
     */
    public static function createFromSubgraphAndStartNode(
        ContentSubgraphInterface $subgraph,
        Node $startNode,
        OriginDimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $targetParentNodeAggregateId,
        ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId,
        ?NodeName $targetNodeName
    ): self {
        $nodeSubtreeSnapshot = NodeSubtreeSnapshot::fromSubgraphAndStartNode($subgraph, $startNode);

        return new self(
            $startNode->subgraphIdentity->contentStreamId,
            $nodeSubtreeSnapshot,
            $dimensionSpacePoint,
            $targetParentNodeAggregateId,
            $targetSucceedingSiblingNodeAggregateId,
            $targetNodeName,
            NodeAggregateIdMapping::generateForNodeSubtreeSnapshot($nodeSubtreeSnapshot)
        );
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
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
                && $this->contentStreamId === $nodeIdToPublish->contentStreamId
                && $this->targetDimensionSpacePoint->equals($nodeIdToPublish->dimensionSpacePoint)
                && $targetNodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
        );
    }

    public function createCopyForContentStream(ContentStreamId $target): self
    {
        return new self(
            $target,
            $this->nodeTreeToInsert,
            $this->targetDimensionSpacePoint,
            $this->targetParentNodeAggregateId,
            $this->targetSucceedingSiblingNodeAggregateId,
            $this->targetNodeName,
            $this->nodeAggregateIdMapping
        );
    }

    public function withNodeAggregateIdMapping(
        NodeAggregateIdMapping $nodeAggregateIdMapping
    ): self {
        return new self(
            $this->contentStreamId,
            $this->nodeTreeToInsert,
            $this->targetDimensionSpacePoint,
            $this->targetParentNodeAggregateId,
            $this->targetSucceedingSiblingNodeAggregateId,
            $this->targetNodeName,
            $nodeAggregateIdMapping
        );
    }
}

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
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeAggregateIdMapping;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeSubtreeSnapshot;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\User\UserId;

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
    private function __construct(
        /**
         * The id of the content stream this command is to be handled in
         *
         * @var ContentStreamId
         */
        public readonly ContentStreamId $contentStreamId,
        /**
         * The to be copied node's node aggregate id
         *
         * @var NodeSubtreeSnapshot
         */
        public readonly NodeSubtreeSnapshot $nodeTreeToInsert,
        /**
         * the dimension space point which is the target of the copy
         *
         * @var OriginDimensionSpacePoint
         */
        public readonly OriginDimensionSpacePoint $targetDimensionSpacePoint,
        public readonly UserId $initiatingUserId,
        /**
         * Node aggregate id of the target node's parent (optional)
         *
         * If not given, the node will be added as a root node
         *
         * @var NodeAggregateId
         */
        public readonly NodeAggregateId $targetParentNodeAggregateId,
        /**
         * Node aggregate id of the target node's succeeding sibling (optional)
         *
         * If not given, the node will be added as the parent's first child
         *
         * @var NodeAggregateId|null
         */
        public readonly ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId,
        /**
         * the root node name of the root-inserted-node
         *
         * @var NodeName|null
         */
        public readonly ?NodeName $targetNodeName,
        public readonly NodeAggregateIdMapping $nodeAggregateIdMapping
    ) {
    }

    /**
     * @todo (could be an extra method) reference start node by address instead of passing it
     */
    public static function createFromSubgraphAndStartNode(
        ContentSubgraphInterface $subgraph,
        Node $startNode,
        OriginDimensionSpacePoint $dimensionSpacePoint,
        UserId $initiatingUserId,
        NodeAggregateId $targetParentNodeAggregateId,
        ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId,
        ?NodeName $targetNodeName
    ): self {
        $nodeSubtreeSnapshot = NodeSubtreeSnapshot::fromSubgraphAndStartNode($subgraph, $startNode);

        return new self(
            $startNode->subgraphIdentity->contentStreamId,
            $nodeSubtreeSnapshot,
            $dimensionSpacePoint,
            $initiatingUserId,
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
            UserId::fromString($array['initiatingUserId']),
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
        return [
            'contentStreamId' => $this->contentStreamId,
            'nodeTreeToInsert' => $this->nodeTreeToInsert,
            'targetDimensionSpacePoint' => $this->targetDimensionSpacePoint,
            'initiatingUserId' => $this->initiatingUserId,
            'targetParentNodeAggregateId' => $this->targetParentNodeAggregateId,
            'targetSucceedingSiblingNodeAggregateId' => $this->targetSucceedingSiblingNodeAggregateId,
            'targetNodeName' => $this->targetNodeName,
            'nodeAggregateIdMapping' => $this->nodeAggregateIdMapping,
        ];
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
            $this->initiatingUserId,
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
            $this->initiatingUserId,
            $this->targetParentNodeAggregateId,
            $this->targetSucceedingSiblingNodeAggregateId,
            $this->targetNodeName,
            $nodeAggregateIdMapping
        );
    }
}

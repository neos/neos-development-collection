<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeMove\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\NodeMoveMappings;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * A node aggregate was moved in a content stream as defined in the node move mappings.
 *
 * We always move a node aggregate (in a given ContentStreamId, identified by a NodeAggregateId).
 *
 * You can move any amount of nodes in the aggregate.
 * The targets (new parents // new succeeding) for each node & dimension space point
 * are specified in {@see NodeMoveMappings}.
 *
 * @api events are the persistence-API of the content repository
 */
final class NodeAggregateWasMoved implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        /**
         * The MoveNodeMappings contains for every OriginDimensionSpacePoint of the aggregate which should be moved,
         * a list of new parent NodeAggregateIds, and a list of new succeeding-sibling NodeAggregateIds.
         *
         * This happens between
         * MoveNodeMappings
         *   (list of MoveNodeMapping)
         *   one MoveNodeMapping == one OriginDimensionSpacePoint we want to move.
         *     -> new parents need to be specified (NodeVariantAssignments);
         *        new succeeding siblings need to be specified (for order) (NodeVariantAssignments)
         *     -> !!! this might be multiple DIFFERENT ones, because one OriginDimensionSpacePoint might shine through
         *        into different covered dimensions, and there it might be at a different location.
         *         the KEY here is the COVERED DSP Hash (!!!) - TODO should be fixed
         *         the value is the Id + Origin Dimension Space Point OF THE PARENT
         *
         * @var NodeMoveMappings|null
         */
        public readonly ?NodeMoveMappings $nodeMoveMappings,
        /**
         * This specifies all "edges" which should move to the END of their siblings.
         * All dimension space points included here must NOT be part of any MoveNodeMapping.
         *
         * This case is needed because we can only specify a new parent or/and a new succeeding sibling in the
         * MoveNodeMappings; so we need a way to "move to the end".
         *
         * @var DimensionSpacePointSet
         */
        public readonly DimensionSpacePointSet $repositionNodesWithoutAssignments,
        public readonly UserId $initiatingUserId
    ) {
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->nodeMoveMappings,
            $this->repositionNodesWithoutAssignments,
            $this->initiatingUserId
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            NodeMoveMappings::fromArray($values['nodeMoveMappings']),
            DimensionSpacePointSet::fromArray($values['repositionNodesWithoutAssignments']),
            UserId::fromString($values['initiatingUserId'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'nodeAggregateId' => $this->nodeAggregateId,
            'nodeMoveMappings' => $this->nodeMoveMappings,
            'repositionNodesWithoutAssignments' => $this->repositionNodesWithoutAssignments,
            'initiatingUserId' => $this->initiatingUserId
        ];
    }
}

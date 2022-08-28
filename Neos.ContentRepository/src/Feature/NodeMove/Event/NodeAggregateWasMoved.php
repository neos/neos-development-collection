<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\NodeMove\Event;

use Neos\ContentRepository\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\EventStore\EventInterface;

/**
 * A node aggregate was moved in a content stream as defined in the node move mappings.
 *
 * We always move a node aggregate (in a given ContentStreamIdentifier, identified by a NodeAggregateIdentifier).
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
    EmbedsContentStreamAndNodeAggregateIdentifier
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        /**
         * The MoveNodeMappings contains for every OriginDimensionSpacePoint of the aggregate which should be moved,
         * a list of new parent NodeAggregateIdentifiers, and a list of new succeeding-sibling NodeAggregateIdentifiers.
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
         *         the value is the Identifier + Origin Dimension Space Point OF THE PARENT
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
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->nodeMoveMappings,
            $this->repositionNodesWithoutAssignments,
            $this->initiatingUserIdentifier
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamIdentifier::fromString($values['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($values['nodeAggregateIdentifier']),
            NodeMoveMappings::fromArray($values['nodeMoveMappings']),
            DimensionSpacePointSet::fromArray($values['repositionNodesWithoutAssignments']),
            UserIdentifier::fromString($values['initiatingUserIdentifier'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'nodeMoveMappings' => $this->nodeMoveMappings,
            'repositionNodesWithoutAssignments' => $this->repositionNodesWithoutAssignments,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }
}

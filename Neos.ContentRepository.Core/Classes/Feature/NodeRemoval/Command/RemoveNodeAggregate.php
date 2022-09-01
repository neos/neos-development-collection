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

namespace Neos\ContentRepository\Core\Feature\NodeRemoval\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\User\UserId;

/**
 * @api commands are the write-API of the ContentRepository
 */
final class RemoveNodeAggregate implements
    CommandInterface,
    \JsonSerializable,
    RebasableToOtherContentStreamsInterface,
    MatchableWithNodeIdToPublishOrDiscardInterface
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        /** One of the dimension space points covered by the node aggregate in which the user intends to remove it */
        public readonly DimensionSpacePoint $coveredDimensionSpacePoint,
        public readonly NodeVariantSelectionStrategy $nodeVariantSelectionStrategy,
        public readonly UserId $initiatingUserId,
        /**
         * This is usually the NodeAggregateId of the parent node of the deleted node. It is needed for instance
         * in the Neos UI for the following scenario:
         * - when removing a node, you still need to be able to publish the removal.
         * - For this to work, the Neos UI needs to know the id of the removed Node, **on the page
         *   where the removal happened** (so that the user can decide to publish a single page INCLUDING the removal
         *   on the page)
         * - Because this command will *remove* the edge,
         *   we cannot know the position in the tree after doing the removal anymore.
         *
         * That's why we need this field: For the Neos UI, it stores the document node of the removed node
         * (see Remove.php), as that is what the UI needs lateron for the change display.
         */
        public readonly ?NodeAggregateId $removalAttachmentPoint = null
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ContentStreamId::fromString($array['contentStreamId']),
            NodeAggregateId::fromString($array['nodeAggregateId']),
            DimensionSpacePoint::fromArray($array['coveredDimensionSpacePoint']),
            NodeVariantSelectionStrategy::from($array['nodeVariantSelectionStrategy']),
            UserId::fromString($array['initiatingUserId']),
            isset($array['removalAttachmentPoint'])
                ? NodeAggregateId::fromString($array['removalAttachmentPoint'])
                : null
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'nodeAggregateId' => $this->nodeAggregateId,
            'coveredDimensionSpacePoint' => $this->coveredDimensionSpacePoint,
            'nodeVariantSelectionStrategy' => $this->nodeVariantSelectionStrategy,
            'initiatingUserId' => $this->initiatingUserId,
            'removalAttachmentPoint' => $this->removalAttachmentPoint
        ];
    }

    public function createCopyForContentStream(ContentStreamId $target): self
    {
        return new self(
            $target,
            $this->nodeAggregateId,
            $this->coveredDimensionSpacePoint,
            $this->nodeVariantSelectionStrategy,
            $this->initiatingUserId,
            $this->removalAttachmentPoint
        );
    }

    public function matchesNodeId(NodeIdToPublishOrDiscard $nodeIdToPublish): bool
    {
        return (
            $this->contentStreamId === $nodeIdToPublish->contentStreamId
                && $this->nodeAggregateId->equals($nodeIdToPublish->nodeAggregateId)
                && $this->coveredDimensionSpacePoint === $nodeIdToPublish->dimensionSpacePoint
        );
    }
}

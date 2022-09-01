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

namespace Neos\ContentRepository\Core\Feature\NodeRemoval\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * @api events are the persistence-API of the content repository
 */
final class NodeAggregateWasRemoved implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly OriginDimensionSpacePointSet $affectedOccupiedDimensionSpacePoints,
        public readonly DimensionSpacePointSet $affectedCoveredDimensionSpacePoints,
        public readonly UserId $initiatingUserId,
        /** {@see RemoveNodeAggregate::$removalAttachmentPoint} for detailed docs what this is used for. */
        public readonly ?NodeAggregateId $removalAttachmentPoint = null
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
        return new NodeAggregateWasRemoved(
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->affectedOccupiedDimensionSpacePoints,
            $this->affectedCoveredDimensionSpacePoints,
            $this->initiatingUserId,
            $this->removalAttachmentPoint
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            OriginDimensionSpacePointSet::fromArray($values['affectedOccupiedDimensionSpacePoints']),
            DimensionSpacePointSet::fromArray($values['affectedCoveredDimensionSpacePoints']),
            UserId::fromString($values['initiatingUserId']),
            isset($values['removalAttachmentPoint'])
                ? NodeAggregateId::fromString($values['removalAttachmentPoint'])
                : null,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'nodeAggregateId' => $this->nodeAggregateId,
            'affectedOccupiedDimensionSpacePoints' => $this->affectedOccupiedDimensionSpacePoints,
            'affectedCoveredDimensionSpacePoints' => $this->affectedCoveredDimensionSpacePoints,
            'initiatingUserId' => $this->initiatingUserId,
            'removalAttachmentPoint' => $this->removalAttachmentPoint
        ];
    }
}

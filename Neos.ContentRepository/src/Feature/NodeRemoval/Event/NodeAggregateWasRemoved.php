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

namespace Neos\ContentRepository\Feature\NodeRemoval\Event;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\EventStore\EventInterface;

final class NodeAggregateWasRemoved implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateIdentifier
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly OriginDimensionSpacePointSet $affectedOccupiedDimensionSpacePoints,
        public readonly DimensionSpacePointSet $affectedCoveredDimensionSpacePoints,
        public readonly UserIdentifier $initiatingUserIdentifier,
        /** {@see RemoveNodeAggregate::$removalAttachmentPoint} for detailed docs what this is used for. */
        public readonly ?NodeAggregateIdentifier $removalAttachmentPoint = null
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
        return new NodeAggregateWasRemoved(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->affectedOccupiedDimensionSpacePoints,
            $this->affectedCoveredDimensionSpacePoints,
            $this->initiatingUserIdentifier,
            $this->removalAttachmentPoint
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamIdentifier::fromString($values['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($values['nodeAggregateIdentifier']),
            OriginDimensionSpacePointSet::fromArray($values['affectedOccupiedDimensionSpacePoints']),
            DimensionSpacePointSet::fromArray($values['affectedCoveredDimensionSpacePoints']),
            UserIdentifier::fromString($values['initiatingUserIdentifier']),
            isset($values['removalAttachmentPoint'])
                ? NodeAggregateIdentifier::fromString($values['removalAttachmentPoint'])
                : null,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'affectedOccupiedDimensionSpacePoints' => $this->affectedOccupiedDimensionSpacePoints,
            'affectedCoveredDimensionSpacePoints' => $this->affectedCoveredDimensionSpacePoints,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'removalAttachmentPoint' => $this->removalAttachmentPoint
        ];
    }
}

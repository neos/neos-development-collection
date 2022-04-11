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

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class NodeAggregateWasRemoved implements
    DomainEventInterface,
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
    ) {}

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
}

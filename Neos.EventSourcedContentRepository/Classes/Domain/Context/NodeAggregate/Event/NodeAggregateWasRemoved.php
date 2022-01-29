<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class NodeAggregateWasRemoved implements DomainEventInterface, PublishableToOtherContentStreamsInterface, EmbedsContentStreamAndNodeAggregateIdentifier
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    private DimensionSpacePointSet $affectedOccupiedDimensionSpacePoints;

    private DimensionSpacePointSet $affectedCoveredDimensionSpacePoints;

    private UserIdentifier $initiatingUserIdentifier;

    /**
     * {@see RemoveNodeAggregate::$removalAttachmentPoint} for detailed docs what this is used for.
     *
     * @var NodeAggregateIdentifier|null
     */
    private ?NodeAggregateIdentifier $removalAttachmentPoint;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePointSet $affectedOccupiedDimensionSpacePoints,
        DimensionSpacePointSet $affectedCoveredDimensionSpacePoints,
        UserIdentifier $initiatingUserIdentifier,
        ?NodeAggregateIdentifier $removalAttachmentPoint = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->affectedOccupiedDimensionSpacePoints = $affectedOccupiedDimensionSpacePoints;
        $this->affectedCoveredDimensionSpacePoints = $affectedCoveredDimensionSpacePoints;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->removalAttachmentPoint = $removalAttachmentPoint;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getAffectedOccupiedDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->affectedOccupiedDimensionSpacePoints;
    }

    public function getAffectedCoveredDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->affectedCoveredDimensionSpacePoints;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier|null
     */
    public function getRemovalAttachmentPoint(): ?NodeAggregateIdentifier
    {
        return $this->removalAttachmentPoint;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier)
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

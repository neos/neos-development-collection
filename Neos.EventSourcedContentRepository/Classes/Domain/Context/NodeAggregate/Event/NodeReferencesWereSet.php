<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifierCollection;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A named reference from source to destination node was created
 */
#[Flow\Proxy(false)]
final class NodeReferencesWereSet implements DomainEventInterface, PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateIdentifier
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private NodeAggregateIdentifier $sourceNodeAggregateIdentifier;

    private OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint;

    private NodeAggregateIdentifierCollection $destinationNodeAggregateIdentifiers;

    private PropertyName $referenceName;

    private UserIdentifier $initiatingUserIdentifier;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $sourceNodeAggregateIdentifier,
        OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        NodeAggregateIdentifierCollection $destinationNodeAggregateIdentifiers,
        PropertyName $referenceName,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->sourceNodeAggregateIdentifier = $sourceNodeAggregateIdentifier;
        $this->sourceOriginDimensionSpacePoint = $sourceOriginDimensionSpacePoint;
        $this->destinationNodeAggregateIdentifiers = $destinationNodeAggregateIdentifiers;
        $this->referenceName = $referenceName;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getSourceNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->sourceNodeAggregateIdentifier;
    }

    public function getSourceOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->sourceOriginDimensionSpacePoint;
    }

    public function getDestinationNodeAggregateIdentifiers(): NodeAggregateIdentifierCollection
    {
        return $this->destinationNodeAggregateIdentifiers;
    }

    public function getReferenceName(): PropertyName
    {
        return $this->referenceName;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->sourceNodeAggregateIdentifier,
            $this->sourceOriginDimensionSpacePoint,
            $this->destinationNodeAggregateIdentifiers,
            $this->referenceName,
            $this->initiatingUserIdentifier
        );
    }

    /**
     * this method is implemented for fulfilling the {@see EmbedsContentStreamAndNodeAggregateIdentifier} interface,
     * needed for proper content cache flushing in Neos.
     *
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->getSourceNodeAggregateIdentifier();
    }
}

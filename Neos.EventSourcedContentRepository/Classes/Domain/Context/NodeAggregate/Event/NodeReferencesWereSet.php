<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A named reference from source to destination node was created
 *
 * @Flow\Proxy(false)
 */
final class NodeReferencesWereSet implements DomainEventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $sourceNodeAggregateIdentifier;

    /**
     * @var OriginDimensionSpacePoint
     */
    private $sourceOriginDimensionSpacePoint;

    /**
     * @var NodeAggregateIdentifier[]
     */
    private $destinationNodeAggregateIdentifiers;

    /**
     * @var PropertyName
     */
    private $referenceName;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $sourceNodeAggregateIdentifier,
        OriginDimensionSpacePoint $sourceOriginDimensionSpacePoint,
        array $destinationNodeAggregateIdentifiers,
        PropertyName $referenceName
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->sourceNodeAggregateIdentifier = $sourceNodeAggregateIdentifier;
        $this->sourceOriginDimensionSpacePoint = $sourceOriginDimensionSpacePoint;
        $this->destinationNodeAggregateIdentifiers = $destinationNodeAggregateIdentifiers;
        $this->referenceName = $referenceName;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getSourceNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->sourceNodeAggregateIdentifier;
    }

    /**
     * @return OriginDimensionSpacePoint
     */
    public function getSourceOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->sourceOriginDimensionSpacePoint;
    }

    /**
     * @return NodeAggregateIdentifier[]
     */
    public function getDestinationNodeAggregateIdentifiers(): array
    {
        return $this->destinationNodeAggregateIdentifiers;
    }

    /**
     * @return PropertyName
     */
    public function getReferenceName(): PropertyName
    {
        return $this->referenceName;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier)
    {
        return new NodeReferencesWereSet(
            $targetContentStreamIdentifier,
            $this->sourceNodeAggregateIdentifier,
            $this->sourceOriginDimensionSpacePoint,
            $this->destinationNodeAggregateIdentifiers,
            $this->referenceName
        );
    }
}

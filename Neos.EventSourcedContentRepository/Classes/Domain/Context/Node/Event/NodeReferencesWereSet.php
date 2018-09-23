<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Event;

use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcing\Event\EventInterface;

/**
 * A named reference from source- to destination-node was created
 */
final class NodeReferencesWereSet implements EventInterface, CopyableAcrossContentStreamsInterface
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var DimensionSpacePointSet
     */
    private $dimensionSpacePointSet;

    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var NodeAggregateIdentifier[]
     */
    private $destinationNodeAggregateIdentifiers;

    /**
     * @var PropertyName
     */
    private $propertyName;

    /**
     * ReferenceBetweenNodesWasCreated constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @param NodeIdentifier $nodeIdentifier
     * @param PropertyName $referenceNodeIdentifier
     * @param array $destinationNodeIdentifiers
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet,
        NodeIdentifier $nodeIdentifier,
        PropertyName $propertyName,
        array $destinationNodeAggregateIdentifiers
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePointSet = $dimensionSpacePointSet;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->propertyName = $propertyName;
        $this->destinationNodeAggregateIdentifiers = $destinationNodeAggregateIdentifiers;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getDimensionSpacePointSet(): DimensionSpacePointSet
    {
        return $this->dimensionSpacePointSet;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return array
     */
    public function getDestinationNodeAggregateIdentifiers(): array
    {
        return $this->destinationNodeAggregateIdentifiers;
    }

    /**
     * @return PropertyName
     */
    public function getPropertyName(): PropertyName
    {
        return $this->propertyName;
    }



    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        return new NodeReferencesWereSet(
            $targetContentStream,
            $this->dimensionSpacePointSet,
            $this->nodeIdentifier,
            $this->propertyName,
            $this->destinationNodeAggregateIdentifiers
        );
    }
}

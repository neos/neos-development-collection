<?php

namespace Neos\ContentRepository\Domain\Context\Node\Event;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcing\Event\EventInterface;

/**
 * A named reference from source- to destination-node was created
 */
final class ReferenceBetweenNodesWasCreated implements EventInterface, CopyableAcrossContentStreamsInterface
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
     * @var NodeAggregateIdentifier
     */
    private $sourceNodeIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $destinationtNodeIdentifier;

    /**
     * @var PropertyName
     */
    private $propertyName;

    /**
     * ReferenceBetweenNodesWasCreated constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @param NodeAggregateIdentifier $nodeIdentifier
     * @param NodeAggregateIdentifier $referencePosition
     * @param PropertyName $referenceNodeIdentifier
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet,
        NodeAggregateIdentifier $sourceNodeIdentifier,
        NodeAggregateIdentifier $destinationtNodeIdentifier,
        PropertyName $propertyName
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePointSet = $dimensionSpacePointSet;
        $this->sourceNodeIdentifier = $sourceNodeIdentifier;
        $this->destinationtNodeIdentifier = $destinationtNodeIdentifier;
        $this->propertyName = $propertyName;
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
     * @return NodeAggregateIdentifier
     */
    public function getSourceNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->sourceNodeIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getDestinationtNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->destinationtNodeIdentifier;
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
        return new ReferenceBetweenNodesWasCreated(
            $targetContentStream,
            $this->dimensionSpacePointSet,
            $this->sourceNodeIdentifier,
            $this->destinationtNodeIdentifier,
            $this->propertyName
        );
    }
}

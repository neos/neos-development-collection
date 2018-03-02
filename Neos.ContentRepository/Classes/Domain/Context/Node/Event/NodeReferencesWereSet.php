<?php

namespace Neos\ContentRepository\Domain\Context\Node\Event;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\PropertyName;
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
    private $destinationtNodesAggregateIdentifiers;

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
     * @param array $destinationtNodesAggregateIdentifiers
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet,
        NodeIdentifier $nodeIdentifier,
        PropertyName $propertyName,
        array $destinationtNodesAggregateIdentifiers
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePointSet = $dimensionSpacePointSet;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->propertyName = $propertyName;
        $this->destinationtNodesAggregateIdentifiers = $destinationtNodesAggregateIdentifiers;
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
    public function getDestinationtNodesAggregateIdentifiers(): array
    {
        return $this->destinationtNodesAggregateIdentifiers;
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
            $this->destinationtNodesAggregateIdentifiers
        );
    }
}

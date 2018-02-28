<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\PropertyName;

/**
 * Create a named reference from source- to destination-node
 */
final class SetNodeReferences
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
    private $destinationtNodeAggregateIdentifiers;

    /**
     * @var PropertyName
     */
    private $propertyName;

    /**
     * CreateReferenceBetweenNodes constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeAggregateIdentifier[] $destinationtNodeAggregateIdentifiers
     * @param PropertyName $propertyName
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet,
        NodeIdentifier $nodeIdentifier,
        array $destinationtNodeAggregateIdentifiers,
        PropertyName $propertyName
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePointSet = $dimensionSpacePointSet;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->destinationtNodeAggregateIdentifiers = $destinationtNodeAggregateIdentifiers;
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
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier[]
     */
    public function getDestinationtNodeAggregateIdentifiers(): array
    {
        return $this->destinationtNodeAggregateIdentifiers;
    }

    /**
     * @return PropertyName
     */
    public function getPropertyName(): PropertyName
    {
        return $this->propertyName;
    }


}

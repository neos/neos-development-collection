<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Command;

use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

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
     * CreateReferenceBetweenNodes constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param PropertyName $propertyName
     * @param array $destinationNodeAggregateIdentifiers
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $nodeIdentifier,
        PropertyName $propertyName,
        array $destinationNodeAggregateIdentifiers
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
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
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
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
    public function getPropertyName(): PropertyName
    {
        return $this->propertyName;
    }


}

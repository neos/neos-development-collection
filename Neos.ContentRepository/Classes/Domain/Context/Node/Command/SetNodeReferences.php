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
     * CreateReferenceBetweenNodes constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param PropertyName $propertyName
     * @param array $destinationtNodesAggregateIdentifiers
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $nodeIdentifier,
        PropertyName $propertyName,
        array $destinationtNodesAggregateIdentifiers
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
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
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier[]
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
}

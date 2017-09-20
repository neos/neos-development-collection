<?php

namespace Neos\ContentRepository\Domain\Context\Node\Event;

use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
use Neos\EventSourcing\Event\EventInterface;

/**
 * PropertyWasSet
 */
final class PropertyWasSet implements EventInterface
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var PropertyValue
     */
    private $value;

    /**
     *
     * @param NodeAggregateIdentifier $nodeIdentifier
     * @param string $propertyName
     * @param PropertyValue $value
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeIdentifier,
        $propertyName,
        PropertyValue $value
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->propertyName = $propertyName;
        $this->value = $value;
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
    public function getNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @return PropertyValue
     */
    public function getValue(): PropertyValue
    {
        return $this->value;
    }
}

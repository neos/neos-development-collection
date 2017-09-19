<?php

namespace Neos\ContentRepository\Domain\Context\Node\Event;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\EventSourcing\Event\EventInterface;
use Neos\ContentRepository\Domain\ValueObject\DimensionValues;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;

final class ChildNodeWithVariantWasCreated implements EventInterface
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $parentNodeIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * @var NodeTypeName
     */
    private $nodeTypeName;

    /**
     * @var DimensionValues
     */
    private $dimensionValues;

    /**
     * (property name => PropertyValue)
     *
     * @var array
     */
    private $propertyDefaultValuesAndTypes;

    /**
     * ChildNodeWithVariantWasCreated constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $parentNodeIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeName $nodeName
     * @param NodeTypeName $nodeTypeName
     * @param DimensionValues $dimensionValues
     * @param array $propertyDefaultValuesAndTypes
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $parentNodeIdentifier,
        NodeIdentifier $nodeIdentifier,
        NodeName $nodeName,
        NodeTypeName $nodeTypeName,
        DimensionValues $dimensionValues,
        array $propertyDefaultValuesAndTypes
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeName = $nodeName;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionValues = $dimensionValues;
        $this->propertyDefaultValuesAndTypes = $propertyDefaultValuesAndTypes;
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
    public function getParentNodeIdentifier(): NodeIdentifier
    {
        return $this->parentNodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return NodeName
     */
    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }

    /**
     * @return NodeTypeName
     */
    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    /**
     * @return DimensionValues
     */
    public function getDimensionValues(): DimensionValues
    {
        return $this->dimensionValues;
    }

    /**
     * @return array
     */
    public function getPropertyDefaultValuesAndTypes(): array
    {
        return $this->propertyDefaultValuesAndTypes;
    }
}

<?php

namespace Neos\ContentRepository\Domain\Context\Node\Event;

use Neos\EventSourcing\Event\EventInterface;
use Neos\ContentRepository\Domain\ValueObject\DimensionValues;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;

class ChildNodeWithVariantWasCreated implements EventInterface
{

    /**
     * @var NodeIdentifier
     */
    protected $parentNodeIdentifier;

    /**
     * @var NodeIdentifier
     */
    protected $nodeIdentifier;

    /**
     * @var NodeName
     */
    protected $nodeName;

    /**
     * @var NodeTypeName
     */
    protected $nodeTypeName;

    /**
     * @var DimensionValues
     */
    protected $dimensionValues;

    /**
     * (property name => PropertyValue)
     * @var array
     */
    protected $propertyDefaultValuesAndTypes;

    /**
     * ChildNodeWithVariantWasCreated constructor.
     *
     * @param NodeIdentifier $parentNodeIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeName $nodeName
     * @param NodeTypeName $nodeTypeName
     * @param DimensionValues $dimensionValues
     * @param $propertyDefaultValuesAndTypes
     */
    public function __construct(
        NodeIdentifier $parentNodeIdentifier,
        NodeIdentifier $nodeIdentifier,
        NodeName $nodeName,
        NodeTypeName $nodeTypeName,
        DimensionValues $dimensionValues,
        $propertyDefaultValuesAndTypes
    ) {
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeName = $nodeName;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionValues = $dimensionValues;
        $this->propertyDefaultValuesAndTypes = $propertyDefaultValuesAndTypes;
    }

}
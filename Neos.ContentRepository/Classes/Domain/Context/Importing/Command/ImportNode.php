<?php
namespace Neos\ContentRepository\Domain\Context\Importing\Command;

use Neos\ContentRepository\Domain\ValueObject\DimensionValues;
use Neos\ContentRepository\Domain\ValueObject\ImportingSessionIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValues;

final class ImportNode
{
    /**
     * @var ImportingSessionIdentifier
     */
    private $importingSessionIdentifier;

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
     * @var PropertyValues
     */
    private $propertyValues;

    public function __construct(
        ImportingSessionIdentifier $importingSessionIdentifier,
        NodeIdentifier $parentNodeIdentifier,
        NodeIdentifier $nodeIdentifier,
        NodeName $nodeName,
        NodeTypeName $nodeTypeName,
        DimensionValues $dimensionValues,
        PropertyValues $propertyValues
    ) {
        $this->importingSessionIdentifier = $importingSessionIdentifier;
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeName = $nodeName;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionValues = $dimensionValues;
        $this->propertyValues = $propertyValues;
    }

    public function getImportingSessionIdentifier(): ImportingSessionIdentifier
    {
        return $this->importingSessionIdentifier;
    }

    public function getParentNodeIdentifier(): NodeIdentifier
    {
        return $this->parentNodeIdentifier;
    }

    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }

    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    public function getDimensionValues(): DimensionValues
    {
        return $this->dimensionValues;
    }

    public function getPropertyValues(): PropertyValues
    {
        return $this->propertyValues;
    }

}

<?php
namespace Neos\ContentRepository\Domain\Context\Importing\Event;

use Neos\ContentRepository\Domain\ValueObject\DimensionValues;
use Neos\ContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\ImportingSessionIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcing\Event\EventInterface;

final class NodeWasImported implements EventInterface, CopyableAcrossContentStreamsInterface
{

    /**
     * @var ImportingSessionIdentifier
     */
    private $importingSessionIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $parentNodeIdentifier;

    /**
     * @var NodeAggregateIdentifier
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
        NodeAggregateIdentifier $parentNodeIdentifier,
        NodeAggregateIdentifier $nodeIdentifier,
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

    public function getParentNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->parentNodeIdentifier;
    }

    public function getNodeIdentifier(): NodeAggregateIdentifier
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

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        // nothing to copy here
    }
}

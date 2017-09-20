<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\DimensionValues;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;

final class CreateChildNodeWithVariant
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

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

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeIdentifier,
        NodeAggregateIdentifier $nodeIdentifier,
        NodeName $nodeName,
        NodeTypeName $nodeTypeName,
        DimensionValues $dimensionValues
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeName = $nodeName;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionValues = $dimensionValues;
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
    public function getParentNodeIdentifier(): NodeAggregateIdentifier
    {
        return $this->parentNodeIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeIdentifier(): NodeAggregateIdentifier
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


}

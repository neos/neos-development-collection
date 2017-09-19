<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\DimensionValues;
use Neos\ContentRepository\Domain\ValueObject\EditingSessionIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;

class CreateChildNodeWithVariant
{

    /**
     * @var EditingSessionIdentifier
     */
    protected $editingSessionIdentifier;

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

    public function __construct(
        EditingSessionIdentifier $editingSessionIdentifier,
        NodeIdentifier $parentNodeIdentifier,
        NodeIdentifier $nodeIdentifier,
        NodeName $nodeName,
        NodeTypeName $nodeTypeName,
        DimensionValues $dimensionValues
    ) {
        $this->editingSessionIdentifier = $editingSessionIdentifier;
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeName = $nodeName;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionValues = $dimensionValues;
    }
}

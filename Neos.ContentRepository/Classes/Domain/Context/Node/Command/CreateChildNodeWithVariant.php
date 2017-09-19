<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\DimensionValues;
use Neos\ContentRepository\Domain\ValueObject\EditingSession;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;

class CreateChildNodeWithVariant
{

    /**
     * @var EditingSession
     */
    protected $editingSession;

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
        EditingSession $editingSession,
        NodeIdentifier $parentNodeIdentifier,
        NodeIdentifier $nodeIdentifier,
        NodeName $nodeName,
        NodeTypeName $nodeTypeName,
        DimensionValues $dimensionValues
    ) {
        $this->editingSession = $editingSession;
        $this->parentNodeIdentifier = $parentNodeIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeName = $nodeName;
        $this->nodeTypeName = $nodeTypeName;
        $this->dimensionValues = $dimensionValues;
    }
}
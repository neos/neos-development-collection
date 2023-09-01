<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;

trait LoadNodeTypeTrait
{
    abstract protected function getNodeTypeManager(): NodeTypeManager;

    /**
     * loads and returns the node type or null if it does not exist
     */
    protected function loadNodeType(NodeTypeName $nodeTypeName): ?NodeType
    {
        try {
            return $this->getNodeTypeManager()->getNodeType($nodeTypeName);
        } catch (NodeTypeNotFoundException $e) {
            // the $nodeTypeName was not found; so we need to remove all nodes of this type.
            return null;
        }
    }
}

<?php

declare(strict_types=1);

namespace Neos\Neos\Utility;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * Utility trait for retrieving node types for nodes with a built-in fallback mechanism
 *
 * This is only a temporary drop-in replacement as the automatic fallback handling
 * of the NodeTypeManager was removed and Node::getNodeType was also removed.
 *
 * Its preferred to use the nullable {@see NodeTypeManager::getNodeType()} instead, and for cases where
 * the Fallback NodeType is really required use {@see NodeTypeNameFactory::forFallback()}.
 *
 * @property ContentRepositoryRegistry $contentRepositoryRegistry
 * @deprecated to ease migration from Neos 8.3
 */
trait NodeTypeWithFallbackProvider
{
    /**
     * @deprecated to ease migration from Neos 8.3
     */
    protected function getNodeType(Node $node): NodeType
    {
        $nodeTypeManager = $this->contentRepositoryRegistry->get($node->contentRepositoryId)->getNodeTypeManager();

        return $nodeTypeManager->getNodeType($node->nodeTypeName)
            ?? $nodeTypeManager->getNodeType(NodeTypeNameFactory::forFallback())
            ?? throw new NodeTypeNotFound(sprintf('Fallback NodeType not found while attempting to get NodeType "%s".', $node->nodeTypeName->value), 1710789992);
    }
}

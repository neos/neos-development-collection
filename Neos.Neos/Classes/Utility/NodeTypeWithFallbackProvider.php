<?php

declare(strict_types=1);

namespace Neos\Neos\Utility;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * Utility trait for retrieving node types for nodes with a built-in fallback mechanism
 *
 * @property ContentRepositoryRegistry $contentRepositoryRegistry
 */
trait NodeTypeWithFallbackProvider
{
    protected function getNodeType(Node $node): NodeType
    {
        $nodeTypeManager = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId)->getNodeTypeManager();

        return $nodeTypeManager->hasNodeType($node->nodeTypeName)
            ? $nodeTypeManager->getNodeType($node->nodeTypeName)
            : $nodeTypeManager->getNodeType(NodeTypeNameFactory::forFallback());
    }
}

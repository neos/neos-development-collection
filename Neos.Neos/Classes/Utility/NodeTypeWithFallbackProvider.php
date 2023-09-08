<?php

declare(strict_types=1);

namespace Neos\Neos\Utility;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\Utility\ContentRepositoryRegistryProvider;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * Utility trait for retrieving node types for nodes with a built-in fallback mechanism
 */
trait NodeTypeWithFallbackProvider
{
    use ContentRepositoryRegistryProvider;

    protected function getNodeType(Node $node): NodeType
    {
        $nodeTypeManager = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId)->getNodeTypeManager();

        return $nodeTypeManager->hasNodeType($node->nodeTypeName)
            ? $nodeTypeManager->getNodeType($node->nodeTypeName)
            : $nodeTypeManager->getNodeType(NodeTypeNameFactory::forFallback());
    }
}

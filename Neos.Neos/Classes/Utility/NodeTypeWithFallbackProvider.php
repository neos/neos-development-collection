<?php

namespace Neos\Neos\Utility;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * Utility trait for retrieving node types for nodes with a built-in fallback mechanism
 */
trait NodeTypeWithFallbackProvider
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    protected function getNodeType(Node $node): NodeType
    {
        $nodeTypeManager = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId)->getNodeTypeManager();

        try {
            return $nodeTypeManager->getNodeType($node->nodeTypeName);
        } catch (NodeTypeNotFoundException) {
            return $nodeTypeManager->getNodeType(NodeTypeNameFactory::forFallback());
        }
    }
}

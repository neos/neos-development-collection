<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;

final class NodeTreeTraversalHelper
{
    /**
     * the callback always gets the current NodeInterface passed as first parameter, and then its parent, and its parent etc etc.
     * Until it has reached the root, or the return value of the closure is FALSE.
     *
     * @param ContentSubgraphInterface $subgraph
     * @param NodeInterface $node
     * @param \Closure $callback
     */
    public static function traverseUpUntilCondition(ContentSubgraphInterface $subgraph, NodeInterface $node, \Closure $callback): void
    {
        do {
            $shouldContinueTraversal = $callback($node);
            $node = $subgraph->findParentNode($node->getNodeAggregateIdentifier());
        } while ($shouldContinueTraversal !== false && $node !== null);
    }

    public static function findNodeByNodePath(ContentSubgraphInterface $subgraph, NodeBasedReadModelInterface $node, NodePath $nodePath): ?NodeInterface
    {
        $nodeAggregateIdentifier = $node->getNodeAggregateIdentifier();
        if ($nodePath->isAbsolute()) {
            $nodeAggregateIdentifier = self::findRootNodeAggregateIdentifier($subgraph, $nodeAggregateIdentifier);
        }


        $childNodeAggregateIdentifier = self::findNodeAggregateIdentifierByPath($subgraph, $nodeAggregateIdentifier, $nodePath);
        if ($childNodeAggregateIdentifier !== null) {
            return $subgraph->findNodeByNodeAggregateIdentifier($childNodeAggregateIdentifier);
        }

        return null;
    }


    private static function findRootNodeAggregateIdentifier(ContentSubgraphInterface $subgraph, NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAggregateIdentifier
    {
        while (true) {
            $parentNode = $subgraph->findParentNode($nodeAggregateIdentifier);
            if ($parentNode === null) {
                // there is no parent, so the root node was the node before
                return $nodeAggregateIdentifier;
            } else {
                $nodeAggregateIdentifier = $parentNode->getNodeAggregateIdentifier();
            }
        }
    }

    private static function findNodeAggregateIdentifierByPath(ContentSubgraphInterface $subgraph, NodeAggregateIdentifier $nodeAggregateIdentifier, NodePath $nodePath): ?NodeAggregateIdentifier
    {
        foreach ($nodePath->getParts() as $nodeName) {
            $childNode = $subgraph->findChildNodeConnectedThroughEdgeName($nodeAggregateIdentifier, $nodeName);
            if ($childNode === null) {
                // we cannot find the child node, so there is no node on this path
                return null;
            }
            $nodeAggregateIdentifier = $childNode->getNodeAggregateIdentifier();
        }

        return $nodeAggregateIdentifier;
    }
}

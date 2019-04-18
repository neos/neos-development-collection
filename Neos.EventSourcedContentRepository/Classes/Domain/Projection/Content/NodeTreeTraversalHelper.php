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
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;


final class NodeTreeTraversalHelper
{

    public static function findNodeByNodePath(ContentSubgraphInterface $subgraph, TraversableNodeInterface $node, NodePath $nodePath): ?TraversableNodeInterface
    {
        $nodeAggregateIdentifier = $node->getNodeAggregateIdentifier();
        if ($nodePath->isAbsolute()) {
            $nodeAggregateIdentifier = self::findRootNodeAggregateIdentifier($subgraph, $nodeAggregateIdentifier);
        }


        $childNodeAggregateIdentifier = self::findNodeAggregateIdentifierByPath($subgraph, $nodeAggregateIdentifier, $nodePath);
        if ($childNodeAggregateIdentifier !== null) {
            $node = $subgraph->findNodeByNodeAggregateIdentifier($childNodeAggregateIdentifier);
            return new TraversableNode($node, $subgraph);
        }

        return null;
    }



    private static function findRootNodeAggregateIdentifier(ContentSubgraphInterface $subgraph, NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAggregateIdentifier
    {
        while(true) {
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

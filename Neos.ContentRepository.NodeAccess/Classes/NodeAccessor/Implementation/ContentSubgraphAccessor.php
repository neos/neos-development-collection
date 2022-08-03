<?php
declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\NodeAccessor\Implementation;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\NodeAccess\NodeAccessor\NodeAccessorInterface;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Content\References;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Content\Nodes;
use Neos\ContentRepository\Projection\Content\SearchTerm;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

final class ContentSubgraphAccessor implements NodeAccessorInterface
{
    public function __construct(
        private readonly ContentSubgraphInterface $subgraph,
        private readonly ContentGraphInterface $contentGraph
    ) {
    }

    public function findChildNodes(
        NodeInterface $parentNode,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes {
        return $this->subgraph->findChildNodes(
            $parentNode->getNodeAggregateIdentifier(),
            $nodeTypeConstraints,
            $limit,
            $offset
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->subgraph->getContentStreamIdentifier();
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->subgraph->getDimensionSpacePoint();
    }

    public function findByIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface
    {
        return $this->subgraph->findNodeByNodeAggregateIdentifier($nodeAggregateIdentifier);
    }

    public function findReferencedNodes(NodeInterface $node, PropertyName $name = null): References
    {
        return $this->subgraph->findReferencedNodes($node->getNodeAggregateIdentifier(), $name);
    }

    public function findReferencingNodes(NodeInterface $node, PropertyName $name = null): References
    {
        return $this->subgraph->findReferencingNodes($node->getNodeAggregateIdentifier(), $name);
    }

    public function findParentNode(NodeInterface $childNode): ?NodeInterface
    {
        return $this->subgraph->findParentNode($childNode->getNodeAggregateIdentifier());
    }

    public function findNodeByPath(NodePath $path, NodeInterface $startingNode): ?NodeInterface
    {
        return $this->subgraph->findNodeByPath($path, $startingNode->getNodeAggregateIdentifier());
    }

    public function findChildNodeConnectedThroughEdgeName(NodeInterface $parentNode, NodeName $edgeName): ?NodeInterface
    {
        return $this->subgraph->findChildNodeConnectedThroughEdgeName(
            $parentNode->getNodeAggregateIdentifier(),
            $edgeName
        );
    }

    public function findNodePath(NodeInterface $node): NodePath
    {
        return $this->subgraph->findNodePath($node->getNodeAggregateIdentifier());
    }

    public function findSubtrees(
        array $entryNodes,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface {
        $entryNodeAggregateIdentifiers = NodeAggregateIdentifiers::fromArray(array_map(function (NodeInterface $node) {
            return $node->getNodeAggregateIdentifier();
        }, $entryNodes));
        return $this->subgraph->findSubtrees($entryNodeAggregateIdentifiers, $maximumLevels, $nodeTypeConstraints);
    }

    public function findDescendants(
        array $entryNodes,
        NodeTypeConstraints $nodeTypeConstraints,
        ?SearchTerm $searchTerm
    ): Nodes {
        $entryNodeAggregateIdentifiers = array_map(function (NodeInterface $node) {
            return $node->getNodeAggregateIdentifier();
        }, $entryNodes);
        return $this->subgraph->findDescendants($entryNodeAggregateIdentifiers, $nodeTypeConstraints, $searchTerm);
    }

    public function findRootNodeByType(NodeTypeName $nodeTypeName): NodeInterface
    {
        $rootNodeAggregate = $this->contentGraph->findRootNodeAggregateByType(
            $this->getContentStreamIdentifier(),
            $nodeTypeName
        );

        $rootNode = $this->subgraph->findNodeByNodeAggregateIdentifier($rootNodeAggregate->getIdentifier());
        if (!$rootNode instanceof NodeInterface) {
            throw new \InvalidArgumentException(
                'Could not resolve root node of type "' . $nodeTypeName . '"',
                1651847484
            );
        }

        return $rootNode;
    }
}

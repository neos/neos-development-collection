<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\ContentAccess\Implementation;

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
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorInterface;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\Nodes;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\SearchTerm;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

final class ContentSubgraphAccessor implements NodeAccessorInterface
{
    private ContentSubgraphInterface $subgraph;

    public function __construct(ContentSubgraphInterface $subgraph)
    {
        $this->subgraph = $subgraph;
    }

    public function findChildNodes(NodeInterface $parentNode, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): Nodes
    {
        return $this->subgraph->findChildNodes($parentNode->getNodeAggregateIdentifier(), $nodeTypeConstraints, $limit, $offset);
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

    public function findReferencedNodes(NodeInterface $node, PropertyName $name = null): Nodes
    {
        return $this->subgraph->findReferencedNodes($node->getNodeAggregateIdentifier(), $name);
    }

    public function findReferencingNodes(NodeInterface $node, PropertyName $name = null): Nodes
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
        return $this->subgraph->findChildNodeConnectedThroughEdgeName($parentNode->getNodeAggregateIdentifier(), $edgeName);
    }

    public function findNodePath(NodeInterface $node): NodePath
    {
        return $this->subgraph->findNodePath($node->getNodeAggregateIdentifier());
    }

    public function findSubtrees(array $entryNodes, int $maximumLevels, NodeTypeConstraints $nodeTypeConstraints): SubtreeInterface
    {
        $entryNodeAggregateIdentifiers = array_map(function (NodeInterface $node) {
            return $node->getNodeAggregateIdentifier();
        }, $entryNodes);
        return $this->subgraph->findSubtrees($entryNodeAggregateIdentifiers, $maximumLevels, $nodeTypeConstraints);
    }

    public function findDescendants(array $entryNodes, NodeTypeConstraints $nodeTypeConstraints, ?SearchTerm $searchTerm): Nodes
    {
        $entryNodeAggregateIdentifiers = array_map(function (NodeInterface $node) {
            return $node->getNodeAggregateIdentifier();
        }, $entryNodes);
        return $this->subgraph->findDescendants($entryNodeAggregateIdentifiers, $nodeTypeConstraints, $searchTerm);
    }
}

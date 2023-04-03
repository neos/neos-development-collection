<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphWithRuntimeCaches;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * Wrapper for a concrete implementation of the {@see ContentSubgraphInterface} that
 * builds up an in memory cache while fetching nodes in order to speed up successive calls
 *
 * @internal the parent {@see ContentSubgraphInterface} is API
 */
final class ContentSubgraphWithRuntimeCaches implements ContentSubgraphInterface
{
    public readonly InMemoryCache $inMemoryCache;

    public function __construct(
        private readonly ContentSubgraphInterface $wrappedContentSubgraph,
    ) {
        $this->inMemoryCache = new InMemoryCache();
    }

    public function findChildNodes(NodeAggregateId $parentNodeAggregateId, FindChildNodesFilter $filter): Nodes
    {
        if ($filter->pagination !== null || $filter->propertyValue !== null || $filter->ordering !== null) {
            return $this->wrappedContentSubgraph->findChildNodes($parentNodeAggregateId, $filter);
        }
        $childNodesCache = $this->inMemoryCache->getAllChildNodesByNodeIdCache();
        $namedChildNodeCache = $this->inMemoryCache->getNamedChildNodeByNodeIdCache();
        $parentNodeIdCache = $this->inMemoryCache->getParentNodeIdByChildNodeIdCache();
        $nodeByIdCache = $this->inMemoryCache->getNodeByNodeAggregateIdCache();
        if ($childNodesCache->contains($parentNodeAggregateId, $filter->nodeTypeConstraints)) {
            return $childNodesCache->findChildNodes($parentNodeAggregateId, $filter->nodeTypeConstraints);
        }
        $childNodes = $this->wrappedContentSubgraph->findChildNodes($parentNodeAggregateId, $filter);
        foreach ($childNodes as $node) {
            $namedChildNodeCache->add($parentNodeAggregateId, $node->nodeName, $node);
            $parentNodeIdCache->add($node->nodeAggregateId, $parentNodeAggregateId);
            $nodeByIdCache->add($node->nodeAggregateId, $node);
        }
        $childNodesCache->add($parentNodeAggregateId, $filter->nodeTypeConstraints, $childNodes);
        return $childNodes;
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, CountChildNodesFilter $filter): int
    {
        if ($filter->propertyValue !== null) {
            return $this->wrappedContentSubgraph->countChildNodes($parentNodeAggregateId, $filter);
        }
        $childNodesCache = $this->inMemoryCache->getAllChildNodesByNodeIdCache();
        if ($childNodesCache->contains($parentNodeAggregateId, $filter->nodeTypeConstraints)) {
            return $childNodesCache->countChildNodes($parentNodeAggregateId, $filter->nodeTypeConstraints);
        }
        return $this->wrappedContentSubgraph->countChildNodes($parentNodeAggregateId, $filter);
    }

    public function findReferences(NodeAggregateId $nodeAggregateId, FindReferencesFilter $filter): References
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findReferences($nodeAggregateId, $filter);
    }

    public function countReferences(NodeAggregateId $nodeAggregateId, CountReferencesFilter $filter): int
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->countReferences($nodeAggregateId, $filter);
    }

    public function findBackReferences(NodeAggregateId $nodeAggregateId, FindBackReferencesFilter $filter): References
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findBackReferences($nodeAggregateId, $filter);
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, CountBackReferencesFilter $filter): int
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->countBackReferences($nodeAggregateId, $filter);
    }

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $cache = $this->inMemoryCache->getNodeByNodeAggregateIdCache();

        if ($cache->knowsAbout($nodeAggregateId)) {
            return $cache->get($nodeAggregateId);
        }

        $node = $this->wrappedContentSubgraph->findNodeById($nodeAggregateId);
        if ($node === null) {
            $cache->rememberNonExistingNodeAggregateId($nodeAggregateId);
        } else {
            $cache->add($nodeAggregateId, $node);
        }
        return $node;
    }

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        $parentNodeIdCache = $this->inMemoryCache->getParentNodeIdByChildNodeIdCache();
        if ($parentNodeIdCache->knowsAbout($childNodeAggregateId)) {
            $possibleParentId = $parentNodeIdCache->get($childNodeAggregateId);
            if ($possibleParentId === null) {
                return null;
            }
            // we here trigger findNodeById,
            // as this might retrieve the Parent Node from the in-memory cache if it has been loaded before
            return $this->findNodeById($possibleParentId);
        }
        $parentNode = $this->wrappedContentSubgraph->findParentNode($childNodeAggregateId);
        if ($parentNode === null) {
            $parentNodeIdCache->rememberNonExistingParentNode($childNodeAggregateId);
            return null;
        }
        $parentNodeIdCache->add($childNodeAggregateId, $parentNode->nodeAggregateId);
        // we also add the parent node to the NodeAggregateId => Node cache;
        // as this might improve cache hit rates as well.
        $this->inMemoryCache->getNodeByNodeAggregateIdCache()->add($parentNode->nodeAggregateId, $parentNode);
        return $parentNode;
    }

    public function findNodeByPath(NodePath $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        // TODO implement runtime caches
        return $this->wrappedContentSubgraph->findNodeByPath($path, $startingNodeAggregateId);
    }

    public function findChildNodeConnectedThroughEdgeName(NodeAggregateId $parentNodeAggregateId, NodeName $edgeName): ?Node
    {
        $namedChildNodeCache = $this->inMemoryCache->getNamedChildNodeByNodeIdCache();
        if ($namedChildNodeCache->contains($parentNodeAggregateId, $edgeName)) {
            return $namedChildNodeCache->get($parentNodeAggregateId, $edgeName);
        }
        $node = $this->wrappedContentSubgraph->findChildNodeConnectedThroughEdgeName($parentNodeAggregateId, $edgeName);
        if ($node === null) {
            return null;
        }
        $namedChildNodeCache->add($parentNodeAggregateId, $edgeName, $node);
        $this->inMemoryCache->getNodeByNodeAggregateIdCache()->add($node->nodeAggregateId, $node);
        return $node;
    }

    public function findSucceedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, FindSucceedingSiblingNodesFilter $filter): Nodes
    {
        // TODO implement runtime caches
        return $this->wrappedContentSubgraph->findSucceedingSiblingNodes($siblingNodeAggregateId, $filter);
    }

    public function findPrecedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, FindPrecedingSiblingNodesFilter $filter): Nodes
    {
        // TODO implement runtime caches
        return $this->wrappedContentSubgraph->findPrecedingSiblingNodes($siblingNodeAggregateId, $filter);
    }

    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): NodePath
    {
        $nodePathCache = $this->inMemoryCache->getNodePathCache();
        $cachedNodePath = $nodePathCache->get($nodeAggregateId);
        if ($cachedNodePath !== null) {
            return $cachedNodePath;
        }
        $nodePath = $this->wrappedContentSubgraph->retrieveNodePath($nodeAggregateId);
        $nodePathCache->add($nodeAggregateId, $nodePath);
        return $nodePath;
    }

    public function findSubtree(NodeAggregateId $entryNodeAggregateId, FindSubtreeFilter $filter): ?Subtree
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findSubtree($entryNodeAggregateId, $filter);
        // TODO populate NodeByNodeAggregateIdCache and ParentNodeIdByChildNodeIdCache from result
    }

    public function findDescendantNodes(NodeAggregateId $entryNodeAggregateId, FindDescendantNodesFilter $filter): Nodes
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findDescendantNodes($entryNodeAggregateId, $filter);
    }

    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, Filter\CountDescendantNodesFilter $filter): int
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->countDescendantNodes($entryNodeAggregateId, $filter);
    }

    public function countNodes(): int
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->countNodes();
    }

    public function jsonSerialize(): mixed
    {
        return $this->wrappedContentSubgraph->jsonSerialize();
    }
}

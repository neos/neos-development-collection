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

namespace Neos\ContentRepositoryRegistry\SubgraphCachingInMemory;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

/**
 * Wrapper for a concrete implementation of the {@see ContentSubgraphInterface} that
 * builds up an in memory cache while fetching nodes in order to speed up successive calls.
 *
 * It is a rather pragmatic way to speed up (uncached) rendering.
 *
 * @internal implementation detail of {@see ContentRepositoryRegistry::subgraphForNode()}
 */
final readonly class ContentSubgraphWithRuntimeCaches implements ContentSubgraphInterface
{
    public function __construct(
        private ContentSubgraphInterface $wrappedContentSubgraph,
        private SubgraphCachePool $subgraphCachePool
    ) {
    }

    public function getContentRepositoryId(): ContentRepositoryId
    {
        return $this->wrappedContentSubgraph->getContentRepositoryId();
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->wrappedContentSubgraph->getWorkspaceName();
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->wrappedContentSubgraph->getDimensionSpacePoint();
    }

    public function getVisibilityConstraints(): VisibilityConstraints
    {
        return $this->wrappedContentSubgraph->getVisibilityConstraints();
    }

    public function findChildNodes(NodeAggregateId $parentNodeAggregateId, FindChildNodesFilter $filter): Nodes
    {
        if (!self::isFilterEmpty($filter)) {
            return $this->wrappedContentSubgraph->findChildNodes($parentNodeAggregateId, $filter);
        }
        $childNodesCache = $this->subgraphCachePool->getAllChildNodesByNodeIdCache($this);
        $namedChildNodeCache = $this->subgraphCachePool->getNamedChildNodeByNodeIdCache($this);
        $parentNodeIdCache = $this->subgraphCachePool->getParentNodeIdByChildNodeIdCache($this);
        $nodeByIdCache = $this->subgraphCachePool->getNodeByNodeAggregateIdCache($this);
        if ($childNodesCache->contains($parentNodeAggregateId, $filter->nodeTypes)) {
            return $childNodesCache->findChildNodes($parentNodeAggregateId, $filter->nodeTypes);
        }
        $childNodes = $this->wrappedContentSubgraph->findChildNodes($parentNodeAggregateId, $filter);
        foreach ($childNodes as $node) {
            $namedChildNodeCache->add($parentNodeAggregateId, $node->name, $node);
            $parentNodeIdCache->add($node->aggregateId, $parentNodeAggregateId);
            $nodeByIdCache->add($node->aggregateId, $node);
        }
        $childNodesCache->add($parentNodeAggregateId, $filter->nodeTypes, $childNodes);
        return $childNodes;
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, CountChildNodesFilter $filter): int
    {
        if (!self::isFilterEmpty($filter)) {
            return $this->wrappedContentSubgraph->countChildNodes($parentNodeAggregateId, $filter);
        }
        $childNodesCache = $this->subgraphCachePool->getAllChildNodesByNodeIdCache($this);
        if ($childNodesCache->contains($parentNodeAggregateId, $filter->nodeTypes)) {
            return $childNodesCache->countChildNodes($parentNodeAggregateId, $filter->nodeTypes);
        }
        return $this->wrappedContentSubgraph->countChildNodes($parentNodeAggregateId, $filter);
    }

    public function findReferences(NodeAggregateId $nodeAggregateId, FindReferencesFilter $filter): References
    {
        return $this->wrappedContentSubgraph->findReferences($nodeAggregateId, $filter);
    }

    public function countReferences(NodeAggregateId $nodeAggregateId, CountReferencesFilter $filter): int
    {
        return $this->wrappedContentSubgraph->countReferences($nodeAggregateId, $filter);
    }

    public function findBackReferences(NodeAggregateId $nodeAggregateId, FindBackReferencesFilter $filter): References
    {
        return $this->wrappedContentSubgraph->findBackReferences($nodeAggregateId, $filter);
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, CountBackReferencesFilter $filter): int
    {
        return $this->wrappedContentSubgraph->countBackReferences($nodeAggregateId, $filter);
    }

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $cache = $this->subgraphCachePool->getNodeByNodeAggregateIdCache($this);

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

    public function findRootNodeByType(NodeTypeName $nodeTypeName): ?Node
    {
        return $this->wrappedContentSubgraph->findRootNodeByType($nodeTypeName);
    }

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        $parentNodeIdCache = $this->subgraphCachePool->getParentNodeIdByChildNodeIdCache($this);
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
        $parentNodeIdCache->add($childNodeAggregateId, $parentNode->aggregateId);
        // we also add the parent node to the NodeAggregateId => Node cache;
        // as this might improve cache hit rates as well.
        $this->subgraphCachePool->getNodeByNodeAggregateIdCache($this)->add($parentNode->aggregateId, $parentNode);
        return $parentNode;
    }

    public function findNodeByPath(NodePath|NodeName $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        return $this->wrappedContentSubgraph->findNodeByPath($path, $startingNodeAggregateId);
    }

    public function findNodeByAbsolutePath(AbsoluteNodePath $path): ?Node
    {
        return $this->wrappedContentSubgraph->findNodeByAbsolutePath($path);
    }

    public function findSucceedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, FindSucceedingSiblingNodesFilter $filter): Nodes
    {
        return $this->wrappedContentSubgraph->findSucceedingSiblingNodes($siblingNodeAggregateId, $filter);
    }

    public function findPrecedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, FindPrecedingSiblingNodesFilter $filter): Nodes
    {
        return $this->wrappedContentSubgraph->findPrecedingSiblingNodes($siblingNodeAggregateId, $filter);
    }

    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): AbsoluteNodePath
    {
        $nodePathCache = $this->subgraphCachePool->getNodePathCache($this);
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
        return $this->wrappedContentSubgraph->findSubtree($entryNodeAggregateId, $filter);
    }

    public function findAncestorNodes(NodeAggregateId $entryNodeAggregateId, Filter\FindAncestorNodesFilter $filter): Nodes
    {
        return $this->wrappedContentSubgraph->findAncestorNodes($entryNodeAggregateId, $filter);
    }

    public function countAncestorNodes(NodeAggregateId $entryNodeAggregateId, Filter\CountAncestorNodesFilter $filter): int
    {
        return $this->wrappedContentSubgraph->countAncestorNodes($entryNodeAggregateId, $filter);
    }

    public function findClosestNode(NodeAggregateId $entryNodeAggregateId, Filter\FindClosestNodeFilter $filter): ?Node
    {
        return $this->wrappedContentSubgraph->findClosestNode($entryNodeAggregateId, $filter);
    }

    public function findDescendantNodes(NodeAggregateId $entryNodeAggregateId, FindDescendantNodesFilter $filter): Nodes
    {
        return $this->wrappedContentSubgraph->findDescendantNodes($entryNodeAggregateId, $filter);
    }

    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, Filter\CountDescendantNodesFilter $filter): int
    {
        return $this->wrappedContentSubgraph->countDescendantNodes($entryNodeAggregateId, $filter);
    }

    public function countNodes(): int
    {
        return $this->wrappedContentSubgraph->countNodes();
    }

    private static function isFilterEmpty(object $filter): bool
    {
        return array_filter(get_object_vars($filter), static fn ($value) => $value !== null) === [];
    }
}

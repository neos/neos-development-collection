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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter;
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

/**
 * Wrapper for a concrete implementation of the {@see ContentSubgraphInterface} that
 * builds up an in memory cache while fetching nodes in order to speed up successive calls
 *
 * @internal the parent {@see ContentSubgraphInterface} is API
 */
final readonly class ContentSubgraphWithRuntimeCaches implements ContentSubgraphInterface
{
    public InMemoryCache $inMemoryCache;

    public function __construct(
        private ContentSubgraphInterface $wrappedContentSubgraph,
    ) {
        $this->inMemoryCache = new InMemoryCache();
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

    public function findChildNodes(NodeAggregateId $parentNodeAggregateId, ?Filter\FindChildNodesFilter $filter = null): Nodes
    {
        if (!self::isFilterEmpty($filter)) {
            return $this->wrappedContentSubgraph->findChildNodes($parentNodeAggregateId, $filter);
        }
        $childNodesCache = $this->inMemoryCache->getAllChildNodesByNodeIdCache();
        $namedChildNodeCache = $this->inMemoryCache->getNamedChildNodeByNodeIdCache();
        $parentNodeIdCache = $this->inMemoryCache->getParentNodeIdByChildNodeIdCache();
        $nodeByIdCache = $this->inMemoryCache->getNodeByNodeAggregateIdCache();
        if ($childNodesCache->contains($parentNodeAggregateId, $filter->nodeTypes)) {
            return $childNodesCache->findChildNodes($parentNodeAggregateId, $filter->nodeTypes);
        }
        $childNodes = $this->wrappedContentSubgraph->findChildNodes($parentNodeAggregateId, $filter);
        foreach ($childNodes as $node) {
            $namedChildNodeCache->add($parentNodeAggregateId, $node->nodeName, $node);
            $parentNodeIdCache->add($node->nodeAggregateId, $parentNodeAggregateId);
            $nodeByIdCache->add($node->nodeAggregateId, $node);
        }
        $childNodesCache->add($parentNodeAggregateId, $filter->nodeTypes, $childNodes);
        return $childNodes;
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, ?Filter\CountChildNodesFilter $filter = null): int
    {
        if (!self::isFilterEmpty($filter)) {
            return $this->wrappedContentSubgraph->countChildNodes($parentNodeAggregateId, $filter);
        }
        $childNodesCache = $this->inMemoryCache->getAllChildNodesByNodeIdCache();
        if ($childNodesCache->contains($parentNodeAggregateId, $filter->nodeTypes)) {
            return $childNodesCache->countChildNodes($parentNodeAggregateId, $filter->nodeTypes);
        }
        return $this->wrappedContentSubgraph->countChildNodes($parentNodeAggregateId, $filter);
    }

    public function findReferences(NodeAggregateId $nodeAggregateId, ?Filter\FindReferencesFilter $filter = null): References
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findReferences($nodeAggregateId, $filter);
    }

    public function countReferences(NodeAggregateId $nodeAggregateId, ?Filter\CountReferencesFilter $filter = null): int
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->countReferences($nodeAggregateId, $filter);
    }

    public function findBackReferences(NodeAggregateId $nodeAggregateId, ?Filter\FindBackReferencesFilter $filter = null): References
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findBackReferences($nodeAggregateId, $filter);
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, ?Filter\CountBackReferencesFilter $filter = null): int
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

    public function findRootNodeByType(NodeTypeName $nodeTypeName): ?Node
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findRootNodeByType($nodeTypeName);
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

    public function findNodeByPath(NodePath|NodeName $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findNodeByPath($path, $startingNodeAggregateId);
    }

    public function findNodeByAbsolutePath(AbsoluteNodePath $path): ?Node
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findNodeByAbsolutePath($path);
    }

    public function findSucceedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, ?Filter\FindSucceedingSiblingNodesFilter $filter = null): Nodes
    {
        // TODO implement runtime caches
        return $this->wrappedContentSubgraph->findSucceedingSiblingNodes($siblingNodeAggregateId, $filter);
    }

    public function findPrecedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, ?Filter\FindPrecedingSiblingNodesFilter $filter = null): Nodes
    {
        // TODO implement runtime caches
        return $this->wrappedContentSubgraph->findPrecedingSiblingNodes($siblingNodeAggregateId, $filter);
    }

    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): AbsoluteNodePath
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

    public function findSubtree(NodeAggregateId $entryNodeAggregateId, ?Filter\FindSubtreeFilter $filter = null): ?Subtree
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findSubtree($entryNodeAggregateId, $filter);
        // TODO populate NodeByNodeAggregateIdCache and ParentNodeIdByChildNodeIdCache from result
    }

    public function findAncestorNodes(NodeAggregateId $entryNodeAggregateId, ?Filter\FindAncestorNodesFilter $filter = null): Nodes
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findAncestorNodes($entryNodeAggregateId, $filter);
    }

    public function countAncestorNodes(NodeAggregateId $entryNodeAggregateId, ?Filter\CountAncestorNodesFilter $filter = null): int
    {
        // TODO: Implement countAncestorNodes() method.
        return $this->wrappedContentSubgraph->countAncestorNodes($entryNodeAggregateId, $filter);
    }

    public function findClosestNode(NodeAggregateId $entryNodeAggregateId, ?Filter\FindClosestNodeFilter $filter = null): ?Node
    {
        // TODO: Implement findClosestNode() method.
        return $this->wrappedContentSubgraph->findClosestNode($entryNodeAggregateId, $filter);
    }

    public function findDescendantNodes(NodeAggregateId $entryNodeAggregateId, ?Filter\FindDescendantNodesFilter $filter = null): Nodes
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->findDescendantNodes($entryNodeAggregateId, $filter);
    }

    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, ?Filter\CountDescendantNodesFilter $filter = null): int
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->countDescendantNodes($entryNodeAggregateId, $filter);
    }

    public function countNodes(): int
    {
        // TODO: implement runtime caches
        return $this->wrappedContentSubgraph->countNodes();
    }

    /**
     * @phpstan-assert-if-true !null $filter
     */
    private static function isFilterEmpty(?object $filter): bool
    {
        if ($filter === null) {
            return true;
        }
        return array_filter(get_object_vars($filter), static fn ($value) => $value !== null) === [];
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->wrappedContentSubgraph->jsonSerialize();
    }
}

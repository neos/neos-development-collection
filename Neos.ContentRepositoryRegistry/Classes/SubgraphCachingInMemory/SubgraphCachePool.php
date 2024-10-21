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

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\InMemoryCache\AllChildNodesByNodeIdCache;
use Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\InMemoryCache\NamedChildNodeByNodeIdCache;
use Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\InMemoryCache\NodeByNodeAggregateIdCache;
use Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\InMemoryCache\NodePathCache;
use Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\InMemoryCache\ParentNodeIdByChildNodeIdCache;

/**
 * Accessors to in Memory Cache
 *
 * Detail for runtime performance improvement of the different implementations
 * of {@see ContentSubgraphWithRuntimeCaches}. You never need this externally.
 *
 * All cache accessors have a {@see ContentSubgraphInterface} passed in; and the identity of
 * this content subgraph (ContentRepositoryId, WorkspaceName, DimensionSpacePoint, VisibilityConstraints)
 * will be used to find the right cache.
 *
 * @internal
 */
#[Flow\Scope("singleton")]
final class SubgraphCachePool
{
    /**
     * @var array<string,NodePathCache>
     */
    private $nodePathCaches;

    /**
     * @var array<string,NodeByNodeAggregateIdCache>
     */
    private $nodeByNodeAggregateIdCaches;

    /**
     * @var array<string,AllChildNodesByNodeIdCache>
     */
    private $allChildNodesByNodeIdCaches;

    /**
     * @var array<string,NamedChildNodeByNodeIdCache>
     */
    private $namedChildNodeByNodeIdCaches;

    /**
     * @var array<string,ParentNodeIdByChildNodeIdCache>
     */
    private $parentNodeIdByChildNodeIdCaches;

    public function __construct()
    {
        $this->reset();
    }

    private static function cacheId(ContentSubgraphInterface $subgraph): string
    {
        return $subgraph->getContentRepositoryId()->value . '#' .
            $subgraph->getWorkspaceName()->value . '#' .
            $subgraph->getDimensionSpacePoint()->hash . '#' .
            $subgraph->getVisibilityConstraints()->getHash();
    }

    /**
     * @return NodePathCache
     */
    public function getNodePathCache(ContentSubgraphInterface $subgraph): NodePathCache
    {
        return $this->nodePathCaches[self::cacheId($subgraph)] ??= new NodePathCache();
    }

    public function getNodeByNodeAggregateIdCache(ContentSubgraphInterface $subgraph): NodeByNodeAggregateIdCache
    {
        return $this->nodeByNodeAggregateIdCaches[self::cacheId($subgraph)] ??= new NodeByNodeAggregateIdCache();
    }

    /**
     * @return AllChildNodesByNodeIdCache
     */
    public function getAllChildNodesByNodeIdCache(ContentSubgraphInterface $subgraph): AllChildNodesByNodeIdCache
    {
        return $this->allChildNodesByNodeIdCaches[self::cacheId($subgraph)] ??= new AllChildNodesByNodeIdCache();
    }

    /**
     * @return NamedChildNodeByNodeIdCache
     */
    public function getNamedChildNodeByNodeIdCache(ContentSubgraphInterface $subgraph): NamedChildNodeByNodeIdCache
    {
        return $this->namedChildNodeByNodeIdCaches[self::cacheId($subgraph)] ??= new NamedChildNodeByNodeIdCache();
    }

    /**
     * @return ParentNodeIdByChildNodeIdCache
     */
    public function getParentNodeIdByChildNodeIdCache(ContentSubgraphInterface $subgraph): ParentNodeIdByChildNodeIdCache
    {
        return $this->parentNodeIdByChildNodeIdCaches[self::cacheId($subgraph)] ??= new ParentNodeIdByChildNodeIdCache();
    }

    public function reset(): void
    {
        $this->nodePathCaches = [];
        $this->nodeByNodeAggregateIdCaches = [];
        $this->allChildNodesByNodeIdCaches = [];
        $this->namedChildNodeByNodeIdCaches = [];
        $this->parentNodeIdByChildNodeIdCaches = [];
    }
}

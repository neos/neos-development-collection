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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
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
    private array $nodePathCaches = [];

    /**
     * @var array<string,NodeByNodeAggregateIdCache>
     */
    private array $nodeByNodeAggregateIdCaches = [];

    /**
     * @var array<string,AllChildNodesByNodeIdCache>
     */
    private array $allChildNodesByNodeIdCaches = [];

    /**
     * @var array<string,NamedChildNodeByNodeIdCache>
     */
    private array $namedChildNodeByNodeIdCaches = [];

    /**
     * @var array<string,ParentNodeIdByChildNodeIdCache>
     */
    private array $parentNodeIdByChildNodeIdCaches = [];

    /**
     * @var array<string,ContentSubgraphInterface>
     */
    private array $subgraphInstancesCache = [];

    private bool $isDisabled = false;

    public function getNodePathCache(ContentSubgraphInterface $subgraph): NodePathCache
    {
        if ($this->isDisabled) {
            // return empty cache which will be used as noop as we don't store the added cache values
            return new NodePathCache();
        }
        return $this->nodePathCaches[self::cacheId($subgraph)] ??= new NodePathCache();
    }

    public function getNodeByNodeAggregateIdCache(ContentSubgraphInterface $subgraph): NodeByNodeAggregateIdCache
    {
        if ($this->isDisabled) {
            // return empty cache which will be used as noop as we don't store the added cache values
            return new NodeByNodeAggregateIdCache();
        }
        return $this->nodeByNodeAggregateIdCaches[self::cacheId($subgraph)] ??= new NodeByNodeAggregateIdCache();
    }

    public function getAllChildNodesByNodeIdCache(ContentSubgraphInterface $subgraph): AllChildNodesByNodeIdCache
    {
        if ($this->isDisabled) {
            // return empty cache which will be used as noop as we don't store the added cache values
            return new AllChildNodesByNodeIdCache();
        }
        return $this->allChildNodesByNodeIdCaches[self::cacheId($subgraph)] ??= new AllChildNodesByNodeIdCache();
    }

    public function getNamedChildNodeByNodeIdCache(ContentSubgraphInterface $subgraph): NamedChildNodeByNodeIdCache
    {
        if ($this->isDisabled) {
            // return empty cache which will be used as noop as we don't store the added cache values
            return new NamedChildNodeByNodeIdCache();
        }
        return $this->namedChildNodeByNodeIdCaches[self::cacheId($subgraph)] ??= new NamedChildNodeByNodeIdCache();
    }

    public function getParentNodeIdByChildNodeIdCache(ContentSubgraphInterface $subgraph): ParentNodeIdByChildNodeIdCache
    {
        if ($this->isDisabled) {
            // return empty cache which will be used as noop as we don't store the added cache values
            return new ParentNodeIdByChildNodeIdCache();
        }
        return $this->parentNodeIdByChildNodeIdCaches[self::cacheId($subgraph)] ??= new ParentNodeIdByChildNodeIdCache();
    }

    /**
     * Fetching a content graph requires a sql query. This is why we cache the actual subgraph instances here as well,
     * to avoid having each time fetched the content graph again.
     */
    public function getContentSubgraph(ContentRepository $contentRepository, WorkspaceName $workspaceName, DimensionSpacePoint $dimensionSpacePoint, VisibilityConstraints $visibilityConstraints): ContentSubgraphInterface
    {
        if ($this->isDisabled) {
            // do not return decorated subgraphs, so the above cache methods are unlikely to be called
            return $contentRepository->getContentGraph($workspaceName)->getSubgraph($dimensionSpacePoint, $visibilityConstraints);
        }
        $cacheId = self::cacheIdForArguments($contentRepository->id, $workspaceName, $dimensionSpacePoint, $visibilityConstraints);
        $uncachedContentSubgraphInstance = $this->subgraphInstancesCache[$cacheId] ??= $contentRepository->getContentGraph($workspaceName)->getSubgraph(
            $dimensionSpacePoint,
            $visibilityConstraints
        );

        return ContentSubgraphWithRuntimeCaches::decorate($uncachedContentSubgraphInstance, $this);
    }

    private static function cacheId(ContentSubgraphInterface $subgraph): string
    {
        return self::cacheIdForArguments($subgraph->getContentRepositoryId(), $subgraph->getWorkspaceName(), $subgraph->getDimensionSpacePoint(), $subgraph->getVisibilityConstraints());
    }

    private static function cacheIdForArguments(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, DimensionSpacePoint $dimensionSpacePoint, VisibilityConstraints $visibilityConstraints): string
    {
        return $contentRepositoryId->value . '#' .
            $workspaceName->value . '#' .
            $dimensionSpacePoint->hash . '#' .
            $visibilityConstraints->getHash();
    }

    public function reset(bool $disable): void
    {
        $this->isDisabled = $disable;
        $this->nodePathCaches = [];
        $this->nodeByNodeAggregateIdCaches = [];
        $this->allChildNodesByNodeIdCaches = [];
        $this->namedChildNodeByNodeIdCaches = [];
        $this->parentNodeIdByChildNodeIdCaches = [];
        $this->subgraphInstancesCache = [];
    }
}

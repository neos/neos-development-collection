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

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\InMemoryCache\AllChildNodesByNodeIdCache;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\InMemoryCache\NamedChildNodeByNodeIdCache;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\InMemoryCache\NodeByNodeAggregateIdCache;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\InMemoryCache\NodePathCache;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\InMemoryCache\ParentNodeIdByChildNodeIdCache;

/**
 * Accessors to in Memory Cache
 *
 * Detail for runtime performance improvement of the different implementations
 * of {@see ContentSubgraphInterface}. You never need this externally.
 *
 * @internal
 */
final class InMemoryCache
{
    /**
     * @var NodePathCache
     */
    private $nodePathCache;

    /**
     * @var NodeByNodeAggregateIdCache
     */
    private $nodeByNodeAggregateIdCache;

    /**
     * @var AllChildNodesByNodeIdCache
     */
    private $allChildNodesByNodeIdCache;

    /**
     * @var NamedChildNodeByNodeIdCache
     */
    private $namedChildNodeByNodeIdCache;

    /**
     * @var ParentNodeIdByChildNodeIdCache
     */
    private $parentNodeIdByChildNodeIdCache;

    public function __construct()
    {
        // we start with an enabled cache.
        $this->enable();
    }

    /**
     * Enable all caches. All READ requests should enable the cache.
     */
    public function enable(): void
    {
        $this->reset(true);
    }

    /**
     * Disable all caches. All WRITE requests should work with disabled cache.
     */
    public function disable(): void
    {
        $this->reset(false);
    }

    /**
     * @return NodePathCache
     */
    public function getNodePathCache(): NodePathCache
    {
        return $this->nodePathCache;
    }

    public function getNodeByNodeAggregateIdCache(): NodeByNodeAggregateIdCache
    {
        return $this->nodeByNodeAggregateIdCache;
    }

    /**
     * @return AllChildNodesByNodeIdCache
     */
    public function getAllChildNodesByNodeIdCache(): AllChildNodesByNodeIdCache
    {
        return $this->allChildNodesByNodeIdCache;
    }

    /**
     * @return NamedChildNodeByNodeIdCache
     */
    public function getNamedChildNodeByNodeIdCache(): NamedChildNodeByNodeIdCache
    {
        return $this->namedChildNodeByNodeIdCache;
    }

    /**
     * @return ParentNodeIdByChildNodeIdCache
     */
    public function getParentNodeIdByChildNodeIdCache(): ParentNodeIdByChildNodeIdCache
    {
        return $this->parentNodeIdByChildNodeIdCache;
    }

    /**
     * @param bool $isEnabled if TRUE, the caches work; if FALSE, they do not store anything.
     */
    private function reset(bool $isEnabled): void
    {
        $this->nodePathCache = new NodePathCache($isEnabled);
        $this->nodeByNodeAggregateIdCache = new NodeByNodeAggregateIdCache($isEnabled);
        $this->allChildNodesByNodeIdCache = new AllChildNodesByNodeIdCache($isEnabled);
        $this->namedChildNodeByNodeIdCache = new NamedChildNodeByNodeIdCache($isEnabled);
        $this->parentNodeIdByChildNodeIdCache
            = new ParentNodeIdByChildNodeIdCache($isEnabled);
    }
}

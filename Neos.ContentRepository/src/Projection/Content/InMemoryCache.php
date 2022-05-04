<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\Content\InMemoryCache\AllChildNodesByNodeIdentifierCache;
use Neos\ContentRepository\Projection\Content\InMemoryCache\NamedChildNodeByNodeIdentifierCache;
use Neos\ContentRepository\Projection\Content\InMemoryCache\NodeByNodeAggregateIdentifierCache;
use Neos\ContentRepository\Projection\Content\InMemoryCache\NodePathCache;
/** @codingStandardsIgnoreStart */
use Neos\ContentRepository\Projection\Content\InMemoryCache\ParentNodeIdentifierByChildNodeIdentifierCache;
/** @codingStandardsIgnoreEnd */

/**
 * Accessors to In Memory Cache
 */
final class InMemoryCache
{
    /**
     * @var NodePathCache
     */
    private $nodePathCache;

    /**
     * @var NodeByNodeAggregateIdentifierCache
     */
    private $nodeByNodeAggregateIdentifierCache;

    /**
     * @var AllChildNodesByNodeIdentifierCache
     */
    private $allChildNodesByNodeIdentifierCache;

    /**
     * @var NamedChildNodeByNodeIdentifierCache
     */
    private $namedChildNodeByNodeIdentifierCache;

    /**
     * @var ParentNodeIdentifierByChildNodeIdentifierCache
     */
    private $parentNodeIdentifierByChildNodeIdentifierCache;

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

    public function getNodeByNodeAggregateIdentifierCache(): NodeByNodeAggregateIdentifierCache
    {
        return $this->nodeByNodeAggregateIdentifierCache;
    }

    /**
     * @return AllChildNodesByNodeIdentifierCache
     */
    public function getAllChildNodesByNodeIdentifierCache(): AllChildNodesByNodeIdentifierCache
    {
        return $this->allChildNodesByNodeIdentifierCache;
    }

    /**
     * @return NamedChildNodeByNodeIdentifierCache
     */
    public function getNamedChildNodeByNodeIdentifierCache(): NamedChildNodeByNodeIdentifierCache
    {
        return $this->namedChildNodeByNodeIdentifierCache;
    }

    /**
     * @return ParentNodeIdentifierByChildNodeIdentifierCache
     */
    public function getParentNodeIdentifierByChildNodeIdentifierCache(): ParentNodeIdentifierByChildNodeIdentifierCache
    {
        return $this->parentNodeIdentifierByChildNodeIdentifierCache;
    }

    /**
     * @param bool $isEnabled if TRUE, the caches work; if FALSE, they do not store anything.
     */
    private function reset(bool $isEnabled): void
    {
        $this->nodePathCache = new NodePathCache($isEnabled);
        $this->nodeByNodeAggregateIdentifierCache = new NodeByNodeAggregateIdentifierCache($isEnabled);
        $this->allChildNodesByNodeIdentifierCache = new AllChildNodesByNodeIdentifierCache($isEnabled);
        $this->namedChildNodeByNodeIdentifierCache = new NamedChildNodeByNodeIdentifierCache($isEnabled);
        $this->parentNodeIdentifierByChildNodeIdentifierCache
            = new ParentNodeIdentifierByChildNodeIdentifierCache($isEnabled);
    }
}

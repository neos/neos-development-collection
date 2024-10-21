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

namespace Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\InMemoryCache;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * NOTE: we do NOT directly cache the Parent Node; but only the Parent Node ID;
 * as then, the NodeByNodeIdCache can be used properly
 * - thus it might increase the cache hit rate to split this apart.
 *
 * @internal
 */
final class ParentNodeIdByChildNodeIdCache
{
    /**
     * @var array<string,NodeAggregateId>
     */
    protected array $parentNodeAggregateIds = [];

    /**
     * @var array<string,bool>
     */
    protected array $nodesWithoutParentNode = [];

    public function add(NodeAggregateId $childNodeAggregateId, NodeAggregateId $parentNodeAggregateId): void
    {
        $this->parentNodeAggregateIds[$childNodeAggregateId->value] = $parentNodeAggregateId;
    }

    public function knowsAbout(NodeAggregateId $childNodeAggregateId): bool
    {
        return isset($this->parentNodeAggregateIds[$childNodeAggregateId->value]) || isset($this->nodesWithoutParentNode[$childNodeAggregateId->value]);
    }

    public function rememberNonExistingParentNode(NodeAggregateId $nodeAggregateId): void
    {
        $this->nodesWithoutParentNode[$nodeAggregateId->value] = true;
    }


    public function get(NodeAggregateId $childNodeAggregateId): ?NodeAggregateId
    {
        return $this->parentNodeAggregateIds[$childNodeAggregateId->value] ?? null;
    }
}

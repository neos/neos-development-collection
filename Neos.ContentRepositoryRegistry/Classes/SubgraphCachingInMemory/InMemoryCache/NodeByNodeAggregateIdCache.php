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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * NodeAggregateId -> Node cache
 *
 * also contains a *blacklist* of unknown NodeAggregateIds.
 *
 * @internal
 */
final class NodeByNodeAggregateIdCache
{
    /**
     * @var array<string,Node>
     */
    private array $nodes = [];

    /**
     * @var array<string,bool>
     */
    protected array $nonExistingNodeAggregateIds = [];

    /**
     * basically like "contains"
     */
    public function knowsAbout(NodeAggregateId $nodeAggregateId): bool
    {
        return isset($this->nodes[$nodeAggregateId->value]) || isset($this->nonExistingNodeAggregateIds[$nodeAggregateId->value]);
    }

    public function add(NodeAggregateId $nodeAggregateId, Node $node): void
    {
        $this->nodes[$nodeAggregateId->value] = $node;
    }

    public function rememberNonExistingNodeAggregateId(NodeAggregateId $nodeAggregateId): void
    {
        $this->nonExistingNodeAggregateIds[$nodeAggregateId->value] = true;
    }

    public function get(NodeAggregateId $nodeAggregateId): ?Node
    {
        return $this->nodes[$nodeAggregateId->value] ?? null;
    }
}

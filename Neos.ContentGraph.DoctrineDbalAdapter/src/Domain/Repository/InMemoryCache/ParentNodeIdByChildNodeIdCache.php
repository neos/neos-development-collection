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

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\InMemoryCache;

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

    protected bool $isEnabled;

    public function __construct(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    public function add(NodeAggregateId $childNodeAggregateId, NodeAggregateId $parentNodeAggregateId): void
    {
        if ($this->isEnabled === false) {
            return;
        }

        $key = (string)$childNodeAggregateId;
        $this->parentNodeAggregateIds[$key] = $parentNodeAggregateId;
    }

    public function knowsAbout(NodeAggregateId $childNodeAggregateId): bool
    {
        if ($this->isEnabled === false) {
            return false;
        }

        $key = (string)$childNodeAggregateId;
        return isset($this->parentNodeAggregateIds[$key]) || isset($this->nodesWithoutParentNode[$key]);
    }

    public function rememberNonExistingParentNode(NodeAggregateId $nodeAggregateId): void
    {
        if ($this->isEnabled === false) {
            return;
        }

        $key = (string)$nodeAggregateId;
        $this->nodesWithoutParentNode[$key] = true;
    }


    public function get(NodeAggregateId $childNodeAggregateId): ?NodeAggregateId
    {
        if ($this->isEnabled === false) {
            return null;
        }

        $key = (string)$childNodeAggregateId;
        return $this->parentNodeAggregateIds[$key] ?? null;
    }
}

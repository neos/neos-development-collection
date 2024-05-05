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

namespace Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphWithRuntimeCaches\InMemoryCache;

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
    protected array $nodes = [];

    /**
     * @var array<string,bool>
     */
    protected array $nonExistingNodeAggregateIds = [];

    protected bool $isEnabled;

    public function __construct(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * basically like "contains"
     */
    public function knowsAbout(NodeAggregateId $nodeAggregateId): bool
    {
        if ($this->isEnabled === false) {
            return false;
        }

        return isset($this->nodes[$nodeAggregateId->value]) || isset($this->nonExistingNodeAggregateIds[$nodeAggregateId->value]);
    }

    public function add(NodeAggregateId $nodeAggregateId, Node $node): void
    {
        if ($this->isEnabled === false) {
            return;
        }

        $this->nodes[$nodeAggregateId->value] = $node;
    }

    public function rememberNonExistingNodeAggregateId(NodeAggregateId $nodeAggregateId): void
    {
        if ($this->isEnabled === false) {
            return;
        }

        $this->nonExistingNodeAggregateIds[$nodeAggregateId->value] = true;
    }

    public function get(NodeAggregateId $nodeAggregateId): ?Node
    {
        if ($this->isEnabled === false) {
            return null;
        }

        return $this->nodes[$nodeAggregateId->value] ?? null;
    }
}

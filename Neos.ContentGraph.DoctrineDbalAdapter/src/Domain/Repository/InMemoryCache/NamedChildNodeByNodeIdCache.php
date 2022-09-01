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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * Parent Node ID + Node Name => Child Node
 *
 * @internal
 */
final class NamedChildNodeByNodeIdCache
{
    /**
     * first level: Parent Node ID
     * Second Level: Node Name
     * Value: Node
     * @var array<string,array<string,Node>>
     */
    protected array $nodes = [];

    protected bool $isEnabled;

    public function __construct(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    public function add(
        NodeAggregateId $parentNodeAggregateId,
        ?NodeName $nodeName,
        Node $node
    ): void {
        if ($this->isEnabled === false) {
            return;
        }

        if ($nodeName === null) {
            return;
        }

        $this->nodes[(string)$parentNodeAggregateId][(string)$nodeName] = $node;
    }

    public function contains(NodeAggregateId $parentNodeAggregateId, NodeName $nodeName): bool
    {
        if ($this->isEnabled === false) {
            return false;
        }

        return isset($this->nodes[(string)$parentNodeAggregateId][(string)$nodeName]);
    }

    public function get(NodeAggregateId $parentNodeAggregateId, NodeName $nodeName): ?Node
    {
        if ($this->isEnabled === false) {
            return null;
        }

        return $this->nodes[(string)$parentNodeAggregateId][(string)$nodeName] ?? null;
    }
}

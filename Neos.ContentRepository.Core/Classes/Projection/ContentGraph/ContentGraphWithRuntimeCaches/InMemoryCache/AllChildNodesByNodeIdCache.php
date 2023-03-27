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

use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal
 */
final class AllChildNodesByNodeIdCache
{
    /**
     * @var array<string,array<string,Nodes>>
     */
    private array $childNodes = [];

    public function __construct(
        private readonly bool $isEnabled
    ) {
    }

    public function add(
        NodeAggregateId $parentNodeAggregateId,
        ?NodeTypeConstraints $nodeTypeConstraints,
        Nodes $childNodes
    ): void {
        if ($this->isEnabled === false) {
            return;
        }

        $nodeTypeConstraintsKey = $nodeTypeConstraints !== null ? (string)$nodeTypeConstraints : '*';
        $this->childNodes[$parentNodeAggregateId->value][$nodeTypeConstraintsKey] = $childNodes;
    }

    public function contains(
        NodeAggregateId $parentNodeAggregateId,
        ?NodeTypeConstraints $nodeTypeConstraints
    ): bool {
        if ($this->isEnabled === false) {
            return false;
        }

        $nodeTypeConstraintsKey = $nodeTypeConstraints !== null ? (string)$nodeTypeConstraints : '*';
        return isset($this->childNodes[$parentNodeAggregateId->value][$nodeTypeConstraintsKey]);
    }

    public function findChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        ?NodeTypeConstraints $nodeTypeConstraints,
    ): Nodes {
        if ($this->isEnabled === false) {
            return Nodes::createEmpty();
        }
        $nodeTypeConstraintsKey = $nodeTypeConstraints !== null ? (string)$nodeTypeConstraints : '*';
        return $this->childNodes[$parentNodeAggregateId->value][$nodeTypeConstraintsKey] ?? Nodes::createEmpty();
    }

    public function countChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        ?NodeTypeConstraints $nodeTypeConstraints
    ): int {
        return $this->findChildNodes($parentNodeAggregateId, $nodeTypeConstraints)->count();
    }
}

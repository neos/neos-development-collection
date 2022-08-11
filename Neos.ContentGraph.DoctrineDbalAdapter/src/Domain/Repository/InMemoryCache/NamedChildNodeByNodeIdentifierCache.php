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

use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;

/**
 * Parent Node Identifier + Node Name => Child Node
 *
 * @internal
 */
final class NamedChildNodeByNodeIdentifierCache
{
    /**
     * first level: Parent Node Identifier
     * Second Level: Node Name
     * Value: Node
     * @var array<string,array<string,NodeInterface>>
     */
    protected array $nodes = [];

    protected bool $isEnabled;

    public function __construct(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    public function add(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?NodeName $nodeName,
        NodeInterface $node
    ): void {
        if ($this->isEnabled === false) {
            return;
        }

        if ($nodeName === null) {
            return;
        }

        $this->nodes[(string)$parentNodeAggregateIdentifier][(string)$nodeName] = $node;
    }

    public function contains(NodeAggregateIdentifier $parentNodeAggregateIdentifier, NodeName $nodeName): bool
    {
        if ($this->isEnabled === false) {
            return false;
        }

        return isset($this->nodes[(string)$parentNodeAggregateIdentifier][(string)$nodeName]);
    }

    public function get(NodeAggregateIdentifier $parentNodeAggregateIdentifier, NodeName $nodeName): ?NodeInterface
    {
        if ($this->isEnabled === false) {
            return null;
        }

        return $this->nodes[(string)$parentNodeAggregateIdentifier][(string)$nodeName] ?? null;
    }
}

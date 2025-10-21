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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * An immutable, type-safe collection of NodeAggregate objects
 *
 * @implements \IteratorAggregate<NodeAggregate>
 *
 * @api
 */
final class NodeAggregates implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string,NodeAggregate> indexed by NodeAggregate Id
     */
    private array $nodeAggregates;

    /**
     * @param array<NodeAggregate> $items
     */
    private function __construct(array $items)
    {
        $indexedItems = [];
        foreach ($items as $item) {
            if (!$item instanceof NodeAggregate) {
                throw new \InvalidArgumentException(
                    'Nodes Aggregates can only consist of ' . NodeAggregate::class . ' objects.',
                    1618044512
                );
            }
            $indexedItems[$item->nodeAggregateId->value] = $item;
        }

        $this->nodeAggregates = $indexedItems;
    }

    /**
     * @param array<NodeAggregate> $nodeAggregates
     */
    public static function fromArray(array $nodeAggregates): self
    {
        return new self($nodeAggregates);
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public function get(NodeAggregateId $nodeAggregateId): ?NodeAggregate
    {
        return $this->nodeAggregates[$nodeAggregateId->value] ?? null;
    }

    public function first(): ?NodeAggregate
    {
        foreach ($this->nodeAggregates as $nodeAggregate) {
            return $nodeAggregate;
        }
        return null;
    }

    public function getIterator(): \Traversable
    {
        yield from array_values($this->nodeAggregates);
    }

    public function count(): int
    {
        return count($this->nodeAggregates);
    }

    public function isEmpty(): bool
    {
        return $this->nodeAggregates === [];
    }
}

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

/**
 * An immutable, type-safe collection of NodeAggregate objects
 *
 * @implements \IteratorAggregate<int,NodeAggregate>
 *
 * @api
 */
final class NodeAggregates implements \IteratorAggregate, \Countable
{
    /**
     * @var array<int,NodeAggregate>
     */
    private array $nodeAggregates;

    /**
     * @param iterable<mixed,NodeAggregate> $collection
     */
    private function __construct(iterable $collection)
    {
        $nodes = [];
        foreach ($collection as $item) {
            if (!$item instanceof NodeAggregate) {
                throw new \InvalidArgumentException(
                    'Nodes can only consist of ' . NodeAggregate::class . ' objects.',
                    1618044512
                );
            }
            $nodes[] = $item;
        }

        $this->nodeAggregates = $nodes;
    }

    /**
     * @param array<mixed,NodeAggregate> $nodeAggregates
     */
    public static function fromArray(array $nodeAggregates): self
    {
        return new self($nodeAggregates);
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @return \ArrayIterator<int,NodeAggregate>|NodeAggregate[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodeAggregates);
    }

    public function count(): int
    {
        return count($this->nodeAggregates);
    }

    /**
     * @return NodeAggregate|null
     */
    public function first(): ?NodeAggregate
    {
        if (count($this->nodeAggregates) > 0) {
            $array = $this->nodeAggregates;
            return reset($array);
        }
        return null;
    }
}

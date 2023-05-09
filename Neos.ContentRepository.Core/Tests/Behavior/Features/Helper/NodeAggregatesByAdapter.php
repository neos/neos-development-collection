<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Helper;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;

/**
 * An immutable, type-safe collection of NodeAggregate objects, indexed by content graph adapter
 *
 * @implements \IteratorAggregate<string,NodeAggregate>
 * @implements \ArrayAccess<string,NodeAggregate>
 */
final class NodeAggregatesByAdapter implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @var array<string,NodeAggregate>
     */
    private array $nodeAggregates;

    /**
     * @var \ArrayIterator<string,NodeAggregate>
     */
    private \ArrayIterator $iterator;

    /**
     * @param iterable<string,NodeAggregate> $collection
     */
    public function __construct(iterable $collection)
    {
        $nodeAggregates = [];
        foreach ($collection as $adapterName => $item) {
            if (!is_string($adapterName) || empty($adapterName)) {
                throw new \InvalidArgumentException('NodeAggregatesByAdapter must be indexed by adapter name', 1643562024);
            }
            if (!$item instanceof NodeAggregate) {
                throw new \InvalidArgumentException('NodeAggregatesByAdapter can only consist of ' . NodeAggregate::class . ' objects.', 1618138191);
            }
            $nodeAggregates[$adapterName] = $item;
        }
        $this->nodeAggregates = $nodeAggregates;
        $this->iterator = new \ArrayIterator($nodeAggregates);
    }

    /**
     * @return \ArrayIterator<string,NodeAggregate>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->nodeAggregates[$offset]);
    }

    public function offsetGet(mixed $offset): ?NodeAggregate
    {
        return $this->nodeAggregates[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class NodeAggregatesByAdapter.', 1643562191);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class NodeAggregatesByAdapter.', 1643562191);
    }
}

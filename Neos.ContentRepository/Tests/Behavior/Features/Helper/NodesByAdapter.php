<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Behavior\Features\Helper;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;

/**
 * An immutable, type-safe collection of Node objects, indexed by content graph adapter
 *
 * @implements \IteratorAggregate<string,Node>
 * @implements \ArrayAccess<string,Node>
 */
#[Flow\Proxy(false)]
final class NodesByAdapter implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @var array<string,Node>
     */
    private array $nodes;

    /**
     * @var \ArrayIterator<string,Node>
     */
    private \ArrayIterator $iterator;

    /**
     * @param iterable<string,Node> $collection
     */
    public function __construct(iterable $collection)
    {
        $nodes = [];
        foreach ($collection as $adapterName => $item) {
            if (!is_string($adapterName) || empty($adapterName)) {
                throw new \InvalidArgumentException('NodesByAdapter must be indexed by adapter name', 1643562288);
            }
            if (!$item instanceof Node) {
                throw new \InvalidArgumentException('NodesByAdapter can only consist of ' . Node::class . ' objects.', 1618137807);
            }
            $nodes[$adapterName] = $item;
        }
        $this->nodes = $nodes;
        $this->iterator = new \ArrayIterator($nodes);
    }

    /**
     * @return \ArrayIterator<string,Node>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->nodes[$offset]);
    }

    public function offsetGet(mixed $offset): ?Node
    {
        return $this->nodes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class NodesByAdapter.', 1643562390);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class NodesByAdapter.', 1643562390);
    }
}

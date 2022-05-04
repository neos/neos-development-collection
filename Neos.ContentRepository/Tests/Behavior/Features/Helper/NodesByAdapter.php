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

use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * An immutable, type-safe collection of NodeInterface objects, indexed by content graph adapter
 *
 * @implements \IteratorAggregate<string,NodeInterface>
 * @implements \ArrayAccess<string,NodeInterface>
 */
#[Flow\Proxy(false)]
final class NodesByAdapter implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @var array<string,NodeInterface>
     */
    private array $nodes;

    /**
     * @var \ArrayIterator<string,NodeInterface>
     */
    private \ArrayIterator $iterator;

    /**
     * @param iterable<string,NodeInterface> $collection
     */
    public function __construct(iterable $collection)
    {
        $nodes = [];
        foreach ($collection as $adapterName => $item) {
            if (!is_string($adapterName) || empty($adapterName)) {
                throw new \InvalidArgumentException('NodesByAdapter must be indexed by adapter name', 1643562288);
            }
            if (!$item instanceof NodeInterface) {
                throw new \InvalidArgumentException('NodesByAdapter can only consist of ' . NodeInterface::class . ' objects.', 1618137807);
            }
            $nodes[$adapterName] = $item;
        }
        $this->nodes = $nodes;
        $this->iterator = new \ArrayIterator($nodes);
    }

    /**
     * @return \ArrayIterator<string,NodeInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->nodes[$offset]);
    }

    public function offsetGet(mixed $offset): ?NodeInterface
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

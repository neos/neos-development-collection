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

namespace Neos\ContentRepository\Projection\ContentGraph;

/**
 * An immutable, type-safe collection of Reference objects
 *
 * @implements \IteratorAggregate<int,Reference>
 * @implements \ArrayAccess<int,Reference>
 *
 * @api
 */
final class References implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array<int,Reference>
     */
    public readonly array $references;

    private function __construct(
        Reference ...$references
    ) {
        /** @var array<int,Reference> $references */
        $this->references = $references;
    }

    /**
     * @param array<int,Reference> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(...$array);
    }

    public function getNodes(): Nodes
    {
        return Nodes::fromArray(array_map(
            fn (Reference $reference): Node => $reference->node,
            $this->references
        ));
    }

    /**
     * @return \ArrayIterator<int,Reference>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->references);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->references[$offset]);
    }

    public function offsetGet(mixed $offset): ?Reference
    {
        return $this->references[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class References.', 1658408830);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class References.', 1658408830);
    }

    public function count(): int
    {
        return count($this->references);
    }
}

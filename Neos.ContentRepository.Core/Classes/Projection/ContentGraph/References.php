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
 * An immutable, 0-indexed, type-safe collection of Reference objects
 *
 * Each reference describes the edge with its properties to another node.
 *
 * - references:
 *  In the case of "outgoing" references {@see ContentSubgraphInterface::findReferences()}
 *  each reference name {@see Reference::$name} corresponds to the reference property name from the "outgoing" node, to wich node {@see Reference::$node} the reference points to.
 *
 * - back-references:
 *  In the case of "incoming" references {@see ContentSubgraphInterface::findBackReferences()}
 *  each reference name {@see Reference::$name} corresponds to the reference property name from wich "incoming" node {@see Reference::$node} the reference points from.
 *
 * The properties {@see Reference::$properties} are declared directly on the reference, and can provide information how one node is linked to another.
 *
 * This collection might return multiple references with the same name {@see Reference::$name} if multiple "outgoing" references were set.
 *
 * @implements \IteratorAggregate<int,Reference>
 * @implements \ArrayAccess<int,Reference>
 *
 * @api
 */
final readonly class References implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array<int,Reference>
     */
    public array $references;

    private function __construct(
        Reference ...$references
    ) {
        $this->references = array_values($references);
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
     * @return \Traversable<int,Reference>
     */
    public function getIterator(): \Traversable
    {
        return yield from $this->references;
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

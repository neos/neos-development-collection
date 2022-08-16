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
 * An immutable, type-safe collection of Node objects
 *
 * @implements \IteratorAggregate<int,Node>
 * @implements \ArrayAccess<int,Node>
 *
 * @api
 */
final class Nodes implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array<int,Node>
     */
    private array $nodes;

    /**
     * @param iterable<int,Node> $collection
     */
    private function __construct(iterable $collection)
    {
        $nodes = [];
        foreach ($collection as $item) {
            if (!$item instanceof Node) {
                throw new \InvalidArgumentException(
                    'Nodes can only consist of ' . Node::class . ' objects.',
                    1618044512
                );
            }
            $nodes[] = $item;
        }

        $this->nodes = $nodes;
    }

    /**
     * @param array<int,Node> $nodes
     */
    public static function fromArray(array $nodes): self
    {
        return new self($nodes);
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public function offsetGet(mixed $offset): ?Node
    {
        return $this->nodes[$offset] ?? null;
    }

    /**
     * @return \ArrayIterator<int,Node>|Node[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodes);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->nodes[$offset]);
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class Nodes.', 1643488734);
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class Nodes.', 1643488734);
    }

    public function count(): int
    {
        return count($this->nodes);
    }

    public function first(): ?Node
    {
        if (count($this->nodes) > 0) {
            $array = $this->nodes;
            return reset($array);
        }

        return null;
    }

    public function merge(self $other): self
    {
        $nodes = array_merge($this->nodes, $other->getIterator()->getArrayCopy());

        return self::fromArray($nodes);
    }

    public function reverse(): self
    {
        return new self(array_reverse($this->nodes));
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    private function getNodeIndex(Node $subject): int
    {
        foreach ($this->nodes as $index => $node) {
            if ($node->equals($subject)) {
                return $index;
            }
        }
        throw new \InvalidArgumentException(sprintf(
            'The node %s does not exist in this set',
            $subject->nodeAggregateIdentifier
        ), 1542901216);
    }

    /**
     * Returns the node before the given $referenceNode in this set.
     * Throws an exception if $referenceNode does not exist. Returns NULL if $referenceNode has no preceding sibling
     *
     * @param Node $referenceNode
     * @return Node
     */
    public function previous(Node $referenceNode): ?Node
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);
        if ($referenceNodeIndex === 0) {
            return null;
        }
        return $this[$referenceNodeIndex - 1];
    }

    /**
     * Returns all nodes before the given $referenceNode in this set
     */
    public function previousAll(Node $referenceNode): self
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);

        return new self(array_slice($this->nodes, 0, $referenceNodeIndex));
    }

    /**
     * Returns the node after the given $referenceNode in this set.
     * Throws an exception if $referenceNode does not exist. Returns NULL if $referenceNode has no following sibling
     */
    public function next(Node $referenceNode): ?Node
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);
        if ($referenceNodeIndex === $this->count() - 1) {
            return null;
        }

        return $this[$referenceNodeIndex + 1];
    }

    /**
     * Returns all nodes after the given $referenceNode in this set
     */
    public function nextAll(Node $referenceNode): self
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);

        return new self(array_slice($this->nodes, $referenceNodeIndex + 1));
    }

    /**
     * Returns all nodes after the given $referenceNode in this set
     */
    public function until(Node $referenceNode): self
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);
        return new self(array_slice($this->nodes, $referenceNodeIndex + 1));
    }
}

<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\ImmutableArrayObject;
use Neos\Flow\Annotations as Flow;

/**
 * An immutable, type-safe collection of NodeInterface objects
 * @Flow\Proxy(false)
 */
final class Nodes extends ImmutableArrayObject
{
    private function __construct(iterable $collection)
    {
        $nodes = [];
        foreach ($collection as $item) {
            if (!$item instanceof NodeInterface) {
                throw new \InvalidArgumentException(get_class() . ' can only consist of ' . NodeInterface::class . ' objects.', 1618044512);
            }
            $nodes[] = $item;
        }
        parent::__construct($nodes);
    }

    public static function fromArray(array $nodes): self
    {
        return new self($nodes);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function first(): ?NodeInterface
    {
        if (count($this) > 0) {
            $array = $this->getArrayCopy();
            return reset($array);
        }

        return null;
    }

    public function merge(self $other): self
    {
        $nodes = array_merge($this->getArrayCopy(), $other->getArrayCopy());

        return self::fromArray($nodes);
    }

    /**
     * @return array|NodeInterface[]
     */
    public function getArrayCopy(): array
    {
        return parent::getArrayCopy();
    }

    /**
     * @return \ArrayIterator|NodeInterface[]
     */
    public function getIterator(): \ArrayIterator
    {
        return parent::getIterator();
    }


    /**
     * @param mixed $key
     * @return NodeInterface|false
     */
    public function offsetGet($key): ?NodeInterface
    {
        return parent::offsetGet($key) ?: null;
    }


    public function reverse(): self
    {
        return new self(array_reverse($this->getArrayCopy()));
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    private function getNodeIndex(NodeInterface $subject): int
    {
        foreach ($this->getArrayCopy() as $index => $node) {
            if ($node->equals($subject)) {
                return $index;
            }
        }
        throw new \InvalidArgumentException(sprintf('The node %s does not exist in this set', $subject->getNodeAggregateIdentifier()), 1542901216);
    }

    /**
     * Returns the node before the given $referenceNode in this set. Throws an exception if $referenceNode does not exist. Returns NULL if $referenceNode has no preceding sibling
     *
     * @param NodeInterface $referenceNode
     * @return NodeInterface
     */
    public function previous(NodeInterface $referenceNode): ?NodeInterface
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
    public function previousAll(NodeInterface $referenceNode): self
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);

        return new self(array_slice($this->getArrayCopy(), 0, $referenceNodeIndex));
    }

    /**
     * Returns the node after the given $referenceNode in this set. Throws an exception if $referenceNode does not exist. Returns NULL if $referenceNode has no following sibling
     */
    public function next(NodeInterface $referenceNode): ?NodeInterface
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
    public function nextAll(NodeInterface $referenceNode): self
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);

        return new self(array_slice($this->getArrayCopy(), $referenceNodeIndex + 1));
    }

    /**
     * Returns all nodes after the given $referenceNode in this set
     */
    public function until(NodeInterface $referenceNode): self
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);
        return new self(array_slice($this->getArrayCopy(), $referenceNodeIndex + 1));
    }
}

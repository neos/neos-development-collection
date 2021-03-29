<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * The collection value object for NodeBasedReadModel objects
 *
 * @Flow\Proxy(false)
 */
final class NodeBasedReadModels implements \IteratorAggregate, \Countable
{
    /**
     * @var NodeBasedReadModelInterface[]
     */
    private array $nodes;

    private function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }

    public static function fromArray(array $nodes): self
    {
        foreach ($nodes as $node) {
            if (!$node instanceof NodeBasedReadModelInterface) {
                throw new \InvalidArgumentException(sprintf('NodeBasedReadModels only support instances of %s, given: %s', NodeBasedReadModelInterface::class, is_object($node) ? get_class($node): gettype($node)), 1542893076);
            }
        }

        return new self($nodes);
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->nodes, $other->nodes));
    }

    public function isEmpty(): bool
    {
        return $this->nodes === [];
    }

    public function count(): int
    {
        return count($this->nodes);
    }

    public function getFirst(): ?NodeBasedReadModelInterface
    {
        return reset($this->nodes) ?? null;
    }

    private function getNodeIndex(NodeBasedReadModelInterface $subject): int
    {
        foreach ($this->nodes as $index => $node) {
            if ($node->equals($subject)) {
                return $index;
            }
        }
        throw new \InvalidArgumentException(sprintf('The node %s does not exist in this set', $subject->getNodeAggregateIdentifier()), 1542901216);
    }

    /**
     * Returns the node before the given $referenceNode in this set - or throws an exception if $referenceNode does not exist or is the first node in the set
     *
     * @param NodeBasedReadModelInterface $referenceNode
     * @return NodeBasedReadModelInterface
     */
    public function previous(NodeBasedReadModelInterface $referenceNode): NodeBasedReadModelInterface
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);
        if ($referenceNodeIndex === 0) {
            throw new \InvalidArgumentException(sprintf('The node %s is the first node in the set, so there is no previous node.', $referenceNode->getNodeAggregateIdentifier()), 1542902422);
        }
        return $this->nodes[$referenceNodeIndex - 1];
    }

    /**
     * Returns all nodes before the given $referenceNode in this set
     */
    public function previousAll(NodeBasedReadModelInterface $referenceNode): self
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);

        return new self(array_slice($this->nodes, 0, $referenceNodeIndex));
    }

    /**
     * Returns the node after the given $referenceNode in this set - or throws an exception if $referenceNode does not exist or is the last node in the set
     */
    public function next(NodeBasedReadModelInterface $referenceNode): NodeBasedReadModelInterface
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);
        if ($referenceNodeIndex === $this->count() - 1) {
            throw new \InvalidArgumentException(sprintf('The node %s is the last node in the set, so there is no next node.', $referenceNode->getNodeAggregateIdentifier()), 1542902858);
        }

        return $this->nodes[$referenceNodeIndex + 1];
    }

    /**
     * Returns all nodes after the given $referenceNode in this set
     */
    public function nextAll(NodeBasedReadModelInterface $referenceNode): self
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);

        return new self(array_slice($this->nodes, $referenceNodeIndex + 1));
    }

    /**
     * Returns all nodes after the given $referenceNode in this set
     */
    public function until(NodeBasedReadModelInterface $referenceNode): self
    {
        $referenceNodeIndex = $this->getNodeIndex($referenceNode);
        return new self(array_slice($this->nodes, $referenceNodeIndex + 1));
    }

    /**
     * @return NodeBasedReadModelInterface[]|\ArrayIterator<NodeBasedReadModelInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodes);
    }

    /**
     * @return NodeBasedReadModelInterface[]|array<NodeBasedReadModelInterface>
     */
    public function toArray(): array
    {
        return array_values($this->nodes);
    }
}

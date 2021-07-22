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

use Neos\Flow\Annotations as Flow;

/**
 * An immutable, type-safe collection of NodeInterface objects
 * @Flow\Proxy(false)
 */
final class Nodes implements \IteratorAggregate, \Countable
{
    private array $nodes;

    private function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }

    public static function fromArray(array $array): self
    {
        $nodes = [];
        foreach ($array as $item) {
            if (!$item instanceof NodeInterface) {
                throw new \InvalidArgumentException(get_class() . ' can only consist of ' . NodeInterface::class . ' objects.', 1618044512);
            }
            $nodes[] = $item;
        }

        return new self($nodes);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return \ArrayIterator<int|string, NodeInterface>|NodeInterface[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodes);
    }

    public function count(): int
    {
        return count($this->nodes);
    }

    public function first(): ?NodeInterface
    {
        if (count($this->nodes) > 0) {
            return reset($this->nodes);
        }

        return null;
    }

    public function merge(self $other): self
    {
        $nodes = array_merge($this->nodes, $other->nodes);

        return self::fromArray($nodes);
    }

    public function reverse(): self
    {
        return new self(array_reverse($this->nodes));
    }
}

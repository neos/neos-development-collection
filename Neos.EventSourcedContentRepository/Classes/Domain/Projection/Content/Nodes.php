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
    public function __construct(Iterable $collection)
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

    public function reverse(): self
    {
        return new self(array_reverse($this->getArrayCopy()));
    }

    /**
     * @param mixed $key
     * @return NodeInterface|false
     */
    public function offsetGet($key): ?NodeInterface
    {
        return parent::offsetGet($key) ?: null;
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
}

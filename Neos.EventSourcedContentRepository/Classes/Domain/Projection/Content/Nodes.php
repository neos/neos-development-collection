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
 * @Flow\Proxy(false)
 */
final class Nodes implements \IteratorAggregate, \Countable
{

    private array $nodes;

    private function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }

    public static function fromArray(array $nodes)
    {
        return new static($nodes);
    }

    public static function empty()
    {
        return new static([]);
    }


    public function getIterator()
    {
        return new \ArrayIterator($this->nodes);
    }

    public function count()
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

    public function merge(Nodes $other): Nodes
    {
        $nodes = array_merge($this->nodes, $other->nodes);
        return self::fromArray($nodes);
    }
}

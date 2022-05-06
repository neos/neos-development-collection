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

use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The content graph repository collection, indexed by adapter package
 *
 * @implements \IteratorAggregate<string,ContentGraphInterface>
 * @implements \ArrayAccess<string,ContentGraphInterface>
 */
#[Flow\Proxy(false)]
final class ContentGraphs implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @var array<string,ContentGraphInterface>
     */
    private array $contentGraphs;

    /**
     * @var \ArrayIterator<string,ContentGraphInterface>
     */
    private \ArrayIterator $iterator;

    /**
     * @param iterable<string,ContentGraphInterface> $iterable
     */
    public function __construct(iterable $iterable) {
        $contentGraphs = [];
        foreach ($iterable as $adapterName => $item) {
            if (!is_string($adapterName) || empty($adapterName)) {
                throw new \InvalidArgumentException('ContentGraphs must be indexed by adapter name', 1643488356);
            }
            if (!$item instanceof ContentGraphInterface) {
                throw new \InvalidArgumentException('ContentGraphs can only consist of ' . ContentGraphInterface::class . ' objects.', 1618130675);
            }
            $contentGraphs[$adapterName] = $item;
        }
        $this->contentGraphs = $contentGraphs;
        $this->iterator = new \ArrayIterator($contentGraphs);
    }

    public function offsetGet(mixed $offset): ContentGraphInterface|null
    {
        return $this->contentGraphs[$offset] ?? null;
    }

    /**
     * @return \ArrayIterator<string,ContentGraphInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->contentGraphs[$offset]);
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class ContentGraphs.', 1643488734);
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class ContentGraphs.', 1643488734);
    }

    /**
     * @param array<int,string> $identifiers
     */
    public function reduceTo(array $identifiers): self
    {
        $reduction = [];
        foreach ($identifiers as $identifier) {
            if (isset($this->contentGraphs[$identifier])) {
                $reduction[$identifier] = $this->contentGraphs[$identifier];
            } else {
                // TODO: we want to skip the postgres adapter for now.
                //throw new \InvalidArgumentException('Unknown adapter "' . $identifier . '"', 1648406324);
            }
        }

        return new self($reduction);
    }
}

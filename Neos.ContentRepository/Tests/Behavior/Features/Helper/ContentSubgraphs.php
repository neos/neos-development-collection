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

use Neos\ContentRepository\Projection\Content\ContentSubgraphInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The content subgraph repository collection, indexed by adapter package
 *
 * @implements \IteratorAggregate<string,ContentSubgraphInterface>
 * @implements \ArrayAccess<string,ContentSubgraphInterface>
 */
#[Flow\Proxy(false)]
final class ContentSubgraphs implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @var array<string,ContentSubgraphInterface>
     */
    private array $contentSubgraphs;

    /**
     * @var \ArrayIterator<string,ContentSubgraphInterface>
     */
    private \ArrayIterator $iterator;

    /**
     * @param iterable<string,ContentSubgraphInterface> $collection
     */
    public function __construct(iterable $collection)
    {
        $contentSubgraphs = [];
        foreach ($collection as $adapterName => $item) {
            if (!is_string($adapterName) || empty($adapterName)) {
                throw new \InvalidArgumentException('ContentSubgraphs must be indexed by adapter name', 1643560134);
            }
            if (!$item instanceof ContentSubgraphInterface) {
                throw new \InvalidArgumentException('ContentSubgraphs can only consist of ' . ContentSubgraphInterface::class . ' objects.', 1618130758);
            }
            $contentSubgraphs[$adapterName] = $item;
        }
        $this->contentSubgraphs = $contentSubgraphs;
        $this->iterator = new \ArrayIterator($contentSubgraphs);
    }

    public function offsetGet(mixed $offset): ContentSubgraphInterface|null
    {
        return $this->contentSubgraphs[$offset] ?? null;
    }

    /**
     * @return \ArrayIterator<string,ContentSubgraphInterface>
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
        throw new \BadMethodCallException('Cannot modify immutable object of class ContentSubgraphs.', 1643560225);
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class ContentSubgraphs.', 1643560225);
    }
}

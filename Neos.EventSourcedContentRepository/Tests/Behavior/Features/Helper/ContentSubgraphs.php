<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper;

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
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The content subgraph repository collection, indexed by adapter package
 * @Flow\Proxy(false)
 */
final class ContentSubgraphs extends ImmutableArrayObject
{
    public function __construct(Iterable $collection)
    {
        $subgraphs = [];
        foreach ($collection as $adapterName => $item) {
            if (!$item instanceof ContentSubgraphInterface) {
                throw new \InvalidArgumentException(get_class() . ' can only consist of ' . ContentSubgraphInterface::class . ' objects.', 1618130758);
            }
            $subgraphs[$adapterName] = $item;
        }
        parent::__construct($subgraphs);
    }

    /**
     * @param mixed $key
     * @return ContentSubgraphInterface|false
     */
    public function offsetGet($key)
    {
        return parent::offsetGet($key);
    }

    /**
     * @return array|ContentSubgraphInterface[]
     */
    public function getArrayCopy(): array
    {
        return parent::getArrayCopy();
    }

    /**
     * @return \ArrayIterator|ContentSubgraphInterface[]
     */
    public function getIterator(): \ArrayIterator
    {
        return parent::getIterator();
    }
}

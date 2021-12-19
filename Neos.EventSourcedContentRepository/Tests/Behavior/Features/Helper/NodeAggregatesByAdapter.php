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

use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\ImmutableArrayObject;
use Neos\Flow\Annotations as Flow;

/**
 * An immutable, type-safe collection of ReadableNodeAggregateInterface objects, indexed by content graph adapter
 * @Flow\Proxy(false)
 */
final class NodeAggregatesByAdapter extends ImmutableArrayObject
{
    public function __construct(Iterable $collection)
    {
        $nodeAggregates = [];
        foreach ($collection as $adapterName => $item) {
            if (!$item instanceof ReadableNodeAggregateInterface) {
                throw new \InvalidArgumentException(get_class() . ' can only consist of ' . ReadableNodeAggregateInterface::class . ' objects.', 1618138191);
            }
            $nodeAggregates[$adapterName] = $item;
        }
        parent::__construct($nodeAggregates);
    }

    /**
     * @param mixed $key
     * @return ReadableNodeAggregateInterface|false
     */
    public function offsetGet($key)
    {
        return parent::offsetGet($key);
    }

    /**
     * @return array|ReadableNodeAggregateInterface[]
     */
    public function getArrayCopy(): array
    {
        return parent::getArrayCopy();
    }

    /**
     * @return \ArrayIterator|ReadableNodeAggregateInterface[]
     */
    public function getIterator(): \ArrayIterator
    {
        return parent::getIterator();
    }
}

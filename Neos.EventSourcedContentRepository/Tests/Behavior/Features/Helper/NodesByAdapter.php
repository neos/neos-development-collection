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
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * An immutable, type-safe collection of NodeInterface objects, indexed by content graph adapter
 * @Flow\Proxy(false)
 */
final class NodesByAdapter extends ImmutableArrayObject
{
    public function __construct(Iterable $collection)
    {
        $nodes = [];
        foreach ($collection as $adapterName => $item) {
            if (!$item instanceof NodeInterface) {
                throw new \InvalidArgumentException(get_class() . ' can only consist of ' . NodeInterface::class . ' objects.', 1618137807);
            }
            $nodes[$adapterName] = $item;
        }
        parent::__construct($nodes);
    }

    /**
     * @param mixed $key
     * @return NodeInterface|false
     */
    public function offsetGet($key)
    {
        return parent::offsetGet($key);
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

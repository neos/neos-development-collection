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
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The content graph repository collection, indexed by adapter package
 * @Flow\Proxy(false)
 */
final class ContentGraphs extends ImmutableArrayObject
{
    public function __construct(Iterable $collection)
    {
        $contentGraphs = [];
        foreach ($collection as $adapterName => $item) {
            if (!$item instanceof ContentGraphInterface) {
                throw new \InvalidArgumentException(get_class() . ' can only consist of ' . ContentGraphInterface::class . ' objects.', 1618130675);
            }
            $contentGraphs[$adapterName] = $item;
        }
        parent::__construct($contentGraphs);
    }

    /**
     * @param mixed $key
     * @return ContentGraphInterface|false
     */
    public function offsetGet($key)
    {
        return parent::offsetGet($key);
    }

    /**
     * @return array|ContentGraphInterface[]
     */
    public function getArrayCopy(): array
    {
        return parent::getArrayCopy();
    }

    /**
     * @return \ArrayIterator|ContentGraphInterface[]
     */
    public function getIterator(): \ArrayIterator
    {
        return parent::getIterator();
    }
}

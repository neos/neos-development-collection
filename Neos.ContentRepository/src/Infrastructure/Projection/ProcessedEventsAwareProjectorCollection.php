<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Infrastructure\Projection;

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
 * @implements \IteratorAggregate<int,ProcessedEventsAwareProjectorInterface>
 * @implements \ArrayAccess<int,ProcessedEventsAwareProjectorInterface>
 * @internal
 */
#[Flow\Proxy(false)]
final class ProcessedEventsAwareProjectorCollection implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @var array<int,ProcessedEventsAwareProjectorInterface>
     */
    private array $processedEventsAwareProjectors;

    /**
     * @var \ArrayIterator<int,ProcessedEventsAwareProjectorInterface>
     */
    private \ArrayIterator $iterator;

    /**
     * @param iterable<int,ProcessedEventsAwareProjectorInterface> $collection
     */
    public function __construct(iterable $collection)
    {
        $processedEventsAwareProjectors = [];
        foreach ($collection as $projector) {
            if (!$projector instanceof ProcessedEventsAwareProjectorInterface) {
                throw new \InvalidArgumentException(
                    'ProcessedEventsAwareProjectorCollection can only consist of '
                        . ProcessedEventsAwareProjectorInterface::class . ' objects.',
                    1616950763
                );
            }
            $processedEventsAwareProjectors[] = $projector;
        }
        $this->processedEventsAwareProjectors = $processedEventsAwareProjectors;
        $this->iterator = new \ArrayIterator($processedEventsAwareProjectors);
    }


    /**
     * @return \ArrayIterator<int,ProcessedEventsAwareProjectorInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->processedEventsAwareProjectors[$offset]);
    }

    public function offsetGet(mixed $offset): ?ProcessedEventsAwareProjectorInterface
    {
        return $this->processedEventsAwareProjectors[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class ContentGraphs.', 1643562663);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('Cannot modify immutable object of class ContentGraphs.', 1643562663);
    }
}

<?php

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\PendingChangesProjection;

use Neos\Flow\Annotations as Flow;

/**
 * Read model for a set of pending changes
 *
 * @internal !!! Still a bit unstable - might change in the future.
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<Change>
 */
final readonly class Changes implements \IteratorAggregate, \Countable
{
    /**
     * @param list<Change> $changes
     */
    private function __construct(
        private array $changes
    ) {
    }

    /**
     * @param list<Change> $changes
     */
    public static function fromArray(array $changes): self
    {
        foreach ($changes as $change) {
            if (!$change instanceof Change) {
                throw new \InvalidArgumentException(sprintf('Changes can only consist of %s instances, given: %s', Change::class, get_debug_type($change)), 1727273148);
            }
        }
        return new self($changes);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->changes;
    }

    public function count(): int
    {
        return count($this->changes);
    }
}

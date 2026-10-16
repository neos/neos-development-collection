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
 * @internal Only for consumption inside Neos. Not public api because the implementation will be refactored sooner or later: https://github.com/neos/neos-development-collection/issues/5493
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

    public static function fromItems(Change ... $changes): self
    {
        return new self(array_values($changes));
    }
    /**
     * @param list<Change> $changes
     */
    public static function fromArray(array $changes): self
    {
        return self::fromItems(...$changes);
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

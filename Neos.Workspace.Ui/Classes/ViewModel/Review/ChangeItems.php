<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\ViewModel\Review;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<ChangeItem>
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
final readonly class ChangeItems implements \IteratorAggregate, \Countable
{
    /**
     * @param array<ChangeItem> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    public static function fromItems(ChangeItem ... $items): self
    {
        return new self($items);
    }

    /**
     * @param array<ChangeItem> $items
     */
    public static function fromArray(array $items): self
    {
        return self::fromItems(...$items);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}

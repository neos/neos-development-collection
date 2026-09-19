<?php

/*
 * This file is part of the Neos.Restore.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\ViewModel\Restore;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<int,RestoreListItem>
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
final readonly class RestoreListItems implements \IteratorAggregate, \Countable
{
    /**
     * @param array<int,RestoreListItem> $items
     */
    private function __construct(
        private array $items,
    ) {
    }

    public static function create(RestoreListItem ...$items): self
    {
        return new self(array_values($items));
    }

    /**
     * @param array<RestoreListItem> $items
     */
    public static function fromArray(array $items): self
    {
        foreach ($items as $item) {
            if (!$item instanceof RestoreListItem) {
                throw new \InvalidArgumentException(sprintf('Expected instance of %s, got: %s', RestoreListItem::class, get_debug_type($item)), 1718295710);
            }
        }
        return new self($items);
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

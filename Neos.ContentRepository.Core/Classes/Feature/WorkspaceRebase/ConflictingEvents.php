<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceRebase;

/**
 * @implements \IteratorAggregate<int,ConflictingEvent>
 *
 * @api part of the exception exposed when rebasing failed
 */
final readonly class ConflictingEvents implements \IteratorAggregate, \Countable
{
    /**
     * @var array<int,ConflictingEvent>
     */
    private array $items;

    public function __construct(ConflictingEvent ...$items)
    {
        $this->items = array_values($items);
    }

    public function withAppended(ConflictingEvent $item): self
    {
        return new self(...[...$this->items, $item]);
    }

    public function first(): ?ConflictingEvent
    {
        return $this->items[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @template T
     * @param \Closure(ConflictingEvent): T $callback
     * @return list<T>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, $this->items);
    }
}

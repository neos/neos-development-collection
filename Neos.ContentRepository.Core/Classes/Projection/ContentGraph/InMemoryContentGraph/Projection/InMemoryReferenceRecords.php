<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection;

/**
 * A collection of in-memory reference records
 *
 * @implements \IteratorAggregate<InMemoryReferenceRecord>
 * @internal
 */
final class InMemoryReferenceRecords implements \IteratorAggregate
{
    /**
     * @var InMemoryReferenceRecord[]
     */
    private array $items;

    public function __construct(InMemoryReferenceRecord ...$items)
    {
        $this->items = $items;
    }

    public static function create(InMemoryReferenceRecord ...$items): self
    {
        return new self(...$items);
    }

    public function reverse(): self
    {
        return new self(...array_reverse($this->items));
    }

    /**
     * @return \Traversable<InMemoryReferenceRecord>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

/**
 * A set of Content Repository "domain events"
 *
 * @implements \IteratorAggregate<EventInterface>
 * @api
 */
final readonly class PublishedEvents implements \IteratorAggregate, \Countable
{
    /**
     * @var non-empty-array<EventInterface>
     */
    public array $items;

    private function __construct(EventInterface ...$events)
    {
        /** @var non-empty-array<EventInterface> $events */
        $this->items = $events;
    }

    /**
     * @param non-empty-array<EventInterface> $events
     * @return static
     */
    public static function fromArray(array $events): self
    {
        return new self(...$events);
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function withAppendedEvents(PublishedEvents $events): self
    {
        return new self(...$this->items, ...$events->items);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @template T
     * @param \Closure(EventInterface $event): T $callback
     * @return non-empty-list<T>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, $this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}

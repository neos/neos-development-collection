<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

/**
 * Neos high level representation of {@see \Neos\EventStore\Model\EventsForStreams}
 * With "domain" {@see EventInterface} instances.
 *
 * @implements \IteratorAggregate<int,EventsForStream>
 * @internal events are only intended to be published from core command handlers
 */
final readonly class EventsForStreams implements \IteratorAggregate, \Countable
{
    /** @param non-empty-list<EventsForStream> $items */
    private function __construct(
        public array $items
    ) {
    }

    public static function create(EventsForStream $first, EventsForStream ...$items): self
    {
        return new self([$first, ...array_values($items)]);
    }

    public function withAppended(EventsForStream $item): self
    {
        return new self([...$this->items, $item]);
    }

    public function merge(self $other): self
    {
        return new self([...$this->items, ...$other->items]);
    }

    public function toPublishedEvents(): PublishedEvents
    {
        return PublishedEvents::merge(...$this->map(
            fn (EventsForStream $eventsForStream) => $eventsForStream->events->toInnerEvents()
        ));
    }

    /**
     * @template T
     * @param \Closure(EventsForStream $eventsForStream): T $callback
     * @return list<T>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, $this->items);
    }

    public function first(): EventsForStream
    {
        return $this->items[0];
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

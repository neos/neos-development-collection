<?php

declare(strict_types=1);

namespace Neos\ContentRepository\EventStore;

/**
 * A set of Content Repository "domain events"
 *
 * @implements \IteratorAggregate<EventInterface|DecoratedEvent>
 */
final class Events implements \IteratorAggregate
{
    /**
     * @var array<EventInterface|DecoratedEvent>
     */
    private readonly array $events;

    private function __construct(EventInterface|DecoratedEvent ...$events)
    {
        $this->events = $events;
    }

    public static function with(EventInterface|DecoratedEvent $event): self
    {
        return new self($event);
    }

    /**
     * @param array<EventInterface|DecoratedEvent> $events
     * @return static
     */
    public static function fromArray(array $events): self
    {
        return new self(...$events);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->events);
    }

    /**
     * @param \Closure $callback
     * @return array<mixed>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, $this->events);
    }
}

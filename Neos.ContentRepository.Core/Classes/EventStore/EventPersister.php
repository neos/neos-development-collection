<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Events;

/**
 * Internal service to persist {@see EventInterface} with the proper normalization, and triggering the
 * projection catchup process.
 *
 * @internal
 */
final class EventPersister
{
    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly EventNormalizer $eventNormalizer,
        private readonly Projections $projections,
    ) {
    }

    /**
     * @param EventsToPublish $eventsToPublish
     * @throws ConcurrencyException in case the expectedVersion does not match
     */
    public function publishEvents(ContentRepository $contentRepository, EventsToPublish $eventsToPublish): void
    {
        if ($eventsToPublish->events->isEmpty()) {
            return;
        }
        // the following logic could also be done in an AppEventStore::commit method (being called
        // directly from the individual Command Handlers).
        $normalizedEvents = Events::fromArray(
            $eventsToPublish->events->map(fn(EventInterface|DecoratedEvent $event) => $this->normalizeEvent($event))
        );
        $this->eventStore->commit(
            $eventsToPublish->streamName,
            $normalizedEvents,
            $eventsToPublish->expectedVersion
        );
        foreach ($this->projections as $projection) {
            if ($projection instanceof WithMarkStaleInterface) {
                $projection->markStale();
            }
        }
        $contentRepository->catchUpProjections();
    }

    private function normalizeEvent(EventInterface|DecoratedEvent $event): Event
    {
        $eventId = $event instanceof DecoratedEvent && $event->eventId !== null ? $event->eventId : EventId::create();
        $eventMetadata = $event instanceof DecoratedEvent ? $event->eventMetadata : null;
        $causationId = $event instanceof DecoratedEvent ? $event->causationId : null;
        $correlationId = $event instanceof DecoratedEvent ? $event->correlationId : null;
        $event = $event instanceof DecoratedEvent ? $event->innerEvent : $event;
        return new Event(
            $eventId,
            $this->eventNormalizer->getEventType($event),
            $this->eventNormalizer->getEventData($event),
            $eventMetadata,
            $causationId,
            $correlationId,
        );
    }
}

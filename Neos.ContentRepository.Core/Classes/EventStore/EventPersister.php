<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\CommandHandler\PendingProjections;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Events;
use Neos\EventStore\Model\EventStore\CommitResult;

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
        private readonly ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger,
        private readonly EventNormalizer $eventNormalizer,
        private readonly Projections $projections,
    ) {
    }

    /**
     * @param EventsToPublish $eventsToPublish
     * @return CommandResult
     * @throws ConcurrencyException in case the expectedVersion does not match
     */
    public function publishEvents(EventsToPublish $eventsToPublish): CommandResult
    {
        if ($eventsToPublish->events->isEmpty()) {
            return CommandResult::empty();
        }
        // the following logic could also be done in an AppEventStore::commit method (being called
        // directly from the individual Command Handlers).
        $normalizedEvents = Events::fromArray(
            $eventsToPublish->events->map(fn(EventInterface|DecoratedEvent $event) => $this->normalizeEvent($event))
        );
        $commitResult = $this->eventStore->commit(
            $eventsToPublish->streamName,
            $normalizedEvents,
            $eventsToPublish->expectedVersion
        );
        // for performance reasons, we do not want to update ALL projections all the time; but instead only
        // the projections which are interested in the events from above.
        // Further details can be found in the docs of PendingProjections.
        $pendingProjections = PendingProjections::fromProjectionsAndEventsAndSequenceNumber(
            $this->projections,
            $eventsToPublish->events,
            $commitResult->highestCommittedSequenceNumber
        );

        foreach ($pendingProjections->projections as $projection) {
            if ($projection instanceof WithMarkStaleInterface) {
                $projection->markStale();
            }
        }
        $this->projectionCatchUpTrigger->triggerCatchUp($pendingProjections->projections);

        // The CommandResult can be used to block until projections are up to date.
        return new CommandResult($pendingProjections, $commitResult);
    }

    private function normalizeEvent(EventInterface|DecoratedEvent $event): Event
    {
        $eventId = $event instanceof DecoratedEvent ? $event->eventId : EventId::create();
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

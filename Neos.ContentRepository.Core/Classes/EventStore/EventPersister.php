<?php

declare(strict_types=1);

namespace Neos\ContentRepository\EventStore;

use Neos\ContentRepository\CommandHandler\CommandResult;
use Neos\ContentRepository\CommandHandler\PendingProjections;
use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\Projection\WithMarkStaleInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
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
            $normalizedEvents,
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
        if ($event instanceof DecoratedEvent) {
            $eventId = $event->eventId;
            $eventMetadata = $event->eventMetadata;
            $event = $event->innerEvent;
        } else {
            $eventId = EventId::create();
            $eventMetadata = EventMetadata::none();
        }
        return new Event(
            $eventId,
            $this->eventNormalizer->getEventType($event),
            $this->eventNormalizer->getEventData($event),
            $eventMetadata,
        );
    }
}

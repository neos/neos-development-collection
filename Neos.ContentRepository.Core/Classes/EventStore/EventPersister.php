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
use Neos\EventStore\Model\Events;

/**
 * Internal service to persist {@see EventInterface} with the proper normalization, and triggering the
 * projection catchup process.
 *
 * @internal
 */
final readonly class EventPersister
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger,
        private EventNormalizer $eventNormalizer,
        private Projections $projections,
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
            return new CommandResult();
        }
        // the following logic could also be done in an AppEventStore::commit method (being called
        // directly from the individual Command Handlers).
        $normalizedEvents = Events::fromArray(
            $eventsToPublish->events->map($this->eventNormalizer->normalize(...))
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

        $this->projectionCatchUpTrigger->triggerCatchUp($pendingProjections->projections);
        return new CommandResult();
    }
}

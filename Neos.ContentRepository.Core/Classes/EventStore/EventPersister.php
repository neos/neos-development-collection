<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryHooksFactory;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Events;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

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
        private EventNormalizer $eventNormalizer,
        private Projections $projections,
        private ContentRepositoryHooksFactory $hooksFactory,
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
            $eventsToPublish->events->map($this->eventNormalizer->normalize(...))
        );
        $commitResult = $this->eventStore->commit(
            $eventsToPublish->streamName,
            $normalizedEvents,
            $eventsToPublish->expectedVersion
        );
        $hooks = $this->hooksFactory->build($contentRepository);
        $store = new SemaphoreStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock($contentRepository->id->value . '_catchup');
        $lock->acquire(true);

        $expectedCheckpoint = SequenceNumber::fromInteger($commitResult->highestCommittedSequenceNumber->value - $eventsToPublish->events->count());

        $projectionsToUpdate = [];
        foreach ($this->projections as $projection) {
            if (!$projection->getCheckpoint()->equals($expectedCheckpoint)) {
                //throw new \RuntimeException(sprintf('Projection %s is at checkpoint %d, but was expected to be at %d', $projection::class, $projection->getCheckpoint()->value, $expectedCheckpoint->value), 1714062281);
                continue;
            }
            $projectionsToUpdate[] = $projection;
            if ($projection instanceof WithMarkStaleInterface) {
                $projection->markStale();
            }
        }

        $hooks->dispatchBeforeCatchUp();
        foreach ($this->eventStore->load($eventsToPublish->streamName)->withMinimumSequenceNumber($expectedCheckpoint->next()) as $eventEnvelope) {
            $event = $this->eventNormalizer->denormalize($eventEnvelope->event);
            $hooks->dispatchBeforeEvent($event, $eventEnvelope);
            foreach ($projectionsToUpdate as $projection) {
                $projection->apply($event, $eventEnvelope);
            }
            $hooks->dispatchAfterEvent($event, $eventEnvelope);
        }
        $hooks->dispatchAfterCatchup();
        $lock->release();
        //$contentRepository->catchUpProjections();
    }
}

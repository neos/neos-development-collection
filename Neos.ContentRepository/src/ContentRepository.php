<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository;


use Neos\ContentRepository\CommandHandler\CommandBus;
use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\CommandHandler\CommandResult;
use Neos\ContentRepository\CommandHandler\PendingProjections;
use Neos\ContentRepository\EventStore\DecoratedEvent;
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\Projection\ProjectionStateInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Events;
use Neos\EventStore\Model\EventStore\SetupResult;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\EventStore\ProvidesSetupInterface;

/**
 * Main Entry Point to the system. Encapsulates the full event-sourced Content Repository.
 *
 * Use this to:
 * - set up the necessary database tables and contents via {@see ContentRepository::setUp()}
 * - send commands to the system (to mutate state) via {@see ContentRepository::handle()}
 * - access projection state (to read state) via {@see ContentRepository::projectionState()}
 * - catch up projections via {@see ContentRepository::catchUpProjection()}
 */
final class ContentRepository
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly EventStoreInterface $eventStore,
        private readonly Projections $projections,
        private readonly EventNormalizer $eventNormalizer,
        private readonly ProjectionCatchUpTriggerInterface $projectionCatchUpTrigger,
    )
    {}

    /**
     * The only API to send commands (mutation intentions) to the system.
     *
     * The system is ASYNCHRONOUS by default, so that means the projection is not directly up to date. If you
     * need to be synchronous, call {@see CommandResult::block()} on the returned CommandResult - then the system
     * waits until the projections are up to date.
     *
     * @param CommandInterface $command
     * @return CommandResult
     */
    public function handle(CommandInterface $command): CommandResult
    {
        // the commands only calculate which events they want to have published, but do not do the publishing themselves.
        $eventsToPublish = $this->commandBus->handle($command, $this);

        // the following logic could also be done in an AppEventStore::commit method (being called directly from the individual
        // Command Handlers).
        $normalizedEvents = Events::fromArray($eventsToPublish->events->map(fn (EventInterface|DecoratedEvent $event) => $this->normalizeEvent($event)));
        $commitResult = $this->eventStore->commit($eventsToPublish->streamName, $normalizedEvents, $eventsToPublish->expectedVersion);
        // for performance reasons, we do not want to update ALL projections all the time; but instead only
        // the projections which are interested in the events from above.
        // Further details can be found in the docs of PendingProjections.
        $pendingProjections = PendingProjections::fromProjectionsAndEventsAndSequenceNumber($this->projections, $normalizedEvents, $commitResult->highestCommittedSequenceNumber);
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

    /**
     * @template T of ProjectionState
     * @param class-string<ProjectionInterface<T>> $projectionClassName
     * @return T
     */
    public function projectionState(string $projectionClassName): ProjectionStateInterface
    {
        return $this->projections->get($projectionClassName)->getState();
    }

    /**
     * @template T of ProjectionState
     * @param class-string<ProjectionInterface<T>> $projectionClassName
     */
    public function catchUpProjection(string $projectionClassName): void
    {
        $projection = $this->projections->get($projectionClassName);
        // TODO allow custom stream name per projection
        $streamName = VirtualStreamName::all();
        $eventStream = $this->eventStore->load($streamName);
        $projection->catchUp($eventStream);
    }

    public function setUp(): SetupResult
    {
        if ($this->eventStore instanceof ProvidesSetupInterface) {
            $result = $this->eventStore->setup();
            // TODO better result object
            if ($result->errors !== []) {
                return $result;
            }
        }
        foreach ($this->projections as $projection) {
            $projection->setUp();
        }
        return SetupResult::success('done');
    }

    public function resetProjectionStates(): void
    {
        foreach ($this->projections as $projection) {
            $projection->reset();
        }
    }

    /** TODO  public function getNodeTypeManager() */
    /** TODO  public function getContentGraph() */
}

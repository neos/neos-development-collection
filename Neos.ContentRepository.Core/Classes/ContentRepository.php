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

namespace Neos\ContentRepository\Core;

use Neos\ContentRepository\Core\CommandHandler\CommandBus;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventPersister;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentStream\ContentStreamFinder;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Projection\ProjectionStatuses;
use Neos\ContentRepository\Core\Projection\ProjectionStatusType;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryStatus;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Psr\Clock\ClockInterface;

/**
 * Main Entry Point to the system. Encapsulates the full event-sourced Content Repository.
 *
 * Use this to:
 * - set up the necessary database tables and contents via {@see ContentRepository::setUp()}
 * - send commands to the system (to mutate state) via {@see ContentRepository::handle()}
 * - access projection state (to read state) via {@see ContentRepository::projectionState()}
 *
 * @api
 */
final class ContentRepository
{
    /**
     * @var array<class-string<ProjectionStateInterface>, ProjectionStateInterface>
     */
    private array $projectionStateCache;


    /**
     * @internal use the {@see ContentRepositoryFactory::getOrBuild()} to instantiate
     */
    public function __construct(
        public readonly ContentRepositoryId $id,
        private readonly CommandBus $commandBus,
        private readonly EventStoreInterface $eventStore,
        private readonly Projections $projections,
        private readonly EventPersister $eventPersister,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly InterDimensionalVariationGraph $variationGraph,
        private readonly ContentDimensionSourceInterface $contentDimensionSource,
        private readonly UserIdProviderInterface $userIdProvider,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * The only API to send commands (mutation intentions) to the system.
     * @return object NOTE: This is just a b/c layer to avoid `handle()->block()` from failing but this will change to void with the final release!
     */
    public function handle(CommandInterface $command): object
    {
        #print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));exit;
        #\Neos\Flow\var_dump('HANDLE ' . $command::class);
        // the commands only calculate which events they want to have published, but do not do the
        // publishing themselves
        $eventsToPublish = $this->commandBus->handle($command, $this);

        // TODO meaningful exception message
        $initiatingUserId = $this->userIdProvider->getUserId();
        $initiatingTimestamp = $this->clock->now()->format(\DateTimeInterface::ATOM);

        // Add "initiatingUserId" and "initiatingTimestamp" metadata to all events.
        //                        This is done in order to keep information about the _original_ metadata when an
        //                        event is re-applied during publishing/rebasing
        // "initiatingUserId": The identifier of the user that originally triggered this event. This will never
        //                     be overridden if it is set once.
        // "initiatingTimestamp": The timestamp of the original event. The "recordedAt" timestamp will always be
        //                        re-created and reflects the time an event was actually persisted in a stream,
        // the "initiatingTimestamp" will be kept and is never overridden again.
        // TODO: cleanup
        $eventsToPublish = new EventsToPublish(
            $eventsToPublish->streamName,
            Events::fromArray(
                $eventsToPublish->events->map(function (EventInterface|DecoratedEvent $event) use (
                    $initiatingUserId,
                    $initiatingTimestamp
                ) {
                    $metadata = $event instanceof DecoratedEvent ? $event->eventMetadata?->value ?? [] : [];
                    $metadata['initiatingUserId'] ??= $initiatingUserId;
                    $metadata['initiatingTimestamp'] ??= $initiatingTimestamp;
                    return DecoratedEvent::create($event, metadata: EventMetadata::fromArray($metadata));
                })
            ),
            $eventsToPublish->expectedVersion,
        );

        $this->eventPersister->publishEvents($this, $eventsToPublish);
        return new class {
            /**
             * @deprecated backwards compatibility layer
             */
            public function block(): void
            {
            }
        };
    }


    /**
     * @template T of ProjectionStateInterface
     * @param class-string<T> $projectionStateClassName
     * @return T
     */
    public function projectionState(string $projectionStateClassName): ProjectionStateInterface
    {
        if (isset($this->projectionStateCache[$projectionStateClassName])) {
            /** @var T $projectionState */
            $projectionState = $this->projectionStateCache[$projectionStateClassName];
            return $projectionState;
        }
        foreach ($this->projections as $projection) {
            $projectionState = $projection->getState();
            if ($projectionState instanceof $projectionStateClassName) {
                $this->projectionStateCache[$projectionStateClassName] = $projectionState;
                return $projectionState;
            }
        }
        throw new \InvalidArgumentException(sprintf('A projection state of type "%s" is not registered in this content repository instance.', $projectionStateClassName), 1662033650);
    }

    public function setUp(): void
    {
        $this->eventStore->setup();
        foreach ($this->projections as $projection) {
            $projection->setUp();
        }
    }

    public function status(): ContentRepositoryStatus
    {
        $projectionStatuses = ProjectionStatuses::create();
        $expectedCheckpoint = SequenceNumber::none();
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($this->eventStore->load(VirtualStreamName::all())->backwards()->limit(1) as $eventEnvelope) {
            $expectedCheckpoint = $eventEnvelope->sequenceNumber;
            break;
        }
        foreach ($this->projections as $projectionClassName => $projection) {
            $projectionStatus = $projection->status();
            if ($projectionStatus->type === ProjectionStatusType::OK) {
                $projectionCheckpoint = $projection->getCheckpoint();
                if (!$projectionCheckpoint->equals($expectedCheckpoint)) {
                    $projectionStatus = ProjectionStatus::catchupRequired(sprintf('projection is at checkpoint %d of %d', $projectionCheckpoint->value, $expectedCheckpoint->value));
                }
            }
            $projectionStatuses = $projectionStatuses->with($projectionClassName, $projectionStatus);
        }
        return new ContentRepositoryStatus(
            $this->eventStore->status(),
            $projectionStatuses,
        );
    }

    public function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }

    public function getContentGraph(): ContentGraphInterface
    {
        return $this->projectionState(ContentGraphInterface::class);
    }

    public function getWorkspaceFinder(): WorkspaceFinder
    {
        return $this->projectionState(WorkspaceFinder::class);
    }

    public function getContentStreamFinder(): ContentStreamFinder
    {
        return $this->projectionState(ContentStreamFinder::class);
    }

    public function getVariationGraph(): InterDimensionalVariationGraph
    {
        return $this->variationGraph;
    }

    public function getContentDimensionSource(): ContentDimensionSourceInterface
    {
        return $this->contentDimensionSource;
    }
}

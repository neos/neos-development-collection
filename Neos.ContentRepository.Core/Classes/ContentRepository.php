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
use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventPersister;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentStream\ContentStreamFinder;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionsAndCatchUpHooks;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\EventStore\CatchUp\CatchUp;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStore\SetupResult;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\EventStore\ProvidesSetupInterface;
use Psr\Clock\ClockInterface;

/**
 * Main Entry Point to the system. Encapsulates the full event-sourced Content Repository.
 *
 * Use this to:
 * - set up the necessary database tables and contents via {@see ContentRepository::setUp()}
 * - send commands to the system (to mutate state) via {@see ContentRepository::handle()}
 * - access projection state (to read state) via {@see ContentRepository::projectionState()}
 * - catch up projections via {@see ContentRepository::catchUpProjection()}
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
        private readonly ProjectionsAndCatchUpHooks $projectionsAndCatchUpHooks,
        private readonly EventNormalizer $eventNormalizer,
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
                    $metadata = $event instanceof DecoratedEvent ? $event->eventMetadata->value : [];
                    $metadata['initiatingUserId'] ??= $initiatingUserId;
                    $metadata['initiatingTimestamp'] ??= $initiatingTimestamp;
                    return DecoratedEvent::withMetadata($event, EventMetadata::fromArray($metadata));
                })
            ),
            $eventsToPublish->expectedVersion,
        );

        return $this->eventPersister->publishEvents($eventsToPublish);
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
        foreach ($this->projectionsAndCatchUpHooks->projections as $projection) {
            $projectionState = $projection->getState();
            if ($projectionState instanceof $projectionStateClassName) {
                $this->projectionStateCache[$projectionStateClassName] = $projectionState;
                return $projectionState;
            }
        }
        throw new \InvalidArgumentException(sprintf('a projection state of type "%s" is not registered in this content repository instance.', $projectionStateClassName), 1662033650);
    }

    /**
     * @param class-string<ProjectionInterface<ProjectionStateInterface>> $projectionClassName
     */
    public function catchUpProjection(string $projectionClassName, CatchUpOptions $options): void
    {
        $projection = $this->projectionsAndCatchUpHooks->projections->get($projectionClassName);

        $catchUpHookFactory = $this->projectionsAndCatchUpHooks->getCatchUpHookFactoryForProjection($projection);
        $catchUpHook = $catchUpHookFactory?->build($this);

        // TODO allow custom stream name per projection
        $streamName = VirtualStreamName::all();
        $eventStream = $this->eventStore->load($streamName);
        if ($options->maximumSequenceNumber !== null) {
            //$eventStream = $eventStream->withMaximumSequenceNumber($options->maximumSequenceNumber);
        }

        $eventApplier = function (EventEnvelope $eventEnvelope) use ($projection, $catchUpHook, $options) {
            $event = $this->eventNormalizer->denormalize($eventEnvelope->event);
            if ($options->progressCallback !== null) {
                ($options->progressCallback)($event, $eventEnvelope);
            }
            if (!$projection->canHandle($event)) {
                return;
            }
            $catchUpHook?->onBeforeEvent($event, $eventEnvelope);
            $projection->apply($event, $eventEnvelope);
            $catchUpHook?->onAfterEvent($event, $eventEnvelope);
        };

        $catchUp = CatchUp::create($eventApplier, $projection->getCheckpointStorage());

        if ($catchUpHook !== null) {
            $catchUpHook->onBeforeCatchUp();
            $catchUp = $catchUp->withOnBeforeBatchCompleted(fn() => $catchUpHook->onBeforeBatchCompleted());
        }
        $catchUp->run($eventStream);
        $catchUpHook?->onAfterCatchUp();
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
        foreach ($this->projectionsAndCatchUpHooks->projections as $projection) {
            $projection->setUp();
        }
        return SetupResult::success('done');
    }

    public function resetProjectionStates(): void
    {
        foreach ($this->projectionsAndCatchUpHooks->projections as $projection) {
            $projection->reset();
        }
    }

    /**
     * @param class-string<ProjectionInterface<ProjectionStateInterface>> $projectionClassName
     */
    public function resetProjectionState(string $projectionClassName): void
    {
        $projection = $this->projectionsAndCatchUpHooks->projections->get($projectionClassName);
        $projection->reset();
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

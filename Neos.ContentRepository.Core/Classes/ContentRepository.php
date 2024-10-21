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
use Neos\ContentRepository\Core\Projection\CatchUp;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraphProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionsAndCatchUpHooks;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatuses;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryStatus;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreams;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
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

    private CommandHandlingDependencies $commandHandlingDependencies;

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
        private readonly ContentGraphReadModelInterface $contentGraphReadModel
    ) {
        $this->commandHandlingDependencies = new CommandHandlingDependencies($this, $this->contentGraphReadModel);
    }

    /**
     * The only API to send commands (mutation intentions) to the system.
     *
     * @param CommandInterface $command
     */
    public function handle(CommandInterface $command): void
    {
        // the commands only calculate which events they want to have published, but do not do the
        // publishing themselves
        $eventsToPublish = $this->commandBus->handle($command, $this->commandHandlingDependencies);

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

        $this->eventPersister->publishEvents($eventsToPublish);
    }


    /**
     * @template T of ProjectionStateInterface
     * @param class-string<T> $projectionStateClassName
     * @return T
     */
    public function projectionState(string $projectionStateClassName): ProjectionStateInterface
    {
        if (!isset($this->projectionStateCache)) {
            foreach ($this->projectionsAndCatchUpHooks->projections as $projection) {
                if ($projection instanceof ContentGraphProjectionInterface) {
                    continue;
                }
                $projectionState = $projection->getState();
                $this->projectionStateCache[$projectionState::class] = $projectionState;
            }
        }
        if (isset($this->projectionStateCache[$projectionStateClassName])) {
            /** @var T $projectionState */
            $projectionState = $this->projectionStateCache[$projectionStateClassName];
            return $projectionState;
        }
        if (in_array(ContentGraphReadModelInterface::class, class_implements($projectionStateClassName), true)) {
            throw new \InvalidArgumentException(sprintf('Accessing the internal content repository projection state via %s(%s) is not allowed. Please use the API on the content repository instead.', __FUNCTION__, $projectionStateClassName), 1729338679);
        }

        throw new \InvalidArgumentException(sprintf('A projection state of type "%s" is not registered in this content repository instance.', $projectionStateClassName), 1662033650);
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
            $eventStream = $eventStream->withMaximumSequenceNumber($options->maximumSequenceNumber);
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
            if ($projection instanceof WithMarkStaleInterface) {
                $projection->markStale();
            }
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

    public function setUp(): void
    {
        $this->eventStore->setup();
        foreach ($this->projectionsAndCatchUpHooks->projections as $projection) {
            $projection->setUp();
        }
    }

    public function status(): ContentRepositoryStatus
    {
        $projectionStatuses = ProjectionStatuses::create();
        foreach ($this->projectionsAndCatchUpHooks->projections as $projectionClassName => $projection) {
            $projectionStatuses = $projectionStatuses->with($projectionClassName, $projection->status());
        }
        return new ContentRepositoryStatus(
            $this->eventStore->status(),
            $projectionStatuses,
        );
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

    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     */
    public function getContentGraph(WorkspaceName $workspaceName): ContentGraphInterface
    {
        $workspace = $this->contentGraphReadModel->findWorkspaceByName($workspaceName);
        if ($workspace === null) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }
        return $this->contentGraphReadModel->buildContentGraph($workspaceName, $workspace->currentContentStreamId);
    }

    /**
     * Returns the workspace with the given name, or NULL if it does not exist in this content repository
     */
    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        return $this->contentGraphReadModel->findWorkspaceByName($workspaceName);
    }

    /**
     * Returns all workspaces of this content repository. To limit the set, {@see Workspaces::find()} and {@see Workspaces::filter()} can be used
     * as well as {@see Workspaces::getBaseWorkspaces()} and {@see Workspaces::getDependantWorkspaces()}.
     */
    public function findWorkspaces(): Workspaces
    {
        return $this->contentGraphReadModel->findWorkspaces();
    }

    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream
    {
        return $this->contentGraphReadModel->findContentStreamById($contentStreamId);
    }

    public function findContentStreams(): ContentStreams
    {
        return $this->contentGraphReadModel->findContentStreams();
    }

    public function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
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

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
use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\Events as DomainEvents;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\EventStore\InitiatingEventMetadata;
use Neos\ContentRepository\Core\EventStore\PublishedEvents;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Feature\Security\Exception\AccessDenied;
use Neos\ContentRepository\Core\Infrastructure\PerformanceTracing\PerformanceTracerInterface;
use Neos\ContentRepository\Core\Infrastructure\PerformanceTracing\TracePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStates;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\Core\Subscription\Exception\CatchUpHadErrors;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Events;
use Neos\EventStore\Model\EventsForCommit;
use Psr\Clock\ClockInterface;

/**
 * Main Entry Point to the system. Encapsulates the full event-sourced Content Repository.
 *
 * Use this to:
 * - send commands to the system (to mutate state) via {@see self::handle()}
 * - access the content graph read model
 * - access 3rd party read models via {@see self::projectionState()}
 *
 * @api
 */
final class ContentRepository
{
    /**
     * @internal use the {@see ContentRepositoryFactory::getOrBuild()} to instantiate
     */
    public function __construct(
        public readonly ContentRepositoryId $id,
        private readonly CommandBus $commandBus,
        private readonly EventStoreInterface $eventStore,
        private readonly EventNormalizer $eventNormalizer,
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly InterDimensionalVariationGraph $variationGraph,
        private readonly ContentDimensionSourceInterface $contentDimensionSource,
        private readonly AuthProviderInterface $authProvider,
        private readonly ClockInterface $clock,
        private readonly ContentGraphReadModelInterface $contentGraphReadModel,
        private readonly CommandHookInterface $commandHook,
        private readonly ProjectionStates $projectionStates,
        private readonly ?PerformanceTracerInterface $performanceTracer,
    ) {
    }

    /**
     * The only API to send commands (mutation intentions) to the system.
     *
     * @param CommandInterface $command
     * @throws AccessDenied
     */
    public function handle(CommandInterface $command): void
    {
        $this->performanceTracer?->openSpan(TracePoint::ContentRepositoryHandle, ['c' => get_class($command)]);
        try {
            $command = $this->commandHook->onBeforeHandle($command);
            $this->performanceTracer?->mark(TracePoint::CommandHookOnBeforeHandle);

            $privilege = $this->authProvider->canExecuteCommand($command);
            $this->performanceTracer?->mark(TracePoint::AuthProviderCanExecuteCommand);
            if (!$privilege->granted) {
                throw AccessDenied::becauseCommandIsNotGranted($command, $privilege->getReason());
            }
            $toPublish = $this->commandBus->handle($command);
            $this->performanceTracer?->mark(TracePoint::CommandBusHandle);

            $correlationId = CorrelationId::fromString(sprintf('%s_%s', substr($command::class, strrpos($command::class, '\\') + 1, 20), bin2hex(random_bytes(9))));

            // simple case
            if ($toPublish instanceof EventsToPublish) {
                $this->eventStore->commit($toPublish->streamName, $this->enrichAndNormalizeEvents($toPublish->events, $correlationId), $toPublish->expectedVersion);
                $this->performanceTracer?->mark(TracePoint::EventStoreCommit);
                $fullCatchUpResult = $this->subscriptionEngine->catchUpActive(); // NOTE: we don't batch here, to ensure the catchup is run completely and any errors don't stop it.
                // SubscriptionEngine is tracing automatically; so we do not need to add this here
                if ($fullCatchUpResult->hadErrors()) {
                    throw CatchUpHadErrors::createFromErrors($fullCatchUpResult->errors);
                }
                $additionalCommands = $this->commandHook->onAfterHandle($command, $toPublish->events->toInnerEvents());
                foreach ($additionalCommands as $additionalCommand) {
                    $this->handle($additionalCommand);
                }
                $this->performanceTracer?->mark(TracePoint::CommandHookOnAfterHandle);
                return;
            }

            // control-flow aware command handling via generator
            $eventsToPublishToStreams = []; // TODO introduce eventBus?
            foreach ($toPublish as $eventsToPublish) {
                $eventsToPublishToStreams[] = $eventsToPublish;
            }

            $expectedVersionForStreamsMap = [];

            $commit = array_reduce(
                $eventsToPublishToStreams,
                function (?EventsForCommit $commit, EventsToPublish $eventsToPublish) use ($correlationId, &$expectedVersionForStreamsMap): EventsForCommit {
                    if (array_key_exists($eventsToPublish->streamName->value, $expectedVersionForStreamsMap)) {
                        assert($commit !== null);
                        return $commit->withEventsForStream(
                            streamName: $eventsToPublish->streamName,
                            events: $this->enrichAndNormalizeEvents($eventsToPublish->events, $correlationId),
                        );
                    }

                    $expectedVersionForStreamsMap[$eventsToPublish->streamName->value] = true;

                    return ($commit ? $commit->withEventsForStreamAndExpectedVersion(...) : EventsForCommit::createEventsForStreamAndExpectedVersion(...))(
                        streamName: $eventsToPublish->streamName,
                        events: $this->enrichAndNormalizeEvents($eventsToPublish->events, $correlationId),
                        expectedVersion: $eventsToPublish->expectedVersion
                    );
                },
            );

            if ($eventsToPublishToStreams === [] || $commit === null) {
                throw new \RuntimeException(sprintf('TODO Cannot publish nothing'), 1778939145);
            }

            $this->eventStore->commitAll($commit);
            $publishedEvents = PublishedEvents::merge(...array_map(
                fn (EventsToPublish $eventsToPublish) => $eventsToPublish->events->toInnerEvents(),
                $eventsToPublishToStreams
            ));
            $this->performanceTracer?->mark(TracePoint::EventStoreCommit, ['cnt' => $publishedEvents->count()]);

            // We always NEED to catchup even if there was an unexpected ConcurrencyException to make sure previous commits are handled.
            // Technically it would be acceptable for the catchup to fail here (due to hook errors) because all the events are already persisted.
            $fullCatchUpResult = $this->subscriptionEngine->catchUpActive(); // NOTE: we don't batch here, to ensure the catchup is run completely and any errors don't stop it.
            $this->performanceTracer?->mark(TracePoint::SubscriptionEngineCatchUpActive);
            if ($fullCatchUpResult->hadErrors()) {
                throw CatchUpHadErrors::createFromErrors($fullCatchUpResult->errors);
            }
            $additionalCommands = $this->commandHook->onAfterHandle($command, $publishedEvents);
            foreach ($additionalCommands as $additionalCommand) {
                $this->handle($additionalCommand);
            }
            $this->performanceTracer?->mark(TracePoint::CommandHookOnAfterHandle);
        } finally {
            $this->performanceTracer?->closeSpan();
        }
    }


    /**
     * @template T of ProjectionStateInterface
     * @param class-string<T> $projectionStateClassName
     * @return T
     */
    public function projectionState(string $projectionStateClassName): ProjectionStateInterface
    {
        return $this->projectionStates->get($projectionStateClassName);
    }

    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     * @throws AccessDenied if no read access is granted to the workspace ({@see AuthProviderInterface})
     */
    public function getContentGraph(WorkspaceName $workspaceName): ContentGraphInterface
    {
        $privilege = $this->authProvider->canReadNodesFromWorkspace($workspaceName);
        if (!$privilege->granted) {
            throw AccessDenied::becauseWorkspaceCantBeRead($workspaceName, $privilege->getReason());
        }
        return $this->contentGraphReadModel->getContentGraph($workspaceName);
    }

    /**
     * Main API to retrieve a content subgraph, taking VisibilityConstraints of the current user
     * into account ({@see AuthProviderInterface::getVisibilityConstraints()})
     *
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     * @throws AccessDenied if no read access is granted to the workspace ({@see AuthProviderInterface})
     */
    public function getContentSubgraph(WorkspaceName $workspaceName, DimensionSpacePoint $dimensionSpacePoint): ContentSubgraphInterface
    {
        $contentGraph = $this->getContentGraph($workspaceName);
        $visibilityConstraints = $this->authProvider->getVisibilityConstraints($workspaceName);
        return $contentGraph->getSubgraph($dimensionSpacePoint, $visibilityConstraints);
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
     * as well as {@see Workspaces::getBaseWorkspaces()} and {@see Workspaces::getDependantWorkspacesRecursively()}.
     */
    public function findWorkspaces(): Workspaces
    {
        return $this->contentGraphReadModel->findWorkspaces();
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

    private function enrichAndNormalizeEvents(DomainEvents $events, CorrelationId $correlationId): Events
    {
        $initiatingUserId = $this->authProvider->getAuthenticatedUserId() ?? UserId::forSystemUser();
        $initiatingTimestamp = $this->clock->now();

        $eventsWithMetaData = InitiatingEventMetadata::enrichEventsWithInitiatingMetadata(
            $events,
            $initiatingUserId,
            $initiatingTimestamp
        );

        return Events::fromArray($eventsWithMetaData->map(function (EventInterface|DecoratedEvent $event) use ($correlationId) {
            $decoratedEvent = DecoratedEvent::create($event, correlationId: $correlationId);
            return $this->eventNormalizer->normalize($decoratedEvent);
        }));
    }
}

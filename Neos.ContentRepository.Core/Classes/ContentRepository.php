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
use Neos\ContentRepository\Core\EventStore\EventAugmenter;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
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
        private readonly EventAugmenter $eventAugmenter,
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly InterDimensionalVariationGraph $variationGraph,
        private readonly ContentDimensionSourceInterface $contentDimensionSource,
        private readonly AuthProviderInterface $authProvider,
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
            $eventsToPublish = $this->commandBus->handle($command);
            $this->performanceTracer?->mark(TracePoint::CommandBusHandle);

            $correlationId = $this->eventAugmenter->correlationIdForCommandClass($command::class);

            $this->eventStore->commitAll($this->eventAugmenter->augmentAndNormalizeEventsToPublish($eventsToPublish, $correlationId));

            $publishedEvents = $eventsToPublish->eventsForStreams->toPublishedEvents();
            $this->performanceTracer?->mark(TracePoint::EventStoreCommit, ['cnt' => $publishedEvents->count()]);
            $fullCatchUpResult = $this->subscriptionEngine->catchUpActive(); // NOTE: we don't batch here, to ensure the catchup is run completely and any errors don't stop it.
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
}

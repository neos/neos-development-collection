<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\CatchUpHook;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Neos\AssetUsage\Service\AssetUsageIndexingService;

/**
 * @internal
 */
final class AssetUsageCatchUpHook implements CatchUpHookInterface
{
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly ContentGraphReadModelInterface $contentGraphReadModel,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly AssetUsageIndexingService $assetUsageIndexingService
    ) {
    }

    public function onBeforeCatchUp(SubscriptionStatus $subscriptionStatus): void
    {
        if ($subscriptionStatus === SubscriptionStatus::BOOTING) {
            // FIXME WIP just disabled for now in the Postgres Adapter development process until the table/feature is added.
            //$this->assetUsageIndexingService->pruneIndex($this->contentRepositoryId);
        }
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        match ($eventInstance::class) {
            NodeAggregateWasRemoved::class => $this->removeNodes($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->affectedCoveredDimensionSpacePoints),
            default => null
        };
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        // FIXME WIP just disabled for now in the Postgres Adapter development process until the table/feature is added.
        return;
        // Note that we don't need to update the index for WorkspaceWasPublished, as updateNode will be invoked already with the published node and then clean up its previous usages in nested workspaces
        match ($eventInstance::class) {
            NodeAggregateWithNodeWasCreated::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->originDimensionSpacePoint->toDimensionSpacePoint()),
            NodePeerVariantWasCreated::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->peerOrigin->toDimensionSpacePoint()),
            NodeGeneralizationVariantWasCreated::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->generalizationOrigin->toDimensionSpacePoint()),
            NodeSpecializationVariantWasCreated::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->specializationOrigin->toDimensionSpacePoint()),
            NodePropertiesWereSet::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->originDimensionSpacePoint->toDimensionSpacePoint()),
            WorkspaceWasDiscarded::class => $this->discardWorkspace($eventInstance->getWorkspaceName()),
            DimensionSpacePointWasMoved::class => $this->updateDimensionSpacePoint($eventInstance->getWorkspaceName(), $eventInstance->source, $eventInstance->target),
            // because we don't know which changes were discarded in a conflict, we discard all changes and will build up the index on succeeding calls (with the kept reapplied events)
            WorkspaceWasRebased::class => $eventInstance->hasSkippedEvents() && $this->discardWorkspace($eventInstance->getWorkspaceName()),
            default => null
        };
    }

    public function onAfterBatchCompleted(): void
    {
    }

    public function onAfterCatchUp(): void
    {
    }

    private function updateNode(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, DimensionSpacePoint $dimensionSpacePoint): void
    {
        $contentGraph = $this->contentGraphReadModel->getContentGraph($workspaceName);
        $node = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::createEmpty())->findNodeById($nodeAggregateId);

        if ($node === null) {
            // Node not found, nothing to do here.
            return;
        }

        $nodeType = $this->nodeTypeManager->getNodeType($node->nodeTypeName);
        if ($nodeType === null) {
            return;
        }

        $this->assetUsageIndexingService->updateIndex(
            $this->contentRepositoryId,
            $node,
            $nodeType,
            $this->contentGraphReadModel->findWorkspaces()
        );
    }

    private function removeNodes(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $dimensionSpacePoints): void
    {
        $contentGraph = $this->contentGraphReadModel->getContentGraph($workspaceName);

        foreach ($dimensionSpacePoints as $dimensionSpacePoint) {
            $this->assetUsageIndexingService->removeIndexForWorkspaceNameNodeAggregateIdAndDimensionSpacePoint(
                $this->contentRepositoryId,
                $workspaceName,
                $nodeAggregateId,
                $dimensionSpacePoint
            );

            $subgraph = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::createEmpty());
            $descendants = $subgraph->findDescendantNodes($nodeAggregateId, FindDescendantNodesFilter::create());

            /** @var Node $descendant */
            foreach ($descendants as $descendant) {
                $this->assetUsageIndexingService->removeIndexForWorkspaceNameNodeAggregateIdAndDimensionSpacePoint(
                    $this->contentRepositoryId,
                    $descendant->workspaceName,
                    $descendant->aggregateId,
                    $descendant->dimensionSpacePoint
                );
            }
        }
    }

    private function discardWorkspace(WorkspaceName $workspaceName): void
    {
        $this->assetUsageIndexingService->removeIndexForWorkspace($this->contentRepositoryId, $workspaceName);
    }

    private function updateDimensionSpacePoint(WorkspaceName $workspaceName, DimensionSpacePoint $source, DimensionSpacePoint $target): void
    {
        $this->assetUsageIndexingService->updateDimensionSpacePointInIndex($this->contentRepositoryId, $workspaceName, $source, $target);
    }
}

<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\CatchUpHook;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Neos\AssetUsage\Service\AssetUsageIndexingService;

/**
 * @internal
 */
class AssetUsageCatchUpHook implements CatchUpHookInterface
{
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly ContentGraphReadModelInterface $contentGraphReadModel,
        private readonly AssetUsageIndexingService $assetUsageIndexingService
    ) {
    }

    public function onBeforeCatchUp(): void
    {
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        if ($eventInstance instanceof EmbedsWorkspaceName) {
            try {
                // Skip if the workspace does not exist: "The source workspace missing does not exist" https://github.com/neos/neos-development-collection/pull/5270
                $this->contentGraphReadModel->getContentGraph($eventInstance->getWorkspaceName());
            } catch (WorkspaceDoesNotExist) {
                return;
            }
        }

        match ($eventInstance::class) {
            NodeAggregateWasRemoved::class => $this->removeNodes($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->affectedCoveredDimensionSpacePoints),
            WorkspaceWasPartiallyDiscarded::class => $this->discardNodes($eventInstance->getWorkspaceName(), $eventInstance->discardedNodes),
            default => null
        };
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        if ($eventInstance instanceof EmbedsWorkspaceName) {
            try {
                // Skip if the workspace does not exist: "The source workspace missing does not exist" https://github.com/neos/neos-development-collection/pull/5270
                $this->contentGraphReadModel->getContentGraph($eventInstance->getWorkspaceName());
            } catch (WorkspaceDoesNotExist) {
                return;
            }
        }

        match ($eventInstance::class) {
            NodeAggregateWithNodeWasCreated::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->originDimensionSpacePoint->toDimensionSpacePoint()),
            NodePeerVariantWasCreated::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->peerOrigin->toDimensionSpacePoint()),
            NodeGeneralizationVariantWasCreated::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->generalizationOrigin->toDimensionSpacePoint()),
            NodeSpecializationVariantWasCreated::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->specializationOrigin->toDimensionSpacePoint()),
            NodePropertiesWereSet::class => $this->updateNode($eventInstance->getWorkspaceName(), $eventInstance->nodeAggregateId, $eventInstance->originDimensionSpacePoint->toDimensionSpacePoint()),
            WorkspaceWasDiscarded::class => $this->discardWorkspace($eventInstance->getWorkspaceName()),
            DimensionSpacePointWasMoved::class => $this->updateDimensionSpacePoint($eventInstance->getWorkspaceName(), $eventInstance->source, $eventInstance->target),
            default => null
        };
    }


    public function onBeforeBatchCompleted(): void
    {
    }

    public function onAfterCatchUp(): void
    {
    }

    private function updateNode(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, DimensionSpacePoint $dimensionSpacePoint): void
    {
        $contentGraph = $this->contentGraphReadModel->getContentGraph($workspaceName);
        $node = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::withoutRestrictions())->findNodeById($nodeAggregateId);

        if ($node === null) {
            // Node not found, nothing to do here.
            return;
        }

        $this->assetUsageIndexingService->updateIndex(
            $this->contentRepositoryId,
            $node
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

            $subgraph = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
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

    private function discardNodes(WorkspaceName $workspaceName, NodeIdsToPublishOrDiscard $nodeIds): void
    {
        foreach ($nodeIds as $nodeId) {
            if (!$nodeId->dimensionSpacePoint) {
                // NodeAggregateTypeWasChanged and NodeAggregateNameWasChanged don't impact asset usage
                continue;
            }
            $this->assetUsageIndexingService->removeIndexForWorkspaceNameNodeAggregateIdAndDimensionSpacePoint(
                $this->contentRepositoryId,
                $workspaceName,
                $nodeId->nodeAggregateId,
                $nodeId->dimensionSpacePoint
            );
        }
    }

    private function updateDimensionSpacePoint(WorkspaceName $workspaceName, DimensionSpacePoint $source, DimensionSpacePoint $target): void
    {
        $this->assetUsageIndexingService->updateDimensionSpacePointInIndex($this->contentRepositoryId, $workspaceName, $source, $target);
    }
}

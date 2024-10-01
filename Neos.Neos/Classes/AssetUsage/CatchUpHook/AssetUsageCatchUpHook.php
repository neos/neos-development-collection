<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\CatchUpHook;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
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
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
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
        private readonly ContentRepository $contentRepository,
        private readonly AssetUsageIndexingService $assetUsageIndexingService
    ) {
    }

    public function onBeforeCatchUp(): void
    {
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        if ($eventInstance instanceof EmbedsWorkspaceName && $eventInstance instanceof EmbedsContentStreamId) {
            // Safeguard for temporary content streams created during partial publish -> We want to skip these events, because their workspace doesn't match current content stream.
            try {
                $contentGraph = $this->contentRepository->getContentGraph($eventInstance->getWorkspaceName());
            } catch (WorkspaceDoesNotExist) {
                return;
            }
            if (!$contentGraph->getContentStreamId()->equals($eventInstance->getContentStreamId())) {
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
        if ($eventInstance instanceof EmbedsWorkspaceName && $eventInstance instanceof EmbedsContentStreamId) {
            // Safeguard for temporary content streams created during partial publish -> We want to skip these events, because their workspace doesn't match current content stream.
            try {
                $contentGraph = $this->contentRepository->getContentGraph($eventInstance->getWorkspaceName());
            } catch (WorkspaceDoesNotExist) {
                return;
            }
            if (!$contentGraph->getContentStreamId()->equals($eventInstance->getContentStreamId())) {
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
        $contentGraph = $this->contentRepository->getContentGraph($workspaceName);
        $node = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::withoutRestrictions())->findNodeById($nodeAggregateId);

        if ($node === null) {
            // Node not found, nothing to do here.
            return;
        }

        $this->assetUsageIndexingService->updateIndex(
            $this->contentRepository->id,
            $node
        );
    }

    private function removeNodes(WorkspaceName $workspaceName, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $dimensionSpacePoints): void
    {
        $contentGraph = $this->contentRepository->getContentGraph($workspaceName);

        foreach ($dimensionSpacePoints as $dimensionSpacePoint) {
            $subgraph = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
            $node = $subgraph->findNodeById($nodeAggregateId);
            $descendants = $subgraph->findDescendantNodes($nodeAggregateId, FindDescendantNodesFilter::create());

            $nodes = array_merge([$node], iterator_to_array($descendants));

            /** @var Node $node */
            foreach ($nodes as $node) {
                $this->assetUsageIndexingService->removeIndexForNode(
                    $this->contentRepository->id,
                    $node
                );
            }
        }
    }

    private function discardWorkspace(WorkspaceName $workspaceName): void
    {
        $this->assetUsageIndexingService->removeIndexForWorkspace($this->contentRepository->id, $workspaceName);
    }

    private function discardNodes(WorkspaceName $workspaceName, NodeIdsToPublishOrDiscard $nodeIds): void
    {
        foreach ($nodeIds as $nodeId) {
            $this->assetUsageIndexingService->removeIndexForWorkspaceNameNodeAggregateIdAndDimensionSpacePoint(
                $this->contentRepository->id,
                $workspaceName,
                $nodeId->nodeAggregateId,
                $nodeId->dimensionSpacePoint
            );
        }
    }

    private function updateDimensionSpacePoint(WorkspaceName $workspaceName, DimensionSpacePoint $source, DimensionSpacePoint $target): void
    {
        $this->assetUsageIndexingService->updateDimensionSpacePointInIndex($this->contentRepository->id, $workspaceName, $source, $target);
    }
}

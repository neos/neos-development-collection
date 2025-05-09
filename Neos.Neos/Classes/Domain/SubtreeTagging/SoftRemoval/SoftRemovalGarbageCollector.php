<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\SubtreeTagging\SoftRemoval;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Service\WorkspacePublishingService;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;

/**
 * Service that detects which soft removals in a content repository can be safely transformed to hard removals
 * and executes those.
 *
 * *Invalidation*
 *
 * Triggering of the soft removal garbage collection is done via the {@see WorkspacePublishingService}
 *
 * For these commands the contents of the workspace change and afterward there are less pending changes - e.g. less impending conflicts.
 * Which means that we can trigger the garbage collection in hope to have something cleaned up.
 *
 * - PublishWorkspace
 * - PublishIndividualNodesFromWorkspace
 * - DiscardIndividualNodesFromWorkspace
 * - DiscardWorkspace
 * - DeleteWorkspace
 * - RebaseWorkspace
 *
 * The command `RebaseWorkspace` is a special case. Because (expect for force-rebase with dropped changes) no changes are expected to be dropped
 * and thus there are no subtractions from the impending conflicts. Instead, the rebase takes care of synchronizing the new soft removals into the workspace.
 * New soft removals can either cause new impending conflicts or free the claim of the workspace as the node will no longer be visible.
 *
 * *Implementation*
 *
 * The detection/execution is done in the following steps:
 *   1) find all soft removals in the root workspace live {@see self::findNodeAggregatesInWorkspaceByExplicitRemovedTag()}
 * For each soft removal:
 *   2) check if any workspace objects to the transformation
 *       2.a) by not knowing about the soft removal yet {@see self::withVisibleInDependingWorkspacesConflicts()}
 *       2.b) by having pending changes inside the synchronized soft removal {@see self::withImpendingHardRemovalConflicts()}
 *   3) if there are no impending conflicts, execute the hard removal on the root workspace {@see self::hardRemoveNodesInDimensionsThatImposeNoConflicts}
 *   4) the hard removal will then propagate to the other workspace automatically via rebase
 *
 * *Sync vs async*
 *
 * In the future we could allow to disable the garbage collection being run synchronously but offer an async job.
 *
 * *Impediments*
 *
 * In a single user system the garbage collection will immediately turn the users workspace outdated after publishing a non-conflicting removal.
 *
 * @internal safe to run at any time, but manual runs should be unnecessary
 */
final readonly class SoftRemovalGarbageCollector
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry,
        private ImpendingHardRemovalConflictRepository $impendingConflictRepository,
        private SecurityContext $securityContext,
    ) {
    }

    /**
     * Entry point to trigger the hard removal
     */
    public function run(ContentRepositoryId $contentRepositoryId): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $this->securityContext->withoutAuthorizationChecks(function () use ($contentRepository) {
            try {
                $liveContentGraph = $contentRepository->getContentGraph(WorkspaceName::forLive());
            } catch (WorkspaceDoesNotExist) {
                // nothing to do if live doest exist
                return;
            }

            $softRemovedNodes = $this->findNodeAggregatesInWorkspaceByExplicitRemovedTag($liveContentGraph);

            $softRemovedNodes = $this->withVisibleInDependingWorkspacesConflicts($softRemovedNodes, $contentRepository);

            $softRemovedNodes = $this->withImpendingHardRemovalConflicts($softRemovedNodes, $contentRepository);

            $this->hardRemoveNodesInDimensionsThatImposeNoConflicts($softRemovedNodes, $contentRepository);
        });
    }

    /**
     * Step 1
     *
     * All soft removals that were published to live will be fetched to evaluate the hard removal
     */
    private function findNodeAggregatesInWorkspaceByExplicitRemovedTag(ContentGraphInterface $contentGraph): SoftRemovedNodes
    {
        $softRemovedNodes = [];
        foreach ($contentGraph->findNodeAggregatesTaggedBy(NeosSubtreeTag::removed()) as $nodeAggregateTaggedRemoved) {
            if (
                $nodeAggregateTaggedRemoved->classification->isRoot()
                || $nodeAggregateTaggedRemoved->classification->isTethered()
            ) {
                // root nodes and tethered nodes impose a speciality. For both kind of nodes Neos constraints the removal beforehand
                // if they are soft removed after all, we avoid handling them.
                continue;
            }
            $softRemovedNodes[] = SoftRemovedNode::create(
                $nodeAggregateTaggedRemoved->nodeAggregateId,
                $nodeAggregateTaggedRemoved->getCoveredDimensionsTaggedBy(NeosSubtreeTag::removed(), withoutInherited: true)
            );
        }
        return SoftRemovedNodes::fromArray($softRemovedNodes);
    }

    /**
     * Step 2.a
     *
     * If a workspace is outdated and does not have the soft removal yet, but knows the node we cannot safely remove the node as
     * the user can still make changes to that node or children any time in the future - or might have done so already.
     * Heavily outdated workspaces might not even know the node yet and thus impose NO conflict.
     */
    private function withVisibleInDependingWorkspacesConflicts(SoftRemovedNodes $softRemovedNodes, ContentRepository $contentRepository): SoftRemovedNodes
    {
        $workspacesDependingOnLive = $contentRepository->findWorkspaces()->getDependantWorkspacesRecursively(WorkspaceName::forLive());

        foreach ($workspacesDependingOnLive as $workspace) {
            $contentGraph = $contentRepository->getContentGraph($workspace->workspaceName);

            $nodeAggregatesInWorkspace = $contentGraph->findNodeAggregatesByIds($softRemovedNodes->toNodeAggregateIds());

            foreach ($softRemovedNodes as $softRemovedNode) {
                $nodeAggregateInWorkspace = $nodeAggregatesInWorkspace->get($softRemovedNode->nodeAggregateId);
                if ($nodeAggregateInWorkspace === null) {
                    continue;
                }
                $softDeletedDimensionsInWorkspace = $nodeAggregateInWorkspace->getCoveredDimensionsTaggedBy(NeosSubtreeTag::removed(), withoutInherited: true);
                $notSoftDeletedDimensionsInWorkspace = $nodeAggregateInWorkspace->coveredDimensionSpacePoints->getDifference($softDeletedDimensionsInWorkspace);

                $softRemovedNodes = $softRemovedNodes->with(
                    $softRemovedNode->withConflictingDimensionSpacePoints(
                        $notSoftDeletedDimensionsInWorkspace
                    )
                );
            }
        }

        return $softRemovedNodes;
    }

    /**
     * Step 2.b
     *
     * Workspace that are already synchronised with live know the soft removals. This means that all pending changes have been rebased
     * on live and at the time of the rebase each of the events was aware of soft removal. The conflicts are remembered via hook.
     */
    private function withImpendingHardRemovalConflicts(SoftRemovedNodes $softRemovedNodes, ContentRepository $contentRepository): SoftRemovedNodes
    {
        $impendingConflicts = $this->impendingConflictRepository->findAllConflicts($contentRepository->id);

        foreach ($softRemovedNodes as $softRemovedNode) {
            $impendingConflict = $impendingConflicts->get($softRemovedNode->nodeAggregateId);
            if ($impendingConflict === null) {
                continue;
            }
            $softRemovedNodes = $softRemovedNodes->with(
                $softRemovedNode->withConflictingDimensionSpacePoints(
                    $softRemovedNode->conflictingDimensionSpacePoints->getUnion($impendingConflict->dimensionSpacePointSet)
                )
            );
        }

        return $softRemovedNodes;
    }

    /**
     * Step 3
     *
     * For the dimensions a node was soft-removed we calculate the dimensions that will via STRATEGY_ALL_SPECIALIZATIONS remove exactly it.
     * If a variant that is about to be removed imposes a conflict here we will skip the hard removal.
     */
    private function hardRemoveNodesInDimensionsThatImposeNoConflicts(SoftRemovedNodes $softRemovedNodes, ContentRepository $contentRepository): void
    {
        foreach ($softRemovedNodes as $softRemovedNode) {
            // the generalisations of the non-conflicting soft removed dimensions
            $generalizationsToRemoveWithAllSpecializations = $contentRepository->getVariationGraph()->reduceSetToRelativeRoots(
                $softRemovedNode->removedDimensionSpacePoints
                    ->getDifference($softRemovedNode->conflictingDimensionSpacePoints)
            );
            foreach ($generalizationsToRemoveWithAllSpecializations as $generalization) {
                if (
                    // check if any of the affected dimensions (STRATEGY_ALL_SPECIALIZATIONS) for the $generalization
                    // impose a conflict
                    $contentRepository->getVariationGraph()->getSpecializationSet($generalization)
                        ->getIntersection($softRemovedNode->conflictingDimensionSpacePoints)
                        ->isEmpty()
                ) {
                    try {
                        $contentRepository->handle(RemoveNodeAggregate::create(
                            WorkspaceName::forLive(),
                            $softRemovedNode->nodeAggregateId,
                            $generalization,
                            NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS
                        ));
                    } catch (NodeAggregateCurrentlyDoesNotExist | NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint) {
                        // already removed by another command further up the graph
                    }
                }
            }
        }
    }
}

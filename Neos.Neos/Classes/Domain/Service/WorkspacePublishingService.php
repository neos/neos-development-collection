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

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace as ContentRepositoryWorkspace;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\DiscardingResult;
use Neos\Neos\Domain\Model\PublishingResult;
use Neos\Neos\PendingChangesProjection\Change;
use Neos\Neos\PendingChangesProjection\ChangeFinder;

/**
 * Central authority for publishing/discarding workspace changes from Neos
 *
 * @api
 */
#[Flow\Scope('singleton')]
final class WorkspacePublishingService
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {
    }

    /** @internal experimental api, until actually used by the Neos.Ui */
    public function countPendingWorkspaceChanges(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        return count($this->pendingWorkspaceChanges($contentRepository, $workspaceName));
    }


    /**
     * @throws WorkspaceDoesNotExist | WorkspaceRebaseFailed
     */
    public function rebaseWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, RebaseErrorHandlingStrategy $rebaseErrorHandlingStrategy = RebaseErrorHandlingStrategy::STRATEGY_FAIL): void
    {
        $rebaseCommand = RebaseWorkspace::create($workspaceName)->withErrorHandlingStrategy($rebaseErrorHandlingStrategy);
        $this->contentRepositoryRegistry->get($contentRepositoryId)->handle($rebaseCommand);
    }

    public function publishWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): PublishingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $crWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        if ($crWorkspace->baseWorkspaceName === null) {
            throw new \InvalidArgumentException(sprintf('Failed to publish workspace "%s" because it has no base workspace', $workspaceName->value), 1717517124);
        }
        $numberOfPendingChanges = $this->pendingWorkspaceChanges($contentRepository, $workspaceName);
        $this->contentRepositoryRegistry->get($contentRepositoryId)->handle(PublishWorkspace::create($workspaceName));
        return new PublishingResult(count($numberOfPendingChanges), $crWorkspace->baseWorkspaceName);
    }

    public function publishChangesInSite(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, NodeAggregateId $siteId): PublishingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $crWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        if ($crWorkspace->baseWorkspaceName === null) {
            throw new \InvalidArgumentException(sprintf('Failed to publish workspace "%s" because it has no base workspace', $workspaceName->value), 1717517240);
        }
        $ancestorNodeTypeName = NodeTypeNameFactory::forSite();
        $this->requireNodeToBeOfType(
            $contentRepository,
            $workspaceName,
            $siteId,
            $ancestorNodeTypeName
        );

        $nodeIdsToPublish = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $workspaceName,
            $siteId,
            $ancestorNodeTypeName
        );

        $this->publishNodes($contentRepository, $workspaceName, $nodeIdsToPublish);

        return new PublishingResult(
            count($nodeIdsToPublish),
            $crWorkspace->baseWorkspaceName,
        );
    }

    public function publishChangesInDocument(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, NodeAggregateId $documentId): PublishingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $crWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        if ($crWorkspace->baseWorkspaceName === null) {
            throw new \InvalidArgumentException(sprintf('Failed to publish workspace "%s" because it has no base workspace', $workspaceName->value), 1717517467);
        }
        $ancestorNodeTypeName = NodeTypeNameFactory::forDocument();
        $this->requireNodeToBeOfType(
            $contentRepository,
            $workspaceName,
            $documentId,
            $ancestorNodeTypeName
        );

        $nodeIdsToPublish = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $workspaceName,
            $documentId,
            $ancestorNodeTypeName
        );

        $this->publishNodes($contentRepository, $workspaceName, $nodeIdsToPublish);

        return new PublishingResult(
            count($nodeIdsToPublish),
            $crWorkspace->baseWorkspaceName,
        );
    }

    public function discardAllWorkspaceChanges(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): DiscardingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);

        $changesToBeDiscarded = $this->pendingWorkspaceChanges($contentRepository, $workspaceName);

        $contentRepository->handle(DiscardWorkspace::create($workspaceName));

        return new DiscardingResult(
            count($changesToBeDiscarded)
        );
    }

    public function discardChangesInSite(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, NodeAggregateId $siteId): DiscardingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        $ancestorNodeTypeName = NodeTypeNameFactory::forSite();
        $this->requireNodeToBeOfType(
            $contentRepository,
            $workspaceName,
            $siteId,
            $ancestorNodeTypeName
        );

        $nodeIdsToDiscard = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $workspaceName,
            $siteId,
            NodeTypeNameFactory::forSite()
        );

        $this->discardNodes($contentRepository, $workspaceName, $nodeIdsToDiscard);

        return new DiscardingResult(
            count($nodeIdsToDiscard)
        );
    }

    public function discardChangesInDocument(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, NodeAggregateId $documentId): DiscardingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        $ancestorNodeTypeName = NodeTypeNameFactory::forDocument();
        $this->requireNodeToBeOfType(
            $contentRepository,
            $workspaceName,
            $documentId,
            $ancestorNodeTypeName
        );

        $nodeIdsToDiscard = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $workspaceName,
            $documentId,
            $ancestorNodeTypeName
        );

        $this->discardNodes($contentRepository, $workspaceName, $nodeIdsToDiscard);

        return new DiscardingResult(
            count($nodeIdsToDiscard)
        );
    }

    public function changeBaseWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceName $newBaseWorkspaceName): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        $contentRepository->handle(
            ChangeBaseWorkspace::create(
                $workspaceName,
                $newBaseWorkspaceName,
            )
        );
    }

    private function discardNodes(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeIdsToPublishOrDiscard $nodeIdsToDiscard
    ): void {
        /**
         * TODO: only rebase if necessary!
         * Also, isn't this already included in @see WorkspaceCommandHandler::handleDiscardIndividualNodesFromWorkspace ?
         */
        $contentRepository->handle(
            RebaseWorkspace::create($workspaceName)
        );

        $contentRepository->handle(
            DiscardIndividualNodesFromWorkspace::create(
                $workspaceName,
                $nodeIdsToDiscard
            )
        );
    }

    private function publishNodes(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeIdsToPublishOrDiscard $nodeIdsToPublish
    ): void {
        /**
         * TODO: only rebase if necessary!
         * Also, isn't this already included in @see WorkspaceCommandHandler::handlePublishIndividualNodesFromWorkspace ?
         */
        $contentRepository->handle(
            RebaseWorkspace::create($workspaceName)
        );

        $contentRepository->handle(
            PublishIndividualNodesFromWorkspace::create(
                $workspaceName,
                $nodeIdsToPublish
            )
        );
    }

    private function requireContentRepositoryWorkspace(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName
    ): ContentRepositoryWorkspace {
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if (!$workspace instanceof ContentRepositoryWorkspace) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }
        return $workspace;
    }

    private function requireNodeToBeOfType(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
        NodeTypeName $nodeTypeName,
    ): void {
        $nodeAggregate = $contentRepository->getContentGraph($workspaceName)->findNodeAggregateById(
            $nodeAggregateId,
        );
        if (!$nodeAggregate instanceof NodeAggregate) {
            throw new NodeAggregateCurrentlyDoesNotExist(
                'Node aggregate ' . $nodeAggregateId->value . ' does currently not exist',
                1710967964
            );
        }

        if (
            !$contentRepository->getNodeTypeManager()
                ->getNodeType($nodeAggregate->nodeTypeName)
                ?->isOfType($nodeTypeName)
        ) {
            throw new \RuntimeException(
                sprintf('Node aggregate %s is not of expected type %s', $nodeAggregateId->value, $nodeTypeName->value),
                1710968108
            );
        }
    }

    /**
     * @param NodeAggregateId $ancestorId The id of the ancestor node of all affected nodes
     * @param NodeTypeName $ancestorNodeTypeName The type of the ancestor node of all affected nodes
     */
    private function resolveNodeIdsToPublishOrDiscard(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeAggregateId $ancestorId,
        NodeTypeName $ancestorNodeTypeName
    ): NodeIdsToPublishOrDiscard {
        $nodeIdsToPublishOrDiscard = [];
        foreach ($this->pendingWorkspaceChanges($contentRepository, $workspaceName) as $change) {
            if (
                !$this->isChangePublishableWithinAncestorScope(
                    $contentRepository,
                    $workspaceName,
                    $change,
                    $ancestorNodeTypeName,
                    $ancestorId
                )
            ) {
                continue;
            }

            $nodeIdsToPublishOrDiscard[] = new NodeIdToPublishOrDiscard(
                $change->nodeAggregateId,
                $change->originDimensionSpacePoint->toDimensionSpacePoint()
            );
        }

        return NodeIdsToPublishOrDiscard::create(...$nodeIdsToPublishOrDiscard);
    }

    /**
     * @return array<Change>
     */
    private function pendingWorkspaceChanges(ContentRepository $contentRepository, WorkspaceName $workspaceName): array
    {
        $crWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        /** @var ChangeFinder $changeFinder */
        $changeFinder = $contentRepository->projectionState(ChangeFinder::class);
        return $changeFinder->findByContentStreamId($crWorkspace->currentContentStreamId);
    }

    private function isChangePublishableWithinAncestorScope(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        Change $change,
        NodeTypeName $ancestorNodeTypeName,
        NodeAggregateId $ancestorId
    ): bool {
        // see method comment for `isChangeWithSelfReferencingRemovalAttachmentPoint`
        // to get explanation for this condition
        if ($this->isChangeWithSelfReferencingRemovalAttachmentPoint($change)) {
            if ($ancestorNodeTypeName->equals(NodeTypeNameFactory::forSite())) {
                return true;
            }
        }

        $subgraph = $contentRepository->getContentGraph($workspaceName)->getSubgraph(
            $change->originDimensionSpacePoint->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );

        // A Change is publishable if the respective node (or the respective
        // removal attachment point) has a closest ancestor that matches our
        // current ancestor scope (Document/Site)
        $actualAncestorNode = $subgraph->findClosestNode(
            $change->removalAttachmentPoint ?? $change->nodeAggregateId,
            FindClosestNodeFilter::create(nodeTypes: $ancestorNodeTypeName->value)
        );

        return $actualAncestorNode?->aggregateId->equals($ancestorId) ?? false;
    }

    /**
     * Before the introduction of the {@see WorkspacePublishingService}, the UI only ever
     * referenced the closest document node as a removal attachment point.
     *
     * Removed document nodes therefore were referencing themselves.
     *
     * In order to enable publish/discard of removed documents, the removal
     * attachment point of a document MUST refer to an ancestor. The UI now
     * references the site node in those cases.
     *
     * Workspaces that were created before this change was introduced may
     * contain removed documents, for which the site node can longer be
     * located, because we have no reference to their respective site.
     *
     * Every document node that matches that description will be published
     * or discarded by {@see WorkspacePublishingService::publishChangesInSite()}, regardless of what
     * the current site is.
     *
     * @deprecated remove once we are sure this check is no longer needed due to
     * * the UI sending proper commands
     * * the ChangeFinder being refactored / rewritten
     * (whatever happens first)
     */
    private function isChangeWithSelfReferencingRemovalAttachmentPoint(Change $change): bool
    {
        return $change->removalAttachmentPoint?->equals($change->nodeAggregateId) ?? false;
    }
}

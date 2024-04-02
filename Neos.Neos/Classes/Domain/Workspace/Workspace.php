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

namespace Neos\Neos\Domain\Workspace;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceStatus;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\PendingChangesProjection\Change;
use Neos\Neos\PendingChangesProjection\ChangeFinder;

/**
 * Neos' workspace model
 *
 * Provides a high-level API to evaluate, publish or discard changes in a given workspace.
 * Uses the low-level content repository workspace read model for information retrieval,
 * @see \Neos\ContentRepository\Core\Projection\Workspace\Workspace
 *
 * @api
 */
#[Flow\Proxy(false)]
final class Workspace
{
    public function __construct(
        public readonly WorkspaceName $name,
        private ContentStreamId $currentContentStreamId,
        private WorkspaceStatus $currentStatus,
        private ?WorkspaceName $currentBaseWorkspaceName,
        private readonly ContentRepository $contentRepository,
    ) {
    }

    public function getCurrentContentStreamId(): ContentStreamId
    {
        return $this->currentContentStreamId;
    }

    public function getCurrentStatus(): WorkspaceStatus
    {
        return $this->currentStatus;
    }

    public function getCurrentBaseWorkspaceName(): ?WorkspaceName
    {
        return $this->currentBaseWorkspaceName;
    }

    public function countAllChanges(): int
    {
        $changeFinder = $this->contentRepository->projectionState(ChangeFinder::class);
        $changes = $changeFinder->findByContentStreamId($this->currentContentStreamId);

        return count($changes);
    }

    public function countChangesInSite(NodeAggregateId $siteId): int
    {
        $ancestorNodeTypeName = NodeTypeNameFactory::forSite();
        $this->requireNodeToBeOfType(
            $siteId,
            $ancestorNodeTypeName
        );

        $changes = $this->resolveNodeIdsToPublishOrDiscard(
            $siteId,
            $ancestorNodeTypeName
        );

        return count($changes);
    }

    public function countChangesInDocument(NodeAggregateId $documentId): int
    {
        $ancestorNodeTypeName = NodeTypeNameFactory::forDocument();
        $this->requireNodeToBeOfType(
            $documentId,
            $ancestorNodeTypeName
        );

        $changes = $this->resolveNodeIdsToPublishOrDiscard(
            $documentId,
            $ancestorNodeTypeName
        );

        return count($changes);
    }

    public function publishAllChanges(): PublishingResult
    {
        $changeFinder = $this->contentRepository->projectionState(ChangeFinder::class);
        $changes = $changeFinder->findByContentStreamId($this->currentContentStreamId);

        $this->publish();

        return new PublishingResult(
            count($changes)
        );
    }

    public function publishChangesInSite(NodeAggregateId $siteId): PublishingResult
    {
        $ancestorNodeTypeName = NodeTypeNameFactory::forSite();
        $this->requireNodeToBeOfType(
            $siteId,
            $ancestorNodeTypeName
        );

        $nodeIdsToPublish = $this->resolveNodeIdsToPublishOrDiscard(
            $siteId,
            $ancestorNodeTypeName
        );

        $this->publishNodes($nodeIdsToPublish);

        return new PublishingResult(
            count($nodeIdsToPublish)
        );
    }

    public function publishChangesInDocument(NodeAggregateId $documentId): PublishingResult
    {
        $ancestorNodeTypeName = NodeTypeNameFactory::forDocument();
        $this->requireNodeToBeOfType(
            $documentId,
            $ancestorNodeTypeName
        );

        $nodeIdsToPublish = $this->resolveNodeIdsToPublishOrDiscard(
            $documentId,
            $ancestorNodeTypeName
        );

        $this->publishNodes($nodeIdsToPublish);

        return new PublishingResult(
            count($nodeIdsToPublish)
        );
    }

    public function discardAllChanges(): DiscardingResult
    {
        $changeFinder = $this->contentRepository->projectionState(ChangeFinder::class);
        $changes = $changeFinder->findByContentStreamId($this->currentContentStreamId);

        $this->discard();

        return new DiscardingResult(
            count($changes)
        );
    }

    public function discardChangesInSite(NodeAggregateId $siteId): DiscardingResult
    {
        $ancestorNodeTypeName = NodeTypeNameFactory::forSite();
        $this->requireNodeToBeOfType(
            $siteId,
            $ancestorNodeTypeName
        );

        $nodeIdsToDiscard = $this->resolveNodeIdsToPublishOrDiscard(
            $siteId,
            NodeTypeNameFactory::forSite()
        );

        $this->discardNodes($nodeIdsToDiscard);

        return new DiscardingResult(
            count($nodeIdsToDiscard)
        );
    }

    public function discardChangesInDocument(NodeAggregateId $documentId): DiscardingResult
    {
        $ancestorNodeTypeName = NodeTypeNameFactory::forDocument();
        $this->requireNodeToBeOfType(
            $documentId,
            $ancestorNodeTypeName
        );

        $nodeIdsToDiscard = $this->resolveNodeIdsToPublishOrDiscard(
            $documentId,
            $ancestorNodeTypeName
        );

        $this->discardNodes($nodeIdsToDiscard);

        return new DiscardingResult(
            count($nodeIdsToDiscard)
        );
    }

    public function rebase(bool $force): void
    {
        $rebaseCommand = RebaseWorkspace::create(
            $this->name
        );
        if ($force) {
            $rebaseCommand = $rebaseCommand->withErrorHandlingStrategy(RebaseErrorHandlingStrategy::STRATEGY_FORCE);
        }
        $this->contentRepository->handle($rebaseCommand)->block();

        $this->updateCurrentState();
    }

    public function changeBaseWorkspace(WorkspaceName $baseWorkspaceName): void
    {
        $this->contentRepository->handle(
            ChangeBaseWorkspace::create(
                $this->name,
                $baseWorkspaceName
            )
        )->block();

        $this->updateCurrentState();
    }

    private function requireNodeToBeOfType(
        NodeAggregateId $nodeAggregateId,
        NodeTypeName $nodeTypeName,
    ): void {
        $nodeAggregate = $this->contentRepository->getContentGraph()->findNodeAggregateById(
            $this->currentContentStreamId,
            $nodeAggregateId,
        );
        if (!$nodeAggregate instanceof NodeAggregate) {
            throw new NodeAggregateCurrentlyDoesNotExist(
                'Node aggregate ' . $nodeAggregateId->value . ' does currently not exist',
                1710967964
            );
        }

        if (
            !$this->contentRepository->getNodeTypeManager()
                ->getNodeType($nodeAggregate->nodeTypeName)
                ?->isOfType($nodeTypeName)
        ) {
            throw new \DomainException(
                'Node aggregate ' . $nodeAggregateId->value . ' is not of expected type ' . $nodeTypeName->value,
                1710968108
            );
        }
    }

    private function publish(): void
    {
        $this->contentRepository->handle(
            PublishWorkspace::create(
                $this->name,
            )
        )->block();

        $this->updateCurrentState();
    }

    private function publishNodes(
        NodeIdsToPublishOrDiscard $nodeIdsToPublish
    ): void {
        /**
         * TODO: only rebase if necessary!
         * Also, isn't this already included in @see WorkspaceCommandHandler::handlePublishIndividualNodesFromWorkspace ?
         */
        $this->contentRepository->handle(
            RebaseWorkspace::create(
                $this->name
            )
        )->block();

        $this->contentRepository->handle(
            PublishIndividualNodesFromWorkspace::create(
                $this->name,
                $nodeIdsToPublish
            )
        )->block();

        $this->updateCurrentState();
    }

    private function discard(): void
    {
        $this->contentRepository->handle(
            DiscardWorkspace::create(
                $this->name,
            )
        )->block();

        $this->updateCurrentState();
    }

    private function discardNodes(
        NodeIdsToPublishOrDiscard $nodeIdsToDiscard
    ): void {
        /**
         * TODO: only rebase if necessary!
         * Also, isn't this already included in @see WorkspaceCommandHandler::handleDiscardIndividualNodesFromWorkspace ?
         */
        $this->contentRepository->handle(
            RebaseWorkspace::create(
                $this->name
            )
        )->block();

        $this->contentRepository->handle(
            DiscardIndividualNodesFromWorkspace::create(
                $this->name,
                $nodeIdsToDiscard
            )
        )->block();

        $this->updateCurrentState();
    }

    /**
     * @param NodeAggregateId $ancestorId The id of the ancestor node of all affected nodes
     * @param NodeTypeName $ancestorNodeTypeName The type of the ancestor node of all affected nodes
     */
    private function resolveNodeIdsToPublishOrDiscard(
        NodeAggregateId $ancestorId,
        NodeTypeName $ancestorNodeTypeName
    ): NodeIdsToPublishOrDiscard {
        /** @var ChangeFinder $changeFinder */
        $changeFinder = $this->contentRepository->projectionState(ChangeFinder::class);
        $changes = $changeFinder->findByContentStreamId($this->currentContentStreamId);
        $nodeIdsToPublishOrDiscard = [];
        foreach ($changes as $change) {
            if (
                !$this->isChangePublishableWithinAncestorScope(
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

    private function isChangePublishableWithinAncestorScope(
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

        $subgraph = $this->contentRepository->getContentGraph()->getSubgraph(
            $this->currentContentStreamId,
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

        return $actualAncestorNode?->nodeAggregateId->equals($ancestorId) ?? false;
    }

    /**
     * Before the introduction of the WorkspacePublisher, the UI only ever
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
     * or discarded by WorkspacePublisher::publishSite, regardless of what
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

    private function updateCurrentState(): void
    {
        /** The workspace projection should have been marked stale via @see WithMarkStaleInterface in the meantime */
        $contentRepositoryWorkspace = $this->contentRepository->getWorkspaceFinder()
            ->findOneByName($this->name);
        if (!$contentRepositoryWorkspace) {
            throw new WorkspaceDoesNotExist('Cannot update state of non-existent workspace ' . $this->name->value, 1711704397);
        }

        $this->currentContentStreamId = $contentRepositoryWorkspace->currentContentStreamId;
        $this->currentStatus = $contentRepositoryWorkspace->status;
        $this->currentBaseWorkspaceName = $contentRepositoryWorkspace->baseWorkspaceName;
    }
}

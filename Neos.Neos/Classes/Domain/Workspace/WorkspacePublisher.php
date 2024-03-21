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
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\PendingChangesProjection\Change;
use Neos\Neos\PendingChangesProjection\ChangeFinder;

/**
 * Neos' workspace publisher service
 *
 * @api
 */
#[Flow\Scope('singleton')]
final readonly class WorkspacePublisher
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    /**
     * @return int The amount of changes that were published
     */
    public function publishChangesInSite(PublishChangesInSite $command): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($command->contentRepositoryId);
        $contentStreamId = $this->requireContentStream($contentRepository, $command->workspaceName);
        $ancestorNodeTypeName = NodeTypeNameFactory::forSite();
        $this->requireNodeToBeOfType(
            $contentRepository,
            $contentStreamId,
            $command->siteId,
            $ancestorNodeTypeName
        );

        $nodeIdsToPublish = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $command->workspaceName,
            $command->siteId,
            $ancestorNodeTypeName
        );

        $this->publishNodes(
            $contentRepository,
            $command->workspaceName,
            $nodeIdsToPublish
        );

        return count($nodeIdsToPublish);
    }

    /**
     * @return int The amount of changes that were published
     */
    public function publishChangesInDocument(PublishChangesInDocument $command): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($command->contentRepositoryId);
        $contentStreamId = $this->requireContentStream($contentRepository, $command->workspaceName);
        $ancestorNodeTypeName = NodeTypeNameFactory::forDocument();
        $this->requireNodeToBeOfType(
            $contentRepository,
            $contentStreamId,
            $command->documentId,
            $ancestorNodeTypeName
        );

        $nodeIdsToPublish = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $command->workspaceName,
            $command->documentId,
            $ancestorNodeTypeName
        );

        $this->publishNodes(
            $contentRepository,
            $command->workspaceName,
            $nodeIdsToPublish
        );

        return count($nodeIdsToPublish);
    }

    /**
     * @return int The amount of changes that were discarded
     */
    public function discardChangesInSite(DiscardChangesInSite $command): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($command->contentRepositoryId);
        $contentStreamId = $this->requireContentStream($contentRepository, $command->workspaceName);
        $ancestorNodeTypeName = NodeTypeNameFactory::forSite();
        $this->requireNodeToBeOfType(
            $contentRepository,
            $contentStreamId,
            $command->siteId,
            $ancestorNodeTypeName
        );

        $nodeIdsToDiscard = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $command->workspaceName,
            $command->siteId,
            NodeTypeNameFactory::forSite()
        );

        $this->discardNodes(
            $contentRepository,
            $command->workspaceName,
            $nodeIdsToDiscard,
        );

        return count($nodeIdsToDiscard);
    }

    /**
     * @return int The amount of changes that were discarded
     */
    public function discardChangesInDocument(DiscardChangesInDocument $command): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($command->contentRepositoryId);
        $contentStreamId = $this->requireContentStream($contentRepository, $command->workspaceName);
        $ancestorNodeTypeName = NodeTypeNameFactory::forDocument();
        $this->requireNodeToBeOfType(
            $contentRepository,
            $contentStreamId,
            $command->documentId,
            $ancestorNodeTypeName
        );

        $nodeIdsToDiscard = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $command->workspaceName,
            $command->documentId,
            $ancestorNodeTypeName
        );

        $this->discardNodes(
            $contentRepository,
            $command->workspaceName,
            $nodeIdsToDiscard
        );

        return count($nodeIdsToDiscard);
    }

    private function requireContentStream(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName
    ): ContentStreamId {
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            throw new \DomainException('Workspace "' . $workspaceName->value . '" is missing', 1710967842);
        }

        return $workspace->currentContentStreamId;
    }

    private function requireNodeToBeOfType(
        ContentRepository $contentRepository,
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        NodeTypeName $nodeTypeName,
    ): void {
        $nodeAggregate = $contentRepository->getContentGraph()->findNodeAggregateById(
            $contentStreamId,
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
                ->isOfType($nodeTypeName)
        ) {
            throw new \DomainException(
                'Node aggregate ' . $nodeAggregateId->value . ' is not of expected type ' . $nodeTypeName->value,
                1710968108
            );
        }
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
            RebaseWorkspace::create(
                $workspaceName
            )
        )->block();

        $contentRepository->handle(
            PublishIndividualNodesFromWorkspace::create(
                $workspaceName,
                $nodeIdsToPublish
            )
        )->block();
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
            RebaseWorkspace::create(
                $workspaceName
            )
        )->block();

        $contentRepository->handle(
            DiscardIndividualNodesFromWorkspace::create(
                $workspaceName,
                $nodeIdsToDiscard
            )
        )->block();
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
        /** @var ChangeFinder $changeFinder */
        $changeFinder = $contentRepository->projectionState(ChangeFinder::class);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            throw new \DomainException(
                'Cannot publish anything from unknown workspace ' . $workspaceName->value,
                1710891954
            );
        }
        $contentStreamId = $workspace->currentContentStreamId;
        $changes = $changeFinder->findByContentStreamId($contentStreamId);
        $nodeIdsToPublishOrDiscard = [];
        foreach ($changes as $change) {
            if (
                !$this->isChangePublishableWithinAncestorScope(
                    $contentRepository,
                    $contentStreamId,
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
        ContentRepository $contentRepository,
        ContentStreamId $contentStreamId,
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

        $subgraph = $contentRepository->getContentGraph()->getSubgraph(
            $contentStreamId,
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
}

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
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\Neos\PendingChangesProjection\ChangeFinder;
use Neos\Neos\PendingChangesProjection\ChangeProjection;

/**
 * Neos' workspace publisher service
 *
 * @api
 */
#[Flow\Scope('singleton')]
readonly class WorkspacePublisher
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    /**
     * @return int The amount of changes that were published
     */
    public function publishSite(PublishSite $command): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($command->contentRepositoryId);

        $nodeIdsToPublish = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $command->workspaceName,
            $command->siteId,
            NodeTypeName::fromString('Neos.Neos:Site')
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
    public function publishDocument(PublishDocument $command): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($command->contentRepositoryId);

        $nodeIdsToPublish = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $command->workspaceName,
            $command->documentId,
            NodeTypeName::fromString('Neos.Neos:Document')
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
    public function discardSite(DiscardSite $command): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($command->contentRepositoryId);
        $nodeIdsToDiscard = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $command->workspaceName,
            $command->siteId,
            NodeTypeName::fromString('Neos.Neos:Site')
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
    public function discardDocument(DiscardDocument $command): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($command->contentRepositoryId);
        $nodeIdsToDiscard = $this->resolveNodeIdsToPublishOrDiscard(
            $contentRepository,
            $command->workspaceName,
            $command->documentId,
            NodeTypeName::fromString('Neos.Neos:Document')
        );

        $this->discardNodes(
            $contentRepository,
            $command->workspaceName,
            $nodeIdsToDiscard
        );

        return count($nodeIdsToDiscard);
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
        $changeFinder = $contentRepository->projectionState(ChangeProjection::class);
        $contentStreamId = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName)->currentContentStreamId;
        $changes = $changeFinder->findByContentStreamId($contentStreamId);
        $nodeIdsToPublishOrDiscard = [];
        foreach ($changes as $change) {
            $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamId,
                $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                VisibilityConstraints::withoutRestrictions()
            );
            $documentNode = $subgraph->findClosestNode(
                $change->nodeAggregateId,
                FindClosestNodeFilter::create(nodeTypes: $ancestorNodeTypeName->value)
            );
            if (!($documentNode?->nodeAggregateId->equals($ancestorId))) {
                continue;
            }

            $nodeIdsToPublishOrDiscard[] = new NodeIdToPublishOrDiscard(
                $change->nodeAggregateId,
                $change->originDimensionSpacePoint->toDimensionSpacePoint()
            );
        }

        return NodeIdsToPublishOrDiscard::create(...$nodeIdsToPublishOrDiscard);
    }
}

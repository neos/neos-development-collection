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
use Neos\ContentRepository\Core\Feature\WorkspaceCommandSkipped;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Exception\BaseWorkspaceEqualsWorkspaceException;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Exception\CircularRelationBetweenWorkspacesException;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\PartialWorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceContainsPublishableChanges;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace as ContentRepositoryWorkspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\DiscardingResult;
use Neos\Neos\Domain\Model\PublishingResult;
use Neos\Neos\Domain\SubtreeTagging\SoftRemoval\SoftRemovalGarbageCollector;
use Neos\Neos\PendingChangesProjection\Change;
use Neos\Neos\PendingChangesProjection\ChangeFinder;
use Neos\Neos\PendingChangesProjection\Changes;

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
        private readonly SoftRemovalGarbageCollector $softRemovalGarbageCollector
    ) {
    }

    /**
     * @internal experimental api, until actually used by the Neos.Ui
     */
    public function pendingWorkspaceChanges(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): Changes
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        return $this->pendingWorkspaceChangesInternal($contentRepository, $workspaceName);
    }

    /**
     * @internal experimental api, until actually used by the Neos.Ui
     */
    public function countPendingWorkspaceChanges(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): int
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        return $this->countPendingWorkspaceChangesInternal($contentRepository, $workspaceName);
    }

    /**
     * @throws WorkspaceRebaseFailed|WorkspaceCommandSkipped
     */
    public function rebaseWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, RebaseErrorHandlingStrategy $rebaseErrorHandlingStrategy = RebaseErrorHandlingStrategy::STRATEGY_FAIL): void
    {
        $rebaseCommand = RebaseWorkspace::create($workspaceName)->withErrorHandlingStrategy($rebaseErrorHandlingStrategy);
        $this->contentRepositoryRegistry->get($contentRepositoryId)->handle($rebaseCommand);
        $this->softRemovalGarbageCollector->run($contentRepositoryId);
    }

    /**
     * @throws WorkspaceRebaseFailed|WorkspaceCommandSkipped
     */
    public function publishWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): PublishingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $crWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        if ($crWorkspace->isRootWorkspace()) {
            throw new \InvalidArgumentException(sprintf('Failed to publish workspace "%s" because it has no base workspace', $workspaceName->value), 1717517124);
        }
        $numberOfPendingChanges = $this->countPendingWorkspaceChangesInternal($contentRepository, $workspaceName);
        $this->contentRepositoryRegistry->get($contentRepositoryId)->handle(PublishWorkspace::create($workspaceName));
        $this->softRemovalGarbageCollector->run($contentRepositoryId);
        return new PublishingResult($numberOfPendingChanges, $crWorkspace->baseWorkspaceName);
    }

    /**
     * @throws WorkspaceRebaseFailed|PartialWorkspaceRebaseFailed|WorkspaceCommandSkipped
     */
    public function publishChangesInSite(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, NodeAggregateId $siteId): PublishingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $crWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        if ($crWorkspace->isRootWorkspace()) {
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

        if (
            $nodeIdsToPublish->isEmpty()
            && $this->allPendingChangesBelongToAnotherScope($contentRepository, $workspaceName, $ancestorNodeTypeName)
        ) {
            return new PublishingResult(0, $crWorkspace->baseWorkspaceName);
        }

        $this->publishNodes($contentRepository, $workspaceName, $nodeIdsToPublish);
        $this->softRemovalGarbageCollector->run($contentRepositoryId);

        return new PublishingResult(
            count($nodeIdsToPublish),
            $crWorkspace->baseWorkspaceName,
        );
    }

    /**
     * @throws WorkspaceRebaseFailed|PartialWorkspaceRebaseFailed|WorkspaceCommandSkipped
     */
    public function publishChangesInDocument(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, NodeAggregateId $documentId): PublishingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $crWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        if ($crWorkspace->isRootWorkspace()) {
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

        if (
            $nodeIdsToPublish->isEmpty()
            && $this->allPendingChangesBelongToAnotherScope($contentRepository, $workspaceName, $ancestorNodeTypeName)
        ) {
            return new PublishingResult(0, $crWorkspace->baseWorkspaceName);
        }

        $this->publishNodes($contentRepository, $workspaceName, $nodeIdsToPublish);
        $this->softRemovalGarbageCollector->run($contentRepositoryId);

        return new PublishingResult(
            count($nodeIdsToPublish),
            $crWorkspace->baseWorkspaceName,
        );
    }

    /**
     * @throws WorkspaceRebaseFailed|WorkspaceCommandSkipped
     */
    public function discardAllWorkspaceChanges(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): DiscardingResult
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);

        $numberOfChangesToBeDiscarded = $this->countPendingWorkspaceChangesInternal($contentRepository, $workspaceName);

        $contentRepository->handle(DiscardWorkspace::create($workspaceName));
        $this->softRemovalGarbageCollector->run($contentRepositoryId);

        return new DiscardingResult($numberOfChangesToBeDiscarded);
    }

    /**
     * @throws WorkspaceRebaseFailed|PartialWorkspaceRebaseFailed|WorkspaceCommandSkipped
     */
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

        if (
            $nodeIdsToDiscard->isEmpty()
            && $this->allPendingChangesBelongToAnotherScope($contentRepository, $workspaceName, $ancestorNodeTypeName)
        ) {
            return new DiscardingResult(0);
        }

        $this->discardNodes($contentRepository, $workspaceName, $nodeIdsToDiscard);
        $this->softRemovalGarbageCollector->run($contentRepositoryId);

        return new DiscardingResult(
            count($nodeIdsToDiscard)
        );
    }

    /**
     * @throws WorkspaceRebaseFailed|WorkspaceCommandSkipped
     */
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

        if (
            $nodeIdsToDiscard->isEmpty()
            && $this->allPendingChangesBelongToAnotherScope($contentRepository, $workspaceName, $ancestorNodeTypeName)
        ) {
            return new DiscardingResult(0);
        }

        $this->discardNodes($contentRepository, $workspaceName, $nodeIdsToDiscard);
        $this->softRemovalGarbageCollector->run($contentRepositoryId);

        return new DiscardingResult(
            count($nodeIdsToDiscard)
        );
    }

    /**
     * @throws WorkspaceCommandSkipped|WorkspaceContainsPublishableChanges|BaseWorkspaceEqualsWorkspaceException|CircularRelationBetweenWorkspacesException
     */
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

    /**
     * @throws WorkspaceRebaseFailed|PartialWorkspaceRebaseFailed|WorkspaceCommandSkipped
     */
    private function discardNodes(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeAggregateIds $nodeIdsToDiscard
    ): void {
        $contentRepository->handle(
            DiscardIndividualNodesFromWorkspace::create(
                $workspaceName,
                $nodeIdsToDiscard
            )
        );
    }

    /**
     * @throws WorkspaceRebaseFailed|PartialWorkspaceRebaseFailed|WorkspaceCommandSkipped
     */
    private function publishNodes(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeAggregateIds $nodeIdsToPublish
    ): void {
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
        $workspace = $contentRepository->findWorkspaceByName($workspaceName);
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
    ): NodeAggregateIds {
        $contentGraph = $contentRepository->getContentGraph($workspaceName);

        $nodeIdsToPublishOrDiscard = [];
        foreach ($this->pendingWorkspaceChangesInternal($contentRepository, $workspaceName) as $change) {
            if (
                !$this->isChangePublishableWithinAncestorScope(
                    $contentGraph,
                    $change,
                    $ancestorNodeTypeName,
                    $ancestorId
                )
            ) {
                continue;
            }

            $nodeIdsToPublishOrDiscard[] = $change->nodeAggregateId;
        }

        return NodeAggregateIds::create(...$nodeIdsToPublishOrDiscard);
    }

    private function pendingWorkspaceChangesInternal(ContentRepository $contentRepository, WorkspaceName $workspaceName): Changes
    {
        $crWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        return $contentRepository->projectionState(ChangeFinder::class)->findByContentStreamId($crWorkspace->currentContentStreamId);
    }

    private function countPendingWorkspaceChangesInternal(ContentRepository $contentRepository, WorkspaceName $workspaceName): int
    {
        $crWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);
        return $contentRepository->projectionState(ChangeFinder::class)->countByContentStreamId($crWorkspace->currentContentStreamId);
    }

    /**
     * Whether every pending change of this workspace can be attributed to an ancestor of the given type.
     *
     * This is only consulted when nothing was resolved for the requested scope, in order to tell two very
     * different situations apart:
     *
     * - every change belongs to *another* site or document (e.g. the editor is working in a different site
     *   of a multi-site content repository). Publishing or discarding the requested scope is then a no-op.
     * - a change cannot be attributed to any ancestor of that type, for instance because its node no longer
     *   exists in the workspace. Such a change would silently be left behind, so we must not skip the
     *   command and let it fail loudly instead.
     *
     * @see https://github.com/neos/neos-development-collection/issues/5459 for the underlying problem of
     *      changes that cannot be attributed to their document or site.
     */
    private function allPendingChangesBelongToAnotherScope(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName,
        NodeTypeName $ancestorNodeTypeName
    ): bool {
        $contentGraph = $contentRepository->getContentGraph($workspaceName);
        foreach ($this->pendingWorkspaceChangesInternal($contentRepository, $workspaceName) as $change) {
            if ($change->originDimensionSpacePoint === null) {
                // a change to the node aggregate itself – it can only be attributed if the aggregate still exists
                if ($contentGraph->findNodeAggregateById($change->nodeAggregateId) === null) {
                    return false;
                }
                continue;
            }

            $subgraph = $contentGraph->getSubgraph(
                $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                VisibilityConstraints::createEmpty()
            );
            $ancestorNode = $subgraph->findClosestNode(
                $change->getLegacyRemovalAttachmentPoint() ?? $change->nodeAggregateId,
                FindClosestNodeFilter::create(nodeTypes: $ancestorNodeTypeName->value)
            );
            if ($ancestorNode === null) {
                return false;
            }
        }

        return true;
    }

    private function isChangePublishableWithinAncestorScope(
        ContentGraphInterface $contentGraph,
        Change $change,
        NodeTypeName $ancestorNodeTypeName,
        NodeAggregateId $ancestorId
    ): bool {
        if ($change->originDimensionSpacePoint) {
            $subgraph = $contentGraph->getSubgraph(
                $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                VisibilityConstraints::createEmpty()
            );

            // A Change is publishable if the respective node has a closest ancestor that matches our
            // current ancestor scope (Document/Site)
            $actualAncestorNode = $subgraph->findClosestNode(
                $change->getLegacyRemovalAttachmentPoint() ?? $change->nodeAggregateId,
                FindClosestNodeFilter::create(nodeTypes: $ancestorNodeTypeName->value)
            );

            return $actualAncestorNode?->aggregateId->equals($ancestorId) ?? false;
        } else {
            return $this->findAncestorAggregateIds(
                $contentGraph,
                $change->nodeAggregateId
            )->contain($ancestorId);
        }
    }

    private function findAncestorAggregateIds(ContentGraphInterface $contentGraph, NodeAggregateId $descendantNodeAggregateId): NodeAggregateIds
    {
        $nodeAggregateIds = NodeAggregateIds::create($descendantNodeAggregateId);
        foreach ($contentGraph->findParentNodeAggregates($descendantNodeAggregateId) as $parentNodeAggregate) {
            $nodeAggregateIds = $nodeAggregateIds->merge(NodeAggregateIds::create($parentNodeAggregate->nodeAggregateId));
            $nodeAggregateIds = $nodeAggregateIds->merge($this->findAncestorAggregateIds($contentGraph, $parentNodeAggregate->nodeAggregateId));
        }

        return $nodeAggregateIds;
    }
}

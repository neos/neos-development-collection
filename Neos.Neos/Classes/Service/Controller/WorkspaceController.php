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

namespace Neos\Neos\Service\Controller;

use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Command\DiscardWorkspace;
use Neos\ContentRepository\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\Changes\Change;
use Neos\ContentRepository\Projection\Changes\ChangeFinder;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Service\View\NodeView;

/**
 * Service Controller for managing Workspaces
 *
 * @todo properly implement me
 */
class WorkspaceController extends AbstractServiceController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = NodeView::class;

    /**
     * @var NodeView
     */
    protected $view;

    #[Flow\Inject]
    protected WorkspaceFinder $workspaceFinder;

    #[Flow\Inject]
    protected WorkspaceCommandHandler $workspaceCommandHandler;

    #[Flow\Inject]
    protected ChangeFinder $changeFinder;

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

    /**
     * Publishes the given node to the specified targetWorkspace
     *
     * @param NodeInterface $node
     * @param string $targetWorkspaceName
     * @return void
     */
    public function publishNodeAction(NodeInterface $node, $targetWorkspaceName = null)
    {
        // how about no. How is any of this validated?
        $this->throwStatus(400, 'Insufficient arguments');

        /*
        $targetWorkspace = ($targetWorkspaceName !== null)
            ? $this->workspaceRepository->findOneByName($targetWorkspaceName)
            : null;

        $command = PublishIndividualNodesFromWorkspace::create(
            $this->getCurr
        )
        $this->publishingService->publishNode($node, $targetWorkspace);

        $this->throwStatus(204, 'Node published', '');
        */
    }

    /**
     * Publishes the given nodes to the specified targetWorkspace
     *
     * @param array<NodeInterface> $nodes
     * @param string $targetWorkspaceName
     * @return void
     */
    public function publishNodesAction(array $nodes, $targetWorkspaceName = null)
    {
        // how about no. How is any of this validated?
        $this->throwStatus(400, 'Insufficient arguments');

        /*
        $targetWorkspace = ($targetWorkspaceName !== null)
            ? $this->workspaceRepository->findOneByName($targetWorkspaceName)
            : null;
        $this->publishingService->publishNodes($nodes, $targetWorkspace);

        $this->throwStatus(204, 'Nodes published', '');
        */
    }

    /**
     * Discards the given node
     *
     * @param NodeInterface $node
     * @return void
     */
    public function discardNodeAction(NodeInterface $node)
    {
        // how about no. How is any of this validated?
        $this->throwStatus(400, 'Insufficient arguments');

        //$this->throwStatus(204, 'Node changes have been discarded', '');
    }

    /**
     * Discards the given nodes
     *
     * @param array<NodeAddress> $nodes
     * @return void
     */
    public function discardNodesAction(array $nodes)
    {
        // how about no. How is any of this validated?
        $this->throwStatus(400, 'Insufficient arguments');

        //$this->throwStatus(204, 'Node changes have been discarded', '');
    }

    /**
     * Publish everything in the workspace with the given workspace name
     *
     * @param string $sourceWorkspaceName Name of the source workspace containing the content to publish
     * @param ?string $targetWorkspaceName Name of the target workspace the content should be published to, unused
     */
    public function publishAllAction(string $sourceWorkspaceName, ?string $targetWorkspaceName = null): void
    {
        $currentUserIdentifier = $this->getCurrentUserIdentifier();
        if (!$currentUserIdentifier instanceof UserIdentifier) {
            $this->throwStatus(400, 'Missing initiating user');
        }

        $this->workspaceCommandHandler->handlePublishWorkspace(new PublishWorkspace(
            WorkspaceName::fromString($sourceWorkspaceName),
            $currentUserIdentifier
        ));

        $this->throwStatus(204, sprintf(
            'All changes in workspace %s have been published',
            $sourceWorkspaceName
        ), '');
    }

    /**
     * Get every unpublished node in the workspace with the given workspace name
     */
    public function getWorkspaceWideUnpublishedNodesAction(string $workspace): void
    {
        $workspaceName = WorkspaceName::fromString($workspace);
        $workspace = $this->workspaceFinder->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            $this->throwStatus(400, 'Unknown workspace "' . $workspaceName . '"');
        }

        $changes = $this->changeFinder->findByContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());

        $this->view->assignNodes(Nodes::fromArray(array_filter(array_map(function (Change $change): ?NodeInterface {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $change->contentStreamIdentifier,
                $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                VisibilityConstraints::withoutRestrictions()
            );

            return $nodeAccessor->findByIdentifier($change->nodeAggregateIdentifier);
        }, $changes))));
    }

    /**
     * Discard everything in the workspace with the given workspace name
     */
    public function discardAllAction(string $workspace): void
    {
        $currentUserIdentifier = $this->getCurrentUserIdentifier();
        if (!$currentUserIdentifier instanceof UserIdentifier) {
            $this->throwStatus(400, 'Missing initiating user');
        }

        $this->workspaceCommandHandler->handleDiscardWorkspace(DiscardWorkspace::create(
            WorkspaceName::fromString($workspace),
            $currentUserIdentifier
        ));

        $this->throwStatus(204, 'Workspace changes have been discarded', '');
    }
}

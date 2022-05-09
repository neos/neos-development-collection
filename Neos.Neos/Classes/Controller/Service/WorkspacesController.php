<?php
namespace Neos\Neos\Controller\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Neos\View\Service\WorkspaceJsonView;

/**
 * REST service for workspaces
 *
 * @Flow\Scope("singleton")
 */
class WorkspacesController extends ActionController
{
    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var \Neos\Neos\Domain\Service\UserService
     */
    protected $userService;

    #[Flow\Inject]
    protected WorkspaceCommandHandler $workspaceCommandHandler;

    /**
     * @var array<string,class-string>
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => WorkspaceJsonView::class
    ];

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array<int,string>
     * @see http://www.iana.org/assignments/media-types/index.html
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json'
    ];

    /**
     * Shows a list of existing workspaces
     */
    public function indexAction(): void
    {
        $user = $this->userService->getCurrentUser();
        $workspacesArray = [];
        foreach ($this->workspaceFinder->findAll() as $workspace) {
            // FIXME: This check should be implemented through a specialized Workspace Privilege or something similar
            if ($workspace->getOwner() !== null && $workspace->getOwner() !== $user) {
                continue;
            }

            $workspaceArray = [
                'name' => $workspace->getName(),
                'title' => $workspace->getTitle(),
                'description' => $workspace->getDescription(),
                'baseWorkspace' => $workspace->getBaseWorkspace()
            ];
            if ($user !== null) {
                $workspaceArray['readonly'] = !$this->userService->currentUserCanPublishToWorkspace($workspace);
            }
            $workspacesArray[] = $workspaceArray;
        }

        $this->view->assign('workspaces', $workspacesArray);
    }

    /**
     * Shows details of the given workspace
     *
     * @param Workspace $workspace
     */
    public function showAction(Workspace $workspace): void
    {
        $this->view->assign('workspace', $workspace);
    }

    /**
     * Create a workspace
     *
     * @param string $workspaceName
     * @param string $baseWorkspace
     * @param string $ownerAccountIdentifier
     */
    public function createAction($workspaceName, string $baseWorkspace, $ownerAccountIdentifier = null): void
    {
        if ($ownerAccountIdentifier !== null) {
            $owner = $this->userService->getUser($ownerAccountIdentifier);
            if ($owner === null) {
                $this->throwStatus(422, 'Requested owner account does not exist', '');
            }
        } else {
            $owner = null;
        }

        $initiatingUserIdentifier = $this->userService->getCurrentUserIdentifier();
        if (is_null($initiatingUserIdentifier)) {
            $this->throwStatus(422, 'Initiating user is missing', '');
        }

        try {
            $this->workspaceCommandHandler->handleCreateWorkspace(new CreateWorkspace(
                WorkspaceName::fromString($workspaceName),
                WorkspaceName::fromString($baseWorkspace),
                WorkspaceTitle::fromString($workspaceName),
                WorkspaceDescription::fromString($workspaceName),
                $initiatingUserIdentifier,
                ContentStreamIdentifier::create(),
                $owner
                    ? UserIdentifier::fromString($this->persistenceManager->getIdentifierByObject($owner))
                    : null
            ));
        } catch (WorkspaceAlreadyExists $exception) {
            $this->throwStatus(409, 'Workspace already exists', '');
        }

        $this->throwStatus(201, 'Workspace created', '');
    }

    /**
     * Updates a workspace
     *
     * @param array<mixed> $workspace The updated workspace
     * @return void
     */
    public function updateAction(array $workspace)
    {
        $this->throwStatus(404, 'Workspace update not supported yet');
        /*
        $this->workspaceFinder->update($workspace);
        $this->throwStatus(200, 'Workspace updated', '');*/
    }
}

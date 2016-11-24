<?php
namespace TYPO3\Neos\Controller\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\FluidAdaptor\View\TemplateView;
use TYPO3\Neos\View\Service\WorkspaceJsonView;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * REST service for workspaces
 *
 * @Flow\Scope("singleton")
 */
class WorkspacesController extends ActionController
{

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Domain\Service\UserService
     */
    protected $userService;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => WorkspaceJsonView::class
    ];

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array
     * @see http://www.iana.org/assignments/media-types/index.html
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json'
    ];

    /**
     * Shows a list of existing workspaces
     *
     * @return string
     */
    public function indexAction()
    {
        $user = $this->userService->getCurrentUser();
        $workspacesArray = [];
        /** @var Workspace $workspace */
        foreach ($this->workspaceRepository->findAll() as $workspace) {

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
     * @return string
     */
    public function showAction(Workspace $workspace)
    {
        $this->view->assign('workspace', $workspace);
    }

    /**
     * Create a workspace
     *
     * @param string $workspaceName
     * @param Workspace $baseWorkspace
     * @param string $ownerAccountIdentifier
     * @return string
     */
    public function createAction($workspaceName, Workspace $baseWorkspace, $ownerAccountIdentifier = null)
    {
        $existingWorkspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        if ($existingWorkspace !== null) {
            $this->throwStatus(409, 'Workspace already exists', '');
        }

        if ($ownerAccountIdentifier !== null) {
            $owner = $this->userService->getUser($ownerAccountIdentifier);
            if ($owner === null) {
                $this->throwStatus(422, 'Requested owner account does not exist', '');
            }
        } else {
            $owner = null;
        }

        $workspace = new Workspace($workspaceName, $baseWorkspace, $owner);
        $this->workspaceRepository->add($workspace);
        $this->throwStatus(201, 'Workspace created', '');
    }

    /**
     * Configure property mapping for the updateAction
     *
     * @return void
     */
    public function initializeUpdateAction()
    {
        $propertyMappingConfiguration = $this->arguments->getArgument('workspace')->getPropertyMappingConfiguration();
        $propertyMappingConfiguration->allowProperties('name', 'baseWorkspace');
        $propertyMappingConfiguration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
    }

    /**
     * Updates a workspace
     *
     * @param Workspace $workspace The updated workspace
     * @return void
     */
    public function updateAction(Workspace $workspace)
    {
        $this->workspaceRepository->update($workspace);
        $this->throwStatus(200, 'Workspace updated', '');
    }
}

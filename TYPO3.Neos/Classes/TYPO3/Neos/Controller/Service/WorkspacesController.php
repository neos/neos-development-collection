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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
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
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'html' => 'TYPO3\Fluid\View\TemplateView',
        'json' => 'TYPO3\Neos\View\Service\NodeJsonView'
    );

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array
     * @see http://www.iana.org/assignments/media-types/index.html
     */
    protected $supportedMediaTypes = array(
        'text/html',
        'application/json'
    );

    /**
     * Shows a list of existing workspaces
     *
     * @return string
     */
    public function indexAction()
    {
        $this->view->assign('workspaces', $this->workspaceRepository->findAll());
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
     * Shows details of the given workspace
     *
     * @param string $workspaceName
     * @param Workspace $baseWorkspace
     * @return string
     */
    public function createAction($workspaceName, Workspace $baseWorkspace)
    {
        $existingWorkspace = $this->workspaceRepository->findByIdentifier($workspaceName);
        if ($existingWorkspace !== null) {
            $this->throwStatus(409, 'Workspace already exists', '');
        }

        $workspace = new Workspace($workspaceName, $baseWorkspace);
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
        $propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
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

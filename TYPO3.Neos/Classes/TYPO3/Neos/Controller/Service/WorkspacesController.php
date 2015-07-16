<?php
namespace TYPO3\Neos\Controller\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Persistence\QueryResultInterface;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * REST service for workspaces
 *
 * @Flow\Scope("singleton")
 */
class WorkspacesController extends ActionController {

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
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;

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
	 * @param boolean $onlyPublishable
	 * @return string
	 */
	public function indexAction($onlyPublishable = FALSE) {
		$workspaces = $this->workspaceRepository->findAll();
		if ($onlyPublishable) {
			$workspaces = $this->filterWorkspacesByPublishPrivilege($workspaces);
		}

		$this->view->assign('workspaces', $workspaces);
	}

	/**
	 * Shows details of the given workspace
	 *
	 * @param Workspace $workspace
	 * @return string
	 */
	public function showAction(Workspace $workspace) {
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
	public function createAction($workspaceName, Workspace $baseWorkspace, $ownerAccountIdentifier = NULL) {
		$existingWorkspace = $this->workspaceRepository->findByIdentifier($workspaceName);
		if ($existingWorkspace !== NULL) {
			$this->throwStatus(409, 'Workspace already exists', '');
		}

		if ($ownerAccountIdentifier !== NULL) {
			$owner = $this->userService->getUser($ownerAccountIdentifier);
			if ($owner === NULL) {
				$this->throwStatus(422, 'Requested owner account does not exist', '');
			}
		} else {
			$owner = NULL;
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
	public function initializeUpdateAction() {
		$propertyMappingConfiguration = $this->arguments->getArgument('workspace')->getPropertyMappingConfiguration();
		$propertyMappingConfiguration->allowProperties('name', 'baseWorkspace');
		$propertyMappingConfiguration->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
	}

	/**
	 * Updates a workspace
	 *
	 * @param Workspace $workspace The updated workspace
	 * @return void
	 */
	public function updateAction(Workspace $workspace) {
		$this->workspaceRepository->update($workspace);
		$this->throwStatus(200, 'Workspace updated', '');
	}

	/**
	 * Filters the given query result by publish privilege.
	 *
	 * @param QueryResultInterface $workspaces
	 * @return array
	 */
	protected function filterWorkspacesByPublishPrivilege(QueryResultInterface $workspaces) {
		$filteredWorkspaces = array_filter($workspaces->toArray(), [$this, 'filterWorkspaceByPublishPrivilege']);

		return $filteredWorkspaces;
	}

	/**
	 * Check privilege to publish to the given workspace is granted.
	 *
	 * @param Workspace $workspace
	 * @return boolean
	 */
	protected function filterWorkspaceByPublishPrivilege(Workspace $workspace) {
		if ($workspace->getName() !== 'live') {
			return TRUE;
		}

		if ($this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.PublishToLiveWorkspace')) {
			return TRUE;
		}

		return FALSE;
	}

}

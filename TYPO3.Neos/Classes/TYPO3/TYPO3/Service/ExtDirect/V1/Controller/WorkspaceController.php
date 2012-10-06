<?php
namespace TYPO3\TYPO3\Service\ExtDirect\V1\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Domain\Service\ContentContext;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * ExtDirect Controller for managing Workspaces
 *
 * @Flow\Scope("singleton")
 */
class WorkspaceController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3\Service\WorkspacesService
	 */
	protected $workspacesService;

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\TYPO3\Service\ExtDirect\V1\View\NodeView';

	/**
	 * Select special error action
	 *
	 * @return void
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
		if ($this->arguments->hasArgument('node')) {
			$this
				->arguments
				->getArgument('node')
				->getPropertyMappingConfiguration()
				->setTypeConverterOption('TYPO3\TYPO3\Routing\NodeObjectConverter', \TYPO3\TYPO3\Routing\NodeObjectConverter::REMOVED_CONTENT_SHOWN, TRUE);
		}
	}

	/**
	 * Publishes the given node to the specified targetWorkspace
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $targetWorkspaceName
	 * @return void
	 * @ExtDirect
	 */
	public function publishNodeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $targetWorkspaceName) {
			/**
			 * TODO: The publishing pushes the same node twice, which causes the node to be published
			 * already when it's processed the second time. This obviously leads to a problem for the
			 * Workspace object which will (in the second time) try to publish a node in the live workspace
			 * to the baseWorkspace of the live workspace (which does not exist).
			 */
		if ($targetWorkspaceName === $node->getWorkspace()->getName()) {
			$this->view->assign('value', array('success' => TRUE));
			return;
		}

		$this->workspacesService->publishNode($node, $targetWorkspaceName);

		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->arguments->getValidationResults());
	}
}
?>
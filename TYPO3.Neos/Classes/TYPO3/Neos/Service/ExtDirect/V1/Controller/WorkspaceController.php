<?php
namespace TYPO3\Neos\Service\ExtDirect\V1\Controller;

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
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * ExtDirect Controller for managing Workspaces
 *
 * @Flow\Scope("singleton")
 */
class WorkspaceController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\PublishingService
	 */
	protected $publishingService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\Neos\Service\ExtDirect\V1\View\NodeView';

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
				->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', \TYPO3\TYPO3CR\TypeConverter\NodeConverter::REMOVED_CONTENT_SHOWN, TRUE);
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
		$targetWorkspace = $this->workspaceRepository->findOneByName($targetWorkspaceName);
			/**
			 * TODO: The publishing pushes the same node twice, which causes the node to be published
			 * already when it's processed the second time. This obviously leads to a problem for the
			 * Workspace object which will (in the second time) try to publish a node in the live workspace
			 * to the baseWorkspace of the live workspace (which does not exist).
			 */
		if ($targetWorkspace === $node->getWorkspace()) {
			$this->view->assign('value', array('success' => TRUE));
			return;
		}

		$this->publishingService->publishNode($node, $targetWorkspace);

		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Discards the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 * @ExtDirect
	 */
	public function discardNodeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->publishingService->discardNode($node);

		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Publish everything in the workspace with the given workspace name
	 *
	 * @param string $workspaceName
	 * @return void
	 * @ExtDirect
	 */
	public function publishAllAction($workspaceName) {
		$workspace = $this->workspaceRepository->findOneByName($workspaceName);
		$this->publishingService->publishNodes($this->publishingService->getUnpublishedNodes($workspace));

		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Get every unpublished node in the workspace with the given workspace name
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 * @ExtDirect
	 */
	public function getWorkspaceWideUnpublishedNodesAction($workspace) {
		$nodes = $this->publishingService->getUnpublishedNodes($workspace);

		$this->view->assignNodes($nodes);
	}

	/**
	 * Discard everything in the workspace with the given workspace name
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 * @ExtDirect
	 */
	public function discardAllAction($workspace) {
		$this->publishingService->discardNodes($this->publishingService->getUnpublishedNodes($workspace));

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

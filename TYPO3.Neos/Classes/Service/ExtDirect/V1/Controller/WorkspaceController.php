<?php
namespace TYPO3\TYPO3\Service\ExtDirect\V1\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Domain\Service\ContentContext;

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\ExtJS\Annotations\ExtDirect;

/**
 * ExtDirect Controller for managing Workspaces
 *
 * @FLOW3\Scope("singleton")
 */
class WorkspaceController extends \TYPO3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

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
	}

	/**
	 * Returns some status information about the given workspace
	 *
	 * @param string $workspaceName Name of the workspace
	 * @return void
	 * @ExtDirect
	 */
	public function getStatusAction($workspaceName) {
		$context = new ContentContext($workspaceName);
		$workspace = $context->getWorkspace(FALSE);
		if ($workspace === NULL) {
			throw new \InvalidArgumentException('Unknown workspace "' . $workspaceName. '".', 1291745692);
		}

		$data = array(
			'name' => $workspace->getName(),
			'unpublishedNodesCount' => $workspace->getNodeCount() - 1
		);
		$this->view->assign('value', array('data' => $data, 'success' => TRUE));
	}

	/**
	 * Returns a list of nodes which have not yet been published
	 *
	 * @param string $workspaceName Name of the workspace containing the nodes
	 * @return void
	 * @ExtDirect
	 */
	public function getUnpublishedNodesAction($workspaceName) {
		$context = new ContentContext($workspaceName);
		$workspace = $context->getWorkspace(FALSE);
		if ($workspace === NULL) {
			throw new \InvalidArgumentException('Unknown workspace "' . $workspaceName. '".', 1303153725);
		}

		$nodes = $this->nodeRepository->findByWorkspace($workspace)->toArray();
		foreach ($nodes as $index => $node) {
			if ($node->getPath() === '/') {
				unset ($nodes[$index]);
				continue;
			}
			$node->setContext($context);
		}
		$this->view->assignNodes($nodes);
	}

	/**
	 * Publishes the given sourceWorkspace to the specified targetWorkspace
	 *
	 * @param string $sourceWorkspaceName
	 * @param string $targetWorkspaceName
	 * @return void
	 * @ExtDirect
	 */
	public function publishWorkspaceAction($sourceWorkspaceName, $targetWorkspaceName) {
		$context = new ContentContext($sourceWorkspaceName);
		$sourceWorkspace = $context->getWorkspace(FALSE);
		if ($sourceWorkspace === NULL) {
			throw new \InvalidArgumentException('Unknown source workspace "' . $sourceWorkspaceName. '".', 1303153726);
		}

		$sourceWorkspace->publish($targetWorkspaceName);

		$this->view->assign('value', array('success' => TRUE));
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
		$sourceWorkspace = $node->getWorkspace();
		$sourceWorkspace->publishNodes(array($node), $targetWorkspaceName);

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

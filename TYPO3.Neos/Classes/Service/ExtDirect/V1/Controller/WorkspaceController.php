<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Service\ExtDirect\V1\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \F3\TYPO3\Domain\Service\ContentContext;

/**
 * ExtDirect Controller for managing Workspaces
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope singleton
 */
class WorkspaceController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'F3\TYPO3\Service\ExtDirect\V1\View\NodeView';


	/**
	 * Select special error action
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
	}

	/**
	 * Returns some status information about the given workspace
	 *
	 * @param string $workspaceName Name of the workspace
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
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
	 * @param \F3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $targetWorkspaceName
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @extdirect
	 */
	public function publishNodeAction(\F3\TYPO3CR\Domain\Model\NodeInterface $node, $targetWorkspaceName) {
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
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->arguments->getValidationResults());
	}
}
?>

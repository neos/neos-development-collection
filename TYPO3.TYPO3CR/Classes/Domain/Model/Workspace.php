<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A Workspace
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @entity
 * @scope prototype
 */
class Workspace {

	/**
	 * @var string
	 * @identity
	 * @validate StringLength(minimum = 1, maximum = 200)
	 */
	protected $name;

	/**
	 * Workspace (if any) this workspace is based on.
	 *
	 * Content from the base workspace will shine through in this workspace
	 * as long as they are not modified in this workspace.
	 *
	 * @var \F3\TYPO3CR\Domain\Model\Workspace
	 */
	protected $baseWorkspace;

	/**
	 * Root node of this workspace
	 *
	 * @var \F3\TYPO3CR\Domain\Model\Node
	 */
	protected $rootNode;

	/**
	 * @var \F3\TYPO3CR\Domain\Service\Context
	 * @transient
	 */
	protected $context;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructs a new workspace
	 *
	 * @param string $name Name of this workspace
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $baseWorkspace A workspace this workspace is based on (if any)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name, \F3\TYPO3CR\Domain\Model\Workspace $baseWorkspace = NULL) {
		$this->name = $name;
		$this->baseWorkspace = $baseWorkspace;
	}

	/**
	 * Initializes this workspace.
	 *
	 * If this workspace is brand new, a root node is created automatically.
	 *
	 * @param integer $initializationCause
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject($initializationCause) {
		if ($initializationCause === \F3\FLOW3\Object\Container\ObjectContainerInterface::INITIALIZATIONCAUSE_CREATED) {
			$this->rootNode = $this->objectManager->create('F3\TYPO3CR\Domain\Model\Node', '/', $this);
			$this->nodeRepository->add($this->rootNode);
		}
	}

	/**
	 * Returns the name of this workspace
	 *
	 * @return string Name of this workspace
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the base workspace, if any
	 *
	 * @return \F3\TYPO3CR\Domain\Model\Workspace
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseWorkspace() {
		return $this->baseWorkspace;
	}

	/**
	 * Returns the root node of this workspace
	 *
	 * @return \F3\TYPO3CR\Domain\Model\Node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRootNode() {
		return $this->rootNode;
	}

	/**
	 * Sets the context from which this workspace was acquired.
	 *
	 * This will be set by the context itself while retrieving the workspace via the
	 * context's getWorkspace() method. The context is transient and therefore needs to be
	 * set on every script run again.
	 *
	 * This method is only for internal use, don't mess with it.
	 *
	 * @param \F3\TYPO3CR\Domain\Service\Context $context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContext(\F3\TYPO3CR\Domain\Service\Context $context) {
		$this->context = $context;
		$this->rootNode->setContext($context);
	}

	/**
	 * Returns the current context this workspace operates in.
	 *
	 * @return \F3\TYPO3CR\Domain\Service\Context
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Publishes the content of this workspace to another workspace.
	 *
	 * The specified workspace must be a base workspace of this workspace.
	 *
	 * @param string $targetWorkspaceName Name of the workspace to publish to
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function publish($targetWorkspaceName) {
		$sourceNodes = $this->nodeRepository->findByWorkspace($this);
		$this->publishNodes($sourceNodes->toArray(), $targetWorkspaceName);
	}

	/**
	 * Publishes the given nodes to the target workspace.
	 *
	 * The specified workspace must be a base workspace of this workspace.
	 *
	 * @param array<\F3\TYPO3\Domain\Model\Node> $nodes
	 * @param string $targetWorkspaceName Name of the workspace to publish to
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function publishNodes(array $nodes, $targetWorkspaceName) {
		$targetWorkspace = $this->getPublishingTargetWorkspace($targetWorkspaceName);
		foreach ($nodes as $node) {
			if ($node->getPath() !== '/') {
				$targetNode = $this->nodeRepository->findOneByPath($node->getPath(), $targetWorkspace);
				if ($targetNode !== NULL) {
					$this->nodeRepository->remove($targetNode);
				}
				if ($node->isRemoved() === FALSE) {
					$node->setWorkspace($targetWorkspace);
				} else {
					$this->nodeRepository->remove($node);
				}
			}
		}
	}

	/**
	 * Returns the number of nodes in this workspace.
	 *
	 * If $includeBaseWorkspaces is enabled, also nodes of base workspaces are
	 * taken into account. If it is disabled (default) then the number of nodes
	 * is the actual number (+1) of changes related to its base workspaces.
	 *
	 * A node count of 1 means that no changes are pending in this workspace
	 * because a workspace always contains at least its Root Node.
	 *
	 * @param boolean $includeBaseWorkspaces If base workspaces should be taken into account
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodeCount() {
		return $this->nodeRepository->countByWorkspace($this);
	}

	/**
	 * Checks if the specified workspace is a base workspace of this workspace
	 * and if so, returns it.
	 *
	 * @param string $targetWorkspaceName Name of the target workspace
	 * @return \F3\TYPO3CR\Domain\Model\Workspace The target workspace
	 * @throws \F3\TYPO3CR\Exception\WorkspaceException if the specified workspace is not a base workspace of this workspace
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getPublishingTargetWorkspace($targetWorkspaceName) {
		$targetWorkspace = $this->baseWorkspace;
		while ($targetWorkspaceName !== $targetWorkspace->getName()) {
			$targetWorkspace = $targetWorkspace->getBaseWorkspace();
			if ($targetWorkspace === NULL) {
				throw new \F3\TYPO3CR\Exception\WorkspaceException('The specified workspace "' . $targetWorkspaceName . ' is not a base workspace of "' . $this->name . '".', 1289499117);
			}
		}
		return $targetWorkspace;
	}

}

?>
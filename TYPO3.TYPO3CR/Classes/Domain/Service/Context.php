<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Context
 *
 * @FLOW3\Scope("prototype")
 */
class Context implements \TYPO3\TYPO3CR\Domain\Service\ContextInterface {

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	protected $workspace;

	/**
	 * @var string
	 */
	protected $workspaceName;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $currentNode;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * If TRUE, invisible content elements will be shown.
	 *
	 * @var boolean
	 */
	protected $invisibleContentShown = FALSE;

	/**
	 * If TRUE, removed content elements will be shown, even though they are removed.
	 *
	 * @var boolean
	 */
	protected $removedContentShown = FALSE;

	/**
	 * If TRUE, even content elements will be shown which are not accessible by the currently logged in account.
	 *
	 * @var boolean
	 */
	protected $inaccessibleContentShown = FALSE;

	/**
	 * Constructs this context.
	 *
	 * @param string $workspaceName
	 */
	public function __construct($workspaceName) {
		$this->workspaceName = $workspaceName;
		$this->currentDateTime = new \DateTime();
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Repository\NodeRepository $nodeRepository
	 * @return void
	 */
	public function injectNodeRepository(\TYPO3\TYPO3CR\Domain\Repository\NodeRepository $nodeRepository) {
		$this->nodeRepository = $nodeRepository;
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository $workspaceRepository
	 * @return void
	 */
	public function injectWorkspaceRepository(\TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository $workspaceRepository) {
		$this->workspaceRepository = $workspaceRepository;
	}

	/**
	 * Returns the current workspace.
	 *
	 * @param boolean $createWorkspaceIfNecessary If enabled, creates a workspace with the configured name if it doesn't exist already
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace The workspace or NULL
	 */
	public function getWorkspace($createWorkspaceIfNecessary = TRUE) {
		if ($this->workspace === NULL) {
			$this->workspace = $this->workspaceRepository->findOneByName($this->workspaceName);
			if (!$this->workspace) {
				if ($createWorkspaceIfNecessary === FALSE) {
					return NULL;
				}
				$liveWorkspace = $this->workspaceRepository->findOneByName('live');
				if (!$liveWorkspace) {
					$liveWorkspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('live');
					$this->workspaceRepository->add($liveWorkspace);
				}
				if ($this->workspaceName === 'live') {
					$this->workspace = $liveWorkspace;
				} else {
					$this->workspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace($this->workspaceName, $liveWorkspace);
					$this->workspaceRepository->add($this->workspace);
				}
			}
		}
		return $this->workspace;
	}

	/**
	 * Returns the name of the workspace.
	 *
	 * @return string
	 */
	public function getWorkspaceName() {
		return $this->workspaceName;
	}

	/**
	 * Sets the current node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function setCurrentNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->currentNode = $node;
	}

	/**
	 * Returns the current node
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function getCurrentNode() {
		return $this->currentNode;
	}

	/**
	 * Returns the current date and time in form of a \DateTime
	 * object.
	 *
	 * If you use this method for getting the current date and time
	 * everywhere in your code, it will be possible to simulate a certain
	 * time in unit tests or in the actual application (for realizing previews etc).
	 *
	 * @return \DateTime The current date and time - or a simulated version of it
	 */
	public function getCurrentDateTime() {
		return $this->currentDateTime;
	}

	/**
	 * Sets the simulated date and time. This time will then always be returned
	 * by getCurrentDateTime().
	 *
	 * @param \DateTime $currentDateTime A date and time to simulate.
	 * @return void
	 */
	public function setCurrentDateTime(\DateTime $currentDateTime) {
		$this->currentDateTime = $currentDateTime;
	}

	/**
	 * Returns a node specified by the given absolute path.
	 *
	 * @param string $path Absolute path specifying the node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
	 */
	public function getNode($path) {
		if (!is_string($path) || $path[0] !== '/') {
			throw new \InvalidArgumentException('Only absolute paths are allowed for Context::getNode()', 1284975105);
		}
		return ($path === '/') ? $this->getWorkspace()->getRootNode() : $this->getWorkspace()->getRootNode()->getNode(substr($path, 1));
	}

	/**
	 * Finds all nodes lying on the path specified by (and including) the given
	 * starting point and end point.
	 *
	 * @param mixed $startingPoint Either an absolute path or an actual node specifying the starting point, for example /sites/mysite.com/
	 * @param mixed $endPoint Either an absolute path or an actual node specifying the end point, for example /sites/mysite.com/homepage/subpage
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> The nodes found between and including the given paths or an empty array of none were found
	 */
	public function getNodesOnPath($startingPoint, $endPoint) {
		$startingPointPath = ($startingPoint instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) ? $startingPoint->getPath() : $startingPoint;
		$endPointPath = ($endPoint instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) ? $endPoint->getPath() : $endPoint;

		$nodes = $this->nodeRepository->findOnPath($startingPointPath, $endPointPath, $this->workspace);
		return $nodes;
	}

	/**
	 * Returns this context as a "context path"
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->workspaceName . $this->currentNode->getPath();
	}

	/**
	 * @param boolean $invisibleContentShown If TRUE, invisible nodes are shown.
	 * @return void
	 */
	public function setInvisibleContentShown($invisibleContentShown) {
		$this->invisibleContentShown = $invisibleContentShown;
	}

	/**
	 * @return boolean
	 */
	public function isInvisibleContentShown() {
		return $this->invisibleContentShown;
	}

	/**
	 * @param boolean $removedContentShown If TRUE, removed nodes are shown
	 * @return void
	 */
	public function setRemovedContentShown($removedContentShown) {
		$this->removedContentShown = (boolean)$removedContentShown;
	}

	/**
	 * @return boolean
	 */
	public function isRemovedContentShown() {
		return $this->removedContentShown;
	}

	/**
	 * @param boolean $inaccessibleContentShown
	 * @return void
	 */
	public function setInaccessibleContentShown($inaccessibleContentShown) {
		$this->inaccessibleContentShown = (boolean)$inaccessibleContentShown;
	}

	/**
	 * @return boolean
	 */
	public function isInaccessibleContentShown() {
		return $this->inaccessibleContentShown;
	}

}
?>
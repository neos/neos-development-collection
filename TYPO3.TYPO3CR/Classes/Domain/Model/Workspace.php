<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A Workspace
 *
 * @FLOW3\Entity
 * @FLOW3\Scope("prototype")
 */
class Workspace {

	/**
	 * @var string
	 * @FLOW3\Identity
	 * @FLOW3\Validate(type="StringLength", options={ "minimum"=1, "maximum"=200 })
	 */
	protected $name;

	/**
	 * Workspace (if any) this workspace is based on.
	 *
	 * Content from the base workspace will shine through in this workspace
	 * as long as they are not modified in this workspace.
	 *
	 * @var \TYPO3\TYPO3CR\Domain\Model\Workspace
	 * @ORM\ManyToOne
	 */
	protected $baseWorkspace;

	/**
	 * Root node of this workspace
	 *
	 * @var \TYPO3\TYPO3CR\Domain\Model\Node
	 * @ORM\ManyToOne
	 * @ORM\JoinColumn(referencedColumnName="id")
	 */
	protected $rootNode;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\Context
	 * @FLOW3\Transient
	 */
	protected $context;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructs a new workspace
	 *
	 * @param string $name Name of this workspace
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $baseWorkspace A workspace this workspace is based on (if any)
	 */
	public function __construct($name, \TYPO3\TYPO3CR\Domain\Model\Workspace $baseWorkspace = NULL) {
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
	 */
	public function initializeObject($initializationCause) {
		if ($initializationCause === \TYPO3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
			$this->rootNode = new Node('/', $this);
			$this->nodeRepository->add($this->rootNode);
		}
	}

	/**
	 * Returns the name of this workspace
	 *
	 * @return string Name of this workspace
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the base workspace, if any
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	public function getBaseWorkspace() {
		return $this->baseWorkspace;
	}

	/**
	 * Returns the root node of this workspace
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 */
	public function getRootNode() {
		return $this->rootNode;
	}

	/**
	 * Returns the current context this workspace operates in.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	public function getContext() {
		return $this->nodeRepository->getContext();
	}

	/**
	 * Publishes the content of this workspace to another workspace.
	 *
	 * The specified workspace must be a base workspace of this workspace.
	 *
	 * @param string $targetWorkspaceName Name of the workspace to publish to
	 * @return void
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
	 * @param array<\TYPO3\TYPO3\Domain\Model\Node> $nodes
	 * @param string $targetWorkspaceName Name of the workspace to publish to
	 * @return void
	 */
	public function publishNodes(array $nodes, $targetWorkspaceName) {
		$targetWorkspace = $this->getPublishingTargetWorkspace($targetWorkspaceName);
		foreach ($nodes as $node) {
			if ($node->getPath() !== '/') {
				$targetNode = $this->nodeRepository->findOneByIdentifier($node->getIdentifier(), $targetWorkspace);
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
	 * @return integer
	 */
	public function getNodeCount() {
		return $this->nodeRepository->countByWorkspace($this);
	}

	/**
	 * Checks if the specified workspace is a base workspace of this workspace
	 * and if so, returns it.
	 *
	 * @param string $targetWorkspaceName Name of the target workspace
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace The target workspace
	 * @throws \TYPO3\TYPO3CR\Exception\WorkspaceException if the specified workspace is not a base workspace of this workspace
	 */
	protected function getPublishingTargetWorkspace($targetWorkspaceName) {
		$targetWorkspace = $this->baseWorkspace;
		while ($targetWorkspaceName !== $targetWorkspace->getName()) {
			$targetWorkspace = $targetWorkspace->getBaseWorkspace();
			if ($targetWorkspace === NULL) {
				throw new \TYPO3\TYPO3CR\Exception\WorkspaceException('The specified workspace "' . $targetWorkspaceName . ' is not a base workspace of "' . $this->name . '".', 1289499117);
			}
		}
		return $targetWorkspace;
	}
}

?>
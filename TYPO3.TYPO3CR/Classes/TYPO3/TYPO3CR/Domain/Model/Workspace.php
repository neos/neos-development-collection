<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\TYPO3CR\Exception\WorkspaceException;

/**
 * A Workspace
 *
 * @Flow\Entity
 * @api
 */
class Workspace {

	/**
	 * @var string
	 * @Flow\Identity
	 * @ORM\Id
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=200 })
	 */
	protected $name;

	/**
	 * Workspace (if any) this workspace is based on.
	 *
	 * Content from the base workspace will shine through in this workspace
	 * as long as they are not modified in this workspace.
	 *
	 * @var Workspace
	 * @ORM\ManyToOne
	 * @ORM\JoinColumn(onDelete="SET NULL")
	 */
	protected $baseWorkspace;

	/**
	 * Root node data of this workspace
	 *
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeData
	 * @ORM\ManyToOne
	 * @ORM\JoinColumn(referencedColumnName="id")
	 */
	protected $rootNodeData;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\PublishingService
	 */
	protected $publishingService;

	/**
	 * Constructs a new workspace
	 *
	 * @param string $name Name of this workspace
	 * @param Workspace $baseWorkspace A workspace this workspace is based on (if any)
	 * @api
	 */
	public function __construct($name, Workspace $baseWorkspace = NULL) {
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
		if ($initializationCause === ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED) {
			$this->rootNodeData = new NodeData('/', $this);
			$this->nodeDataRepository->add($this->rootNodeData);
		}
	}

	/**
	 * Returns the name of this workspace
	 *
	 * @return string Name of this workspace
	 * @api
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the base workspace, if any
	 *
	 * @return Workspace
	 * @api
	 */
	public function getBaseWorkspace() {
		return $this->baseWorkspace;
	}

	/**
	 * Returns the root node data of this workspace
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeData
	 */
	public function getRootNodeData() {
		return $this->rootNodeData;
	}

	/**
	 * Publishes the content of this workspace to another workspace.
	 *
	 * The specified workspace must be a base workspace of this workspace.
	 *
	 * @param Workspace $targetWorkspace The workspace to publish to
	 * @return void
	 * @api
	 */
	public function publish(Workspace $targetWorkspace) {
		$sourceNodes = $this->publishingService->getUnpublishedNodes($this);
		$this->publishNodes($sourceNodes, $targetWorkspace);
	}

	/**
	 * Publishes the given nodes to the target workspace.
	 *
	 * The specified workspace must be a base workspace of this workspace.
	 *
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
	 * @param Workspace $targetWorkspace The workspace to publish to
	 * @return void
	 * @api
	 */
	public function publishNodes(array $nodes, Workspace $targetWorkspace) {
		foreach ($nodes as $node) {
			$this->publishNode($node, $targetWorkspace);
		}
	}

	/**
	 * Publishes the given node to the target workspace.
	 *
	 * The specified workspace must be a base workspace of this workspace.
	 *
	 * @param NodeInterface $node
	 * @param Workspace $targetWorkspace The workspace to publish to
	 * @return void
	 * @api
	 */
	public function publishNode(NodeInterface $node, Workspace $targetWorkspace) {
		if ($this->baseWorkspace === NULL) {
			return;
		}
		if ($node->getWorkspace() !== $this) {
			return;
		}
		$this->verifyPublishingTargetWorkspace($targetWorkspace);
		$this->emitBeforeNodePublishing($node, $targetWorkspace);
		if ($node->getPath() === '/') {
			return;
		}
		$targetNode = $this->nodeDataRepository->findOneByIdentifier($node->getIdentifier(), $targetWorkspace);
		if ($targetNode !== NULL) {
				// We have to set the workspace to NULL to resolve duplicate key issues when updating the published node
			$targetNode->setWorkspace(NULL);

			$this->nodeDataRepository->remove($targetNode);
		}
		if ($node->isRemoved() === FALSE) {
			$node->setWorkspace($targetWorkspace);
		} else {
			$this->nodeDataRepository->remove($node);
		}
		$this->emitAfterNodePublishing($node, $targetWorkspace);
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
	 * @api
	 */
	public function getNodeCount() {
		return $this->nodeDataRepository->countByWorkspace($this);
	}

	/**
	 * Checks if the specified workspace is a base workspace of this workspace
	 * and if not, throws an exception
	 *
	 * @param Workspace $targetWorkspace The publishing target workspace
	 * @return void
	 * @throws WorkspaceException if the specified workspace is not a base workspace of this workspace
	 */
	protected function verifyPublishingTargetWorkspace(Workspace $targetWorkspace) {
		$baseWorkspace = $this->baseWorkspace;
		while ($targetWorkspace !== $baseWorkspace) {
			if ($baseWorkspace === NULL) {
				throw new WorkspaceException(sprintf('The specified workspace "%s" is not a base workspace of "%s".', $targetWorkspace->getName(), $this->getName()), 1289499117);
			}
			$baseWorkspace = $baseWorkspace->getBaseWorkspace();
		}
	}

	/**
	 * Emits a signal just before a node is being published
	 *
	 * @param NodeInterface $node The node to be published
	 * @param Workspace $targetWorkspace The publishing target workspace
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitBeforeNodePublishing(NodeInterface $node, Workspace $targetWorkspace) {}

	/**
	 * Emits a signal when a node has been published
	 *
	 * @param NodeInterface $node The node that was published
	 * @param Workspace $targetWorkspace The publishing target workspace
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitAfterNodePublishing(NodeInterface $node, Workspace $targetWorkspace) {}

}

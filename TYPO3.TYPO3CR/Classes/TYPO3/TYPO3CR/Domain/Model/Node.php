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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheAwareInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Exception\NodeConstraintException;
use TYPO3\TYPO3CR\Exception\NodeException;
use TYPO3\TYPO3CR\Exception\NodeExistsException;

/**
 * This is the main API for storing and retrieving content in the system.
 *
 * @Flow\Scope("prototype")
 * @api
 */
class Node implements NodeInterface, CacheAwareInterface {

	/**
	 * The NodeData entity this version is for.
	 *
	 * @var NodeData
	 */
	protected $nodeData;

	/**
	 * @var Context
	 */
	protected $context;

	/**
	 * Defines if the NodeData represented by this Node is already
	 * in the same context or if it is currently just "shining through".
	 *
	 * @var boolean
	 */
	protected $nodeDataIsMatchingContext = NULL;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @param NodeData $nodeData
	 * @param Context $context
	 * @throws \InvalidArgumentException if you give a Node as originalNode.
	 * @Flow\Autowiring(false)
	 */
	public function __construct(NodeData $nodeData, Context $context) {
		$this->nodeData = $nodeData;
		$this->context = $context;
	}

	/**
	 * Returns the absolute path of this node with additional context information (such as the workspace name).
	 *
	 * Example: /sites/mysitecom/homepage/about@user-admin
	 *
	 * @return string Node path with context information
	 * @api
	 */
	public function getContextPath() {
		$contextPath = $this->nodeData->getPath();
		$workspaceName = $this->context->getWorkspace()->getName();

		$contextPath .= '@' . $workspaceName;

		if ($this->context->getDimensions() !== array()) {
			$contextPath .= ';';
			foreach ($this->context->getDimensions() as $dimensionName => $dimensionValues) {
				$contextPath .= $dimensionName . '=' . implode(',', $dimensionValues) . '&';
			}
			$contextPath = substr($contextPath, 0, -1);
		}
		return $contextPath;
	}

	/**
	 * Set the name of the node to $newName, keeping its position as it is.
	 *
	 * @param string $newName
	 * @return void
	 * @throws NodeException if you try to set the name of the root node.
	 * @throws \InvalidArgumentException if $newName is invalid
	 * @api
	 */
	public function setName($newName) {
		if (!is_string($newName) || preg_match(NodeInterface::MATCH_PATTERN_NAME, $newName) !== 1) {
			throw new \InvalidArgumentException('Invalid node name "' . $newName . '" (a node name must only contain characters, numbers and the "-" sign).', 1364290748);
		}

		if ($this->getPath() === '/') {
			throw new NodeException('The root node cannot be renamed.', 1346778388);
		}

		if ($this->getName() === $newName) {
			return;
		}

		$this->setPath($this->getParentPath() . ($this->getParentPath() === '/' ? '' : '/') . $newName);
		$this->nodeDataRepository->persistEntities();
		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Sets the absolute path of this node.
	 *
	 * This method is only for internal use by the content repository or node methods. Changing
	 * the path of a node manually may lead to unexpected behavior.
	 *
	 * To achieve a correct behavior when changing the path (moving the node) in a workspace, a shadow node data that will
	 * hide the node data in the base workspace will be created. Thus queries do not need to worry about moved nodes.
	 * Through a movedTo reference the shadow node data will be removed when publishing the moved node.
	 *
	 * @param string $path
	 * @return void
	 * @throws NodeException
	 */
	public function setPath($path) {
		if ($this->getPath() === $path) {
			return;
		}
		$existingNodeData = $this->nodeDataRepository->findByPathWithoutReduce($path, $this->context->getWorkspace());
		if ($existingNodeData !== array()) {
			throw new NodeException(sprintf('Can not rename the node "%s" as a node already exists on path "%s"', $this->getPath(), $path), 1414436551);
		}

		$this->setPathInternal($path);
	}

	/**
	 * Internal setPath method, will not check for existence of node at path
	 *
	 * @param string $path
	 * @return void
	 */
	protected function setPathInternal($path) {
		$originalPath = $this->nodeData->getPath();
		if ($originalPath === $path) {
			return;
		}

		if ($this->getNodeType()->isAggregate()) {
			$nodeDataVariants = $this->nodeDataRepository->findByPathWithoutReduce($originalPath, $this->context->getWorkspace(), TRUE, TRUE);

			/** @var NodeData $nodeData */
			foreach ($nodeDataVariants as $nodeData) {
				$nodeVariant = $this->createNodeForVariant($nodeData);
				$pathSuffix = substr($nodeVariant->getPath(), strlen($originalPath));
				$nodeVariant->moveNodeData($path . $pathSuffix);
			}
		} else {
			/** @var Node $childNode */
			foreach ($this->getChildNodes() as $childNode) {
				$childNode->setPathInternal($path . '/' . $childNode->getName());
			}
			$this->moveNodeData($path);
		}
	}

	/**
	 * Move the NodeData of this node
	 *
	 * Basically 4 scenarios have to be covered here, depending on:
	 *
	 * - Does the NodeData have to be materialized (adapted to the workspace or target dimension)?
	 * - Does a shadow node exist on the target path?
	 *
	 * Because unique key constraints and Doctrine ORM don't support arbitrary removal and update combinations,
	 * existing NodeData instances are re-used and the metadata and content is swapped around.
	 *
	 * @param string $path
	 * @return void
	 */
	protected function moveNodeData($path) {
		$nodeDataWasMaterialized = FALSE;
		$nodeData = $this->nodeData;
		$originalPath = $this->nodeData->getPath();

		if ($nodeData->getWorkspace()->getName() !== $this->context->getWorkspace()->getName()) {
			$nodeData = $this->materializeNodeDataToWorkspace($nodeData);
			$nodeDataWasMaterialized = TRUE;
			$targetPathShadowNodeData = $this->getExistingShadowNodeData($path, $nodeData);
			$nodeData->setPath($path, FALSE);

			$this->nodeData = $nodeData;
		} else {
			$targetPathShadowNodeData = $this->getExistingShadowNodeData($path, $nodeData);
		}

		if ($nodeDataWasMaterialized) {
			if ($targetPathShadowNodeData !== NULL) {
				// The existing shadow node on the target path will be used as the moved node and the current node data will be removed
				$movedNodeData = $targetPathShadowNodeData;
				$movedNodeData->setMovedTo(NULL);
				$movedNodeData->setRemoved(FALSE);
				$movedNodeData->similarize($nodeData);
				$movedNodeData->setPath($path, FALSE);
				$this->nodeDataRepository->remove($nodeData);

				// A new shadow node will be created for the node data that references the recycled, existing shadow node
				$shadowNode = $this->createShadowNodeData($originalPath, $nodeData);
				$shadowNode->setMovedTo($movedNodeData);
			} else {
				// If a node data was materialized before moving, we need to create a shadow node
				$this->createShadowNodeData($originalPath, $nodeData);
			}
		} else {
			$referencedShadowNode = $this->nodeDataRepository->findOneByMovedTo($nodeData);
			if ($targetPathShadowNodeData !== NULL) {
				if ($referencedShadowNode === NULL) {
					// Turn the target path shadow node into the moved node (and adjust the identifier!)
					// Do not reset the movedTo property to keep tracing the original move operation
					$movedNodeData = $targetPathShadowNodeData;
					// Since the shadow node at the target path does not belong to the current node, we have to adjust the identifier
					$movedNodeData->setIdentifier($nodeData->getIdentifier());
					$movedNodeData->setRemoved(FALSE);
					$movedNodeData->similarize($nodeData);
					$movedNodeData->setPath($path, FALSE);

					// Create a shadow node from the current node that shadows the recycled node
					$shadowNodeData = $nodeData;
					$shadowNodeData->shadowMovedNodeData($movedNodeData);
				} else {
					// If there is already shadow node on the target path, we need to make that shadow node the actual moved node and remove the current node data (which cannot be live).
					// We cannot remove the shadow node and update the current node data, since the order of Doctrine queries would cause unique key conflicts.
					$movedNodeData = $targetPathShadowNodeData;
					$movedNodeData->setMovedTo(NULL);
					$movedNodeData->setRemoved(FALSE);
					$movedNodeData->similarize($nodeData);
					$movedNodeData->setPath($path, FALSE);
					$this->nodeDataRepository->remove($nodeData);
				}
			} else {
				if ($referencedShadowNode === NULL) {
					// There is no shadow node on the original or target path, so the current node data will be turned to a shadow node and a new node data will be created for the moved node.
					// We cannot just create a new shadow node, since the order of Doctrine queries would cause unique key conflicts
					$movedNodeData = new NodeData($originalPath, $nodeData->getWorkspace(), $nodeData->getIdentifier(), $nodeData->getDimensionValues());
					$movedNodeData->similarize($nodeData);
					$movedNodeData->setPath($path, FALSE);

					$shadowNodeData = $nodeData;
					$shadowNodeData->shadowMovedNodeData($movedNodeData);
				} else {
					// A shadow node that references this node data already exists, so we just move the current node data
					$movedNodeData = $nodeData;
					$movedNodeData->setPath($path, FALSE);
				}
			}

			$this->nodeData = $movedNodeData;
		}
	}

	/**
	 * Materializes the original node data (of a different workspace) into the current
	 * workspace, excluding content dimensions
	 *
	 * This is only used in setPath for now
	 *
	 * @param NodeData $nodeData
	 * @return NodeData
	 */
	protected function materializeNodeDataToWorkspace(NodeData $nodeData) {
		$newNodeData = new NodeData($nodeData->getPath(), $this->context->getWorkspace(), $nodeData->getIdentifier(), $nodeData->getDimensionValues());
		$this->nodeDataRepository->add($newNodeData);

		$newNodeData->similarize($nodeData);
		return $newNodeData;
	}

	/**
	 * Find an existing shadow node data on the given path for the current node data of the node (used by setPath)
	 *
	 * @param string $path The (new) path of the node data
	 * @param NodeData $nodeData
	 * @return NodeData
	 */
	protected function getExistingShadowNodeData($path, NodeData $nodeData) {
		$existingShadowNode = $this->nodeDataRepository->findOneByPath($path, $nodeData->getWorkspace(), $nodeData->getDimensionValues(), TRUE);
		if ($existingShadowNode !== NULL && $existingShadowNode->getMovedTo() !== NULL) {
			return $existingShadowNode;
		}
		return NULL;
	}

	/**
	 * Create a shadow node data with the same workspace and dimensions as the materialized current node data (used by setPath)
	 *
	 * Note: The constructor will already add the new object to the repository
	 *
	 * @param string $path The (original) path for the node data
	 * @param NodeData $nodeData
	 * @return NodeData
	 */
	protected function createShadowNodeData($path, NodeData $nodeData) {
		$shadowNode = new NodeData($path, $nodeData->getWorkspace(), $nodeData->getIdentifier(), $nodeData->getDimensionValues());
		$shadowNode->similarize($nodeData);
		$shadowNode->shadowMovedNodeData($nodeData);
		return $shadowNode;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getOtherNodeVariants() {
		$otherNodeVariants = array();
		$allNodeVariants = $this->context->getNodeVariantsByIdentifier($this->getIdentifier());
		foreach ($allNodeVariants as $index => $node) {
			if ($node->getNodeData() !== $this->nodeData) {
				$otherNodeVariants[] = $node;
			}
		}
		return $otherNodeVariants;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreationDateTime() {
		return $this->nodeData->getCreationDateTime();
	}

	/**
	 * @return \DateTime
	 */
	public function getLastModificationDateTime() {
		return $this->nodeData->getLastModificationDateTime();
	}

	/**
	 * @param \DateTime $lastModificationDateTime
	 * @return void
	 */
	public function setLastPublicationDateTime(\DateTime $lastModificationDateTime) {
		$this->nodeData->setLastPublicationDateTime($lastModificationDateTime);
	}

	/**
	 * @return \DateTime
	 */
	public function getLastPublicationDateTime() {
		return $this->nodeData->getLastPublicationDateTime();
	}

	/**
	 * Returns the path of this node
	 *
	 * @return string
	 * @api
	 */
	public function getPath() {
		return $this->nodeData->getPath();
	}

	/**
	 * Returns the level at which this node is located.
	 * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
	 *
	 * @return integer
	 * @api
	 */
	public function getDepth() {
		return $this->nodeData->getDepth();
	}

	/**
	 * Returns the name of this node
	 *
	 * @return string
	 * @api
	 */
	public function getName() {
		return $this->nodeData->getName();
	}

	/**
	 * Returns the node label as generated by the configured node label generator
	 *
	 * @return string
	 */
	public function getLabel() {
		// TODO: remove second argument after deprecation phase ends for cropping.
		return $this->getNodeType()->getNodeLabelGenerator()->getLabel($this, FALSE);
	}

	/**
	 * Returns a full length plain text description of this node
	 *
	 * @return string
	 * @deprecated since 1.2
	 */
	public function getFullLabel() {
		// TODO: remove second argument after deprecation phase ends for cropping.
		return $this->getNodeType()->getNodeLabelGenerator()->getLabel($this, FALSE);
	}

	/**
	 * Sets the workspace of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the workspace of a node manually may lead to unexpected behavior.
	 *
	 * @param Workspace $workspace
	 * @return void
	 */
	public function setWorkspace(Workspace $workspace) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($this->getWorkspace()->getName() === $workspace->getName()) {
			return;
		}
		$this->nodeData->setWorkspace($workspace);
		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns the workspace this node is contained in
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
	 * @api
	 */
	public function getWorkspace() {
		return $this->nodeData->getWorkspace();
	}

	/**
	 * Returns the identifier of this node
	 *
	 * @return string the node's UUID (unique within the workspace)
	 * @api
	 */
	public function getIdentifier() {
		return $this->nodeData->getIdentifier();
	}

	/**
	 * Sets the index of this node
	 *
	 * NOTE: This method is meant for internal use and must only be used by other nodes.
	 *
	 * @param integer $index The new index
	 * @return void
	 */
	public function setIndex($index) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($this->getIndex() === $index) {
			return;
		}
		$this->nodeData->setIndex($index);
		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns the index of this node which determines the order among siblings
	 * with the same parent node.
	 *
	 * @return integer
	 */
	public function getIndex() {
		return $this->nodeData->getIndex();
	}

	/**
	 * Returns the parent node of this node
	 *
	 * @return NodeInterface The parent node or NULL if this is the root node
	 * @api
	 */
	public function getParent() {
		if ($this->getPath() === '/') {
			return NULL;
		}

		$parentPath = $this->getParentPath();
		$node = $this->context->getFirstLevelNodeCache()->getByPath($parentPath);
		if ($node !== FALSE) {
			return $node;
		}
		$node = $this->nodeDataRepository->findOneByPathInContext($parentPath, $this->context);
		$this->context->getFirstLevelNodeCache()->setByPath($parentPath, $node);
		return $node;
	}

	/**
	 * Returns the parent node path
	 *
	 * @return string Absolute node path of the parent node
	 * @api
	 */
	public function getParentPath() {
		return $this->nodeData->getParentPath();
	}

	/**
	 * Moves this node before the given node
	 *
	 * @param NodeInterface $referenceNode
	 * @return void
	 * @throws NodeException if you try to move the root node
	 * @throws NodeExistsException
	 * @throws NodeConstraintException if a node constraint prevents moving the node
	 * @api
	 */
	public function moveBefore(NodeInterface $referenceNode) {
		if ($referenceNode === $this) {
			return;
		}

		if ($this->getPath() === '/') {
			throw new NodeException('The root node cannot be moved.', 1285005924);
		}

		if ($referenceNode->getParent() !== $this->getParent() && $referenceNode->getParent()->getNode($this->getName()) !== NULL) {
			throw new NodeExistsException('Node with path "' . $this->getName() . '" already exists.', 1292503468);
		}

		if (!$referenceNode->getParent()->willChildNodeBeAutoCreated($this->getName()) && !$referenceNode->getParent()->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
			throw new NodeConstraintException('Cannot move ' . $this->__toString() . ' before ' . $referenceNode->__toString(), 1400782413);
		}

		$this->emitBeforeNodeMove($this, $referenceNode, NodeDataRepository::POSITION_BEFORE);
		if ($referenceNode->getParentPath() !== $this->getParentPath()) {
			$parentPath = $referenceNode->getParentPath();
			$this->setPath($parentPath . ($parentPath === '/' ? '' : '/') . $this->getName());
			$this->nodeDataRepository->persistEntities();
		} else {
			if (!$this->isNodeDataMatchingContext()) {
				$this->materializeNodeData();
			}
		}

		$this->nodeDataRepository->setNewIndex($this->nodeData, NodeDataRepository::POSITION_BEFORE, $referenceNode);
		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitAfterNodeMove($this, $referenceNode, NodeDataRepository::POSITION_BEFORE);
		$this->emitNodeUpdated($this);
	}

	/**
	 * Moves this node after the given node
	 *
	 * @param NodeInterface $referenceNode
	 * @return void
	 * @throws NodeExistsException
	 * @throws NodeException
	 * @throws NodeConstraintException if a node constraint prevents moving the node
	 * @api
	 */
	public function moveAfter(NodeInterface $referenceNode) {
		if ($referenceNode === $this) {
			return;
		}

		if ($this->getPath() === '/') {
			throw new NodeException('The root node cannot be moved.', 1316361483);
		}

		if ($referenceNode->getParent() !== $this->getParent() && $referenceNode->getParent()->getNode($this->getName()) !== NULL) {
			throw new NodeExistsException('Node with path "' . $this->getName() . '" already exists.', 1292503469);
		}

		if (!$referenceNode->getParent()->willChildNodeBeAutoCreated($this->getName()) && !$referenceNode->getParent()->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
			throw new NodeConstraintException('Cannot move ' . $this->__toString() . ' after ' . $referenceNode->__toString(), 1404648100);
		}

		$this->emitBeforeNodeMove($this, $referenceNode, NodeDataRepository::POSITION_AFTER);
		if ($referenceNode->getParentPath() !== $this->getParentPath()) {
			$parentPath = $referenceNode->getParentPath();
			$this->setPath($parentPath . ($parentPath === '/' ? '' : '/') . $this->getName());
			$this->nodeDataRepository->persistEntities();
		} else {
			if (!$this->isNodeDataMatchingContext()) {
				$this->materializeNodeData();
			}
		}

		$this->nodeDataRepository->setNewIndex($this->nodeData, NodeDataRepository::POSITION_AFTER, $referenceNode);
		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitAfterNodeMove($this, $referenceNode, NodeDataRepository::POSITION_AFTER);
		$this->emitNodeUpdated($this);
	}

	/**
	 * Moves this node into the given node
	 *
	 * @param NodeInterface $referenceNode
	 * @return void
	 * @throws NodeExistsException
	 * @throws NodeException
	 * @throws NodeConstraintException
	 * @api
	 */
	public function moveInto(NodeInterface $referenceNode) {
		if ($referenceNode === $this || $referenceNode === $this->getParent()) {
			return;
		}

		if ($this->getPath() === '/') {
			throw new NodeException('The root node cannot be moved.', 1346769001);
		}

		if ($referenceNode !== $this->getParent() && $referenceNode->getNode($this->getName()) !== NULL) {
			throw new NodeExistsException('Node with path "' . $this->getName() . '" already exists.', 1292503470);
		}

		if (!$referenceNode->willChildNodeBeAutoCreated($this->getName()) && !$referenceNode->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
			throw new NodeConstraintException('Cannot move ' . $this->__toString() . ' into ' . $referenceNode->__toString(), 1404648124);
		}

		$this->emitBeforeNodeMove($this, $referenceNode, NodeDataRepository::POSITION_LAST);
		$parentPath = $referenceNode->getPath();
		$this->setPath($parentPath . ($parentPath === '/' ? '' : '/') . $this->getName());
		$this->nodeDataRepository->persistEntities();

		$this->nodeDataRepository->setNewIndex($this->nodeData, NodeDataRepository::POSITION_LAST);
		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitAfterNodeMove($this, $referenceNode, NodeDataRepository::POSITION_LAST);
		$this->emitNodeUpdated($this);
	}

	/**
	 * @Flow\Signal
	 * @param NodeInterface $movedNode
	 * @param NodeInterface $referenceNode
	 * @param integer $movePosition
	 * @return void
	 */
	protected function emitBeforeNodeMove($movedNode, $referenceNode, $movePosition) {
	}

	/**
	 * @Flow\Signal
	 * @param NodeInterface $movedNode
	 * @param NodeInterface $referenceNode
	 * @param integer $movePosition
	 * @return void
	 */
	protected function emitAfterNodeMove($movedNode, $referenceNode, $movePosition) {
	}

	/**
	 * Copies this node before the given node
	 *
	 * @param NodeInterface $referenceNode
	 * @param string $nodeName
	 * @return NodeInterface
	 * @throws NodeExistsException
	 * @throws NodeConstraintException
	 * @api
	 */
	public function copyBefore(NodeInterface $referenceNode, $nodeName) {
		if ($referenceNode->getParent()->getNode($nodeName) !== NULL) {
			throw new NodeExistsException('Node with path "' . $referenceNode->getParent()->getPath() . '/' . $nodeName . '" already exists.', 1292503465);
		}

		if (!$referenceNode->getParent()->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
			throw new NodeConstraintException('Cannot copy ' . $this->__toString() . ' before ' . $referenceNode->__toString(), 1402050232);
		}

		$this->emitBeforeNodeCopy($this, $referenceNode->getParent());
		$copiedNode = $this->createRecursiveCopy($referenceNode->getParent(), $nodeName, $this->getNodeType()->isAggregate());
		$copiedNode->moveBefore($referenceNode);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($copiedNode);
		$this->emitAfterNodeCopy($copiedNode, $referenceNode->getParent());

		return $copiedNode;
	}

	/**
	 * @Flow\Signal
	 * @param NodeInterface $node
	 * @return void
	 */
	protected function emitBeforeNodeCopy(NodeInterface $sourceNode, NodeInterface $targetParentNode) {
	}

	/**
	 * @Flow\Signal
	 * @param NodeInterface $node
	 * @return void
	 */
	protected function emitAfterNodeCopy(NodeInterface $copiedNode, NodeInterface $targetParentNode) {
	}

	/**
	 * Copies this node after the given node
	 *
	 * @param NodeInterface $referenceNode
	 * @param string $nodeName
	 * @return NodeInterface
	 * @throws NodeExistsException
	 * @throws NodeConstraintException
	 * @api
	 */
	public function copyAfter(NodeInterface $referenceNode, $nodeName) {
		if ($referenceNode->getParent()->getNode($nodeName) !== NULL) {
			throw new NodeExistsException('Node with path "' . $referenceNode->getParent()->getPath() . '/' . $nodeName . '" already exists.', 1292503466);
		}

		if (!$referenceNode->getParent()->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
			throw new NodeConstraintException('Cannot copy ' . $this->__toString() . ' after ' . $referenceNode->__toString(), 1404648170);
		}

		$this->emitBeforeNodeCopy($this, $referenceNode->getParent());
		$copiedNode = $this->createRecursiveCopy($referenceNode->getParent(), $nodeName, $this->getNodeType()->isAggregate());
		$copiedNode->moveAfter($referenceNode);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($copiedNode);
		$this->emitAfterNodeCopy($copiedNode, $referenceNode->getParent());

		return $copiedNode;
	}

	/**
	 * Copies this node into the given node
	 *
	 * @param NodeInterface $referenceNode
	 * @param string $nodeName
	 * @return NodeInterface
	 * @throws NodeExistsException
	 * @throws NodeConstraintException
	 * @api
	 */
	public function copyInto(NodeInterface $referenceNode, $nodeName) {
		$this->emitBeforeNodeCopy($this, $referenceNode);
		$copiedNode = $this->copyIntoInternal($referenceNode, $nodeName, $this->getNodeType()->isAggregate());
		$this->emitAfterNodeCopy($this, $referenceNode);

		return $copiedNode;
	}

	/**
	 * Internal method to do the actual copying.
	 *
	 * For behavior of the $detachedCopy parameter, see method Node::createRecursiveCopy().
	 *
	 * @param NodeInterface $referenceNode
	 * @param $nodeName
	 * @param boolean $detachedCopy
	 * @return NodeInterface
	 * @throws NodeConstraintException
	 * @throws NodeExistsException
	 */
	protected function copyIntoInternal(NodeInterface $referenceNode, $nodeName, $detachedCopy) {
		if ($referenceNode->getNode($nodeName) !== NULL) {
			throw new NodeExistsException('Node with path "' . $referenceNode->getPath() . '/' . $nodeName . '" already exists.', 1292503467);
		}

		// On copy we basically re-recreate an existing node on a new location. As we skip the constraints check on
		// node creation we should do the same while writing the node on the new location.
		if (!$referenceNode->willChildNodeBeAutoCreated($nodeName) && !$referenceNode->isNodeTypeAllowedAsChildNode($this->getNodeType())) {
			throw new NodeConstraintException(sprintf('Cannot copy "%s" into "%s" due to node type constraints.', $this->__toString(), $referenceNode->__toString()), 1404648177);
		}

		$copiedNode = $this->createRecursiveCopy($referenceNode, $nodeName, $detachedCopy);

		$this->context->getFirstLevelNodeCache()->flush();

		$this->emitNodeAdded($copiedNode);

		return $copiedNode;
	}

	/**
	 * Sets the specified property.
	 *
	 * If the node has a content object attached, the property will be set there
	 * if it is settable.
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $value Value of the property
	 * @return mixed
	 * @api
	 */
	public function setProperty($propertyName, $value) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		// Arrays could potentially contain entities and objects could be entities. In that case even if the object is the same it needs to be persisted in NodeData.
		if (!is_object($value) && !is_array($value) && $this->getProperty($propertyName) === $value) {
			return;
		}
		$oldValue = $this->hasProperty($propertyName) ? $this->getProperty($propertyName) : NULL;
		$this->emitBeforeNodePropertyChange($this, $propertyName, $oldValue, $value);
		$this->nodeData->setProperty($propertyName, $value);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodePropertyChanged($this, $propertyName, $oldValue, $value);
		$this->emitNodeUpdated($this);
	}

	/**
	 * If this node has a property with the given name.
	 *
	 * If the node has a content object attached, the property will be checked
	 * there.
	 *
	 * @param string $propertyName
	 * @return boolean
	 * @api
	 */
	public function hasProperty($propertyName) {
		return $this->nodeData->hasProperty($propertyName);
	}

	/**
	 * Returns the specified property.
	 *
	 * If the node has a content object attached, the property will be fetched
	 * there if it is gettable.
	 *
	 * @param string $propertyName Name of the property
	 * @param boolean $returnNodesAsIdentifiers If enabled, references to nodes are returned as node identifiers instead of NodeData objects
	 * @return mixed value of the property
	 * @api
	 */
	public function getProperty($propertyName, $returnNodesAsIdentifiers = FALSE) {
		$value = $this->nodeData->getProperty($propertyName, $returnNodesAsIdentifiers, $this->context);
		if (!empty($value)) {
			$nodeType = $this->getNodeType();
			if ($nodeType->hasConfiguration('properties.' . $propertyName)) {
				$expectedPropertyType = $nodeType->getPropertyType($propertyName);
				switch ($expectedPropertyType) {
					case 'references' :
						if ($returnNodesAsIdentifiers === FALSE) {
							$nodes = array();
							foreach ($value as $nodeData) {
								$node = $this->nodeFactory->createFromNodeData($nodeData, $this->context);
								// $node can be NULL if the node is not visible according to the current content context:
								if ($node !== NULL) {
									$nodes[] = $node;
								}
							}
							$value = $nodes;
						}
						break;
					case 'reference' :
						if ($returnNodesAsIdentifiers === FALSE) {
							$value = $this->nodeFactory->createFromNodeData($value, $this->context);
						}
						break;
					default:
						$value = $this->propertyMapper->convert($value, $expectedPropertyType);
						break;
				}
			}
		}
		return $value;
	}

	/**
	 * Removes the specified property.
	 *
	 * If the node has a content object attached, the property will not be removed on
	 * that object if it exists.
	 *
	 * @param string $propertyName Name of the property
	 * @return void
	 * @throws NodeException if the node does not contain the specified property
	 */
	public function removeProperty($propertyName) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if (!$this->hasProperty($propertyName)) {
			return;
		}
		$this->nodeData->removeProperty($propertyName);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns all properties of this node.
	 *
	 * If the node has a content object attached, the properties will be fetched
	 * there.
	 *
	 * @param boolean $returnNodesAsIdentifiers If enabled, references to nodes are returned as node identifiers instead of NodeData objects
	 * @return array Property values, indexed by their name
	 * @api
	 */
	public function getProperties($returnNodesAsIdentifiers = FALSE) {
		$properties = array();
		foreach ($this->getPropertyNames() as $propertyName) {
			$properties[$propertyName] = $this->getProperty($propertyName, $returnNodesAsIdentifiers);
		}
		return $properties;
	}

	/**
	 * Returns the names of all properties of this node.
	 *
	 * @return array Property names
	 * @api
	 */
	public function getPropertyNames() {
		return $this->nodeData->getPropertyNames();
	}

	/**
	 * Sets a content object for this node.
	 *
	 * @param object $contentObject The content object
	 * @return void
	 * @api
	 */
	public function setContentObject($contentObject) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($this->getContentObject() === $contentObject) {
			return;
		}
		$this->nodeData->setContentObject($contentObject);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns the content object of this node (if any).
	 *
	 * @return object
	 * @api
	 */
	public function getContentObject() {
		return $this->nodeData->getContentObject();
	}

	/**
	 * Unsets the content object of this node.
	 *
	 * @return void
	 * @api
	 */
	public function unsetContentObject() {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		$this->nodeData->unsetContentObject();

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Sets the node type of this node.
	 *
	 * @param NodeType $nodeType
	 * @return void
	 * @api
	 */
	public function setNodeType(NodeType $nodeType) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($this->getNodeType() === $nodeType) {
			return;
		}
		$this->nodeData->setNodeType($nodeType);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns the node type of this node.
	 *
	 * @return NodeType
	 * @api
	 */
	public function getNodeType() {
		return $this->nodeData->getNodeType();
	}

	/**
	 * Creates, adds and returns a child node of this node. Also sets default
	 * properties and creates default subnodes.
	 *
	 * @param string $name Name of the new node
	 * @param NodeType $nodeType Node type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return NodeInterface
	 * @api
	 */
	public function createNode($name, NodeType $nodeType = NULL, $identifier = NULL) {
		$this->emitBeforeNodeCreate($this, $name, $nodeType, $identifier);
		$newNode = $this->createSingleNode($name, $nodeType, $identifier);
		if ($nodeType !== NULL) {
			foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
				if (substr($propertyName, 0, 1) === '_') {
					ObjectAccess::setProperty($newNode, substr($propertyName, 1), $propertyValue);
				} else {
					$newNode->setProperty($propertyName, $propertyValue);
				}
			}

			foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeType) {
				$childNodeIdentifier = $this->buildAutoCreatedChildNodeIdentifier($childNodeName, $newNode->getIdentifier());
				$newNode->createNode($childNodeName, $childNodeType, $childNodeIdentifier);
			}
		}

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($newNode);
		$this->emitAfterNodeCreate($newNode);

		return $newNode;
	}

	/**
	 * Generate a stable identifier for auto-created child nodes
	 *
	 * This is needed if multiple node variants are created through "createNode" with different dimension values. If
	 * child nodes with the same path and different identifiers exist, bad things can happen.
	 *
	 * @param string $childNodeName
	 * @param string $identifier
	 * @return string The generated UUID like identifier
	 */
	protected function buildAutoCreatedChildNodeIdentifier($childNodeName, $identifier) {
		$hex = md5($identifier . '-' . $childNodeName);
		return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
	}

	/**
	 * Creates, adds and returns a child node of this node, without setting default
	 * properties or creating subnodes. Only used internally.
	 *
	 * For internal use only!
	 * TODO: Check if we can change the import service to avoid making this public.
	 *
	 * @param string $name Name of the new node
	 * @param NodeType $nodeType Node type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return Node
	 * @throws NodeConstraintException
	 */
	public function createSingleNode($name, NodeType $nodeType = NULL, $identifier = NULL) {
		if ($nodeType !== NULL && !$this->willChildNodeBeAutoCreated($name) && !$this->isNodeTypeAllowedAsChildNode($nodeType)) {
			throw new NodeConstraintException('Cannot create new node "' . $name . '" of Type "' . $nodeType->getName() . '" in ' . $this->__toString(), 1400782413);
		}

		$dimensions = $this->context->getTargetDimensionValues();

		$nodeData = $this->nodeData->createSingleNodeData($name, $nodeType, $identifier, $this->context->getWorkspace(), $dimensions);
		$node = $this->nodeFactory->createFromNodeData($nodeData, $this->context);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($node);

		return $node;
	}

	/**
	 * Checks if the given Node $name is configured as auto-created childNode in the NodeType configuration.
	 *
	 * @param string $name The node name to check.
	 * @return boolean TRUE if the given nodeName is configured as auto-created child node.
	 */
	public function willChildNodeBeAutoCreated($name) {
		$autoCreatedChildNodes = $this->getNodeType()->getAutoCreatedChildNodes();
		return isset($autoCreatedChildNodes[$name]);
	}

	/**
	 * Creates and persists a node from the given $nodeTemplate as child node
	 *
	 * @param NodeTemplate $nodeTemplate
	 * @param string $nodeName name of the new node. If not specified the name of the nodeTemplate will be used.
	 * @return NodeInterface the freshly generated node
	 * @api
	 */
	public function createNodeFromTemplate(NodeTemplate $nodeTemplate, $nodeName = NULL) {
		$nodeData = $this->nodeData->createNodeDataFromTemplate($nodeTemplate, $nodeName, $this->context->getWorkspace(), $this->context->getDimensions());
		$node = $this->nodeFactory->createFromNodeData($nodeData, $this->context);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($node);

		return $node;
	}

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return NodeInterface The specified node or NULL if no such node exists
	 * @api
	 */
	public function getNode($path) {
		$absolutePath = $this->nodeData->normalizePath($path);
		$node = $this->context->getFirstLevelNodeCache()->getByPath($absolutePath);
		if ($node !== FALSE) {
			return $node;
		}
		$node = $this->nodeDataRepository->findOneByPathInContext($absolutePath, $this->context);
		$this->context->getFirstLevelNodeCache()->setByPath($absolutePath, $node);
		return $node;
	}

	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * node type. For now it is just the first child node.
	 *
	 * @return Node The primary child node or NULL if no such node exists
	 * @api
	 */
	public function getPrimaryChildNode() {
		return $this->nodeDataRepository->findFirstByParentAndNodeTypeInContext($this->getPath(), NULL, $this->context);
	}

	/**
	 * Returns all direct child nodes of this node.
	 * If a node type is specified, only nodes of that type are returned.
	 *
	 * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
	 * @param integer $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
	 * @param integer $offset An optional offset for the query
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> An array of nodes or an empty array if no child nodes matched
	 * @api
	 */
	public function getChildNodes($nodeTypeFilter = NULL, $limit = NULL, $offset = NULL) {
		$nodes = $this->context->getFirstLevelNodeCache()->getChildNodesByPathAndNodeTypeFilter($this->getPath(), $nodeTypeFilter);
		if ($nodes === FALSE) {
			$nodes = $this->nodeDataRepository->findByParentAndNodeTypeInContext($this->getPath(), $nodeTypeFilter, $this->context, $limit, $offset);
			$this->context->getFirstLevelNodeCache()->setChildNodesByPathAndNodeTypeFilter($this->getPath(), $nodeTypeFilter, $nodes);
		}

		if ($offset !== NULL || $limit !== NULL) {
			$offset = ($offset === NULL) ? 0 : $offset;
			return array_slice($nodes, $offset, $limit);
		}

		return $nodes;
	}

	/**
	 * Returns the number of child nodes a similar getChildNodes() call would return.
	 *
	 * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
	 * @return integer The number of child nodes
	 * @api
	 */
	public function getNumberOfChildNodes($nodeTypeFilter = NULL) {
		return $this->nodeData->getNumberOfChildNodes($nodeTypeFilter, $this->context->getWorkspace(), $this->context->getDimensions());
	}

	/**
	 * Checks if this node has any child nodes.
	 *
	 * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
	 * @return boolean TRUE if this node has child nodes, otherwise FALSE
	 * @api
	 */
	public function hasChildNodes($nodeTypeFilter = NULL) {
		return ($this->getNumberOfChildNodes($nodeTypeFilter, $this->context->getWorkspace(), $this->context->getDimensions()) > 0);
	}

	/**
	 * Removes this node and all its child nodes.
	 *
	 * @return void
	 * @api
	 */
	public function remove() {
		/** @var $childNode Node */
		foreach ($this->getChildNodes() as $childNode) {
			$childNode->remove();
		}

		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		$this->nodeData->remove();

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeRemoved($this);
	}

	/**
	 * Enables using the remove method when only setters are available
	 *
	 * @param boolean $removed If TRUE, this node and it's child nodes will be removed. Cannot handle FALSE (yet).
	 * @return void
	 * @api
	 */
	public function setRemoved($removed) {
		if ((boolean)$removed === TRUE) {
			$this->remove();
		}
	}

	/**
	 * If this node is a removed node.
	 *
	 * @return boolean
	 * @api
	 */
	public function isRemoved() {
		return $this->nodeData->isRemoved();
	}

	/**
	 * Sets the "hidden" flag for this node.
	 *
	 * @param boolean $hidden If TRUE, this Node will be hidden
	 * @return void
	 * @api
	 */
	public function setHidden($hidden) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($this->isHidden() === $hidden) {
			return;
		}
		$this->nodeData->setHidden($hidden);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns the current state of the hidden flag
	 *
	 * @return boolean
	 * @api
	 */
	public function isHidden() {
		return $this->nodeData->isHidden();
	}

	/**
	 * Sets the date and time when this node becomes potentially visible.
	 *
	 * @param \DateTime $dateTime Date before this node should be hidden
	 * @return void
	 * @api
	 */
	public function setHiddenBeforeDateTime(\DateTime $dateTime = NULL) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($this->getHiddenBeforeDateTime() instanceof \DateTime && $this->getHiddenBeforeDateTime()->format(\DateTime::W3C) === $dateTime->format(\DateTime::W3C)) {
			return;
		}
		$this->nodeData->setHiddenBeforeDateTime($dateTime);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns the date and time before which this node will be automatically hidden.
	 *
	 * @return \DateTime Date before this node will be hidden
	 * @api
	 */
	public function getHiddenBeforeDateTime() {
		return $this->nodeData->getHiddenBeforeDateTime();
	}

	/**
	 * Sets the date and time when this node should be automatically hidden
	 *
	 * @param \DateTime $dateTime Date after which this node should be hidden
	 * @return void
	 * @api
	 */
	public function setHiddenAfterDateTime(\DateTime $dateTime = NULL) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($this->getHiddenAfterDateTime() instanceof \DateTime && $this->getHiddenAfterDateTime()->format(\DateTime::W3C) === $dateTime->format(\DateTime::W3C)) {
			return;
		}
		$this->nodeData->setHiddenAfterDateTime($dateTime);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns the date and time after which this node will be automatically hidden.
	 *
	 * @return \DateTime Date after which this node will be hidden
	 * @api
	 */
	public function getHiddenAfterDateTime() {
		return $this->nodeData->getHiddenAfterDateTime();
	}

	/**
	 * Sets if this node should be hidden in indexes, such as a site navigation.
	 *
	 * @param boolean $hidden TRUE if it should be hidden, otherwise FALSE
	 * @return void
	 * @api
	 */
	public function setHiddenInIndex($hidden) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($this->isHiddenInIndex() === $hidden) {
			return;
		}
		$this->nodeData->setHiddenInIndex($hidden);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * If this node should be hidden in indexes
	 *
	 * @return boolean
	 * @api
	 */
	public function isHiddenInIndex() {
		return $this->nodeData->isHiddenInIndex();
	}

	/**
	 * Sets the roles which are required to access this node
	 *
	 * @param array $accessRoles
	 * @return void
	 * @api
	 */
	public function setAccessRoles(array $accessRoles) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($this->getAccessRoles() === $accessRoles) {
			return;
		}
		$this->nodeData->setAccessRoles($accessRoles);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns the names of defined access roles
	 *
	 * @return array
	 * @api
	 */
	public function getAccessRoles() {
		return $this->nodeData->getAccessRoles();
	}

	/**
	 * Tells if a node, in general,  has access restrictions, independent of the
	 * current security context.
	 *
	 * @return boolean
	 * @api
	 */
	public function hasAccessRestrictions() {
		return $this->nodeData->hasAccessRestrictions();
	}

	/**
	 * Tells if this node is "visible".
	 *
	 * For this the "hidden" flag and the "hiddenBeforeDateTime" and "hiddenAfterDateTime" dates are
	 * taken into account.
	 *
	 * @return boolean
	 * @api
	 */
	public function isVisible() {
		if ($this->nodeData->isVisible() === FALSE) {
			return FALSE;
		}
		$currentDateTime = $this->context->getCurrentDateTime();
		if ($this->getHiddenBeforeDateTime() !== NULL && $this->getHiddenBeforeDateTime() > $currentDateTime) {
			return FALSE;
		}
		if ($this->getHiddenAfterDateTime() !== NULL && $this->getHiddenAfterDateTime() < $currentDateTime) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Tells if this node may be accessed according to the current security context.
	 *
	 * @return boolean
	 * @api
	 */
	public function isAccessible() {
		return $this->nodeData->isAccessible();
	}

	/**
	 * Returns the context this node operates in.
	 *
	 * @return Context
	 * @api
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Materializes the original node data (of a different workspace) into the current
	 * workspace.
	 *
	 * @return void
	 */
	protected function materializeNodeData() {
		$dimensions = $this->context->getTargetDimensionValues();

		$newNodeData = new NodeData($this->nodeData->getPath(), $this->context->getWorkspace(), $this->nodeData->getIdentifier(), $dimensions);
		$this->nodeDataRepository->add($newNodeData);

		$newNodeData->similarize($this->nodeData);

		$this->nodeData = $newNodeData;
		$this->nodeDataIsMatchingContext = TRUE;

		$nodeType = $this->getNodeType();
		foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeConfiguration) {
			$childNode = $this->getNode($childNodeName);
			if ($childNode instanceof Node && !$childNode->isNodeDataMatchingContext()) {
				$childNode->materializeNodeData();
			}
		}
	}

	/**
	 * Create a recursive copy of this node below $referenceNode with $nodeName.
	 *
	 * $detachedCopy only has an influence if we are copying from one dimension to the other, possibly creating a new
	 * node variant:
	 *
	 * - If $detachedCopy is TRUE, the whole (recursive) copy is done without connecting original and copied node,
	 *   so NOT CREATING a new node variant.
	 * - If $detachedCopy is FALSE, and the node does not yet have a variant in the target dimension, we are CREATING
	 *   a new node variant.
	 *
	 * As a caller of this method, $detachedCopy should be TRUE if $this->getNodeType()->isAggregate() is TRUE, and FALSE
	 * otherwise.
	 *
	 * @param NodeInterface $referenceNode
	 * @param boolean $detachedCopy
	 * @param string $nodeName
	 * @return NodeInterface
	 */
	protected function createRecursiveCopy(NodeInterface $referenceNode, $nodeName, $detachedCopy) {
		$identifier = NULL;

		$referenceNodeDimensions = $referenceNode->getDimensions();
		$referenceNodeDimensionsHash = NodeData::sortDimensionValueArrayAndReturnDimensionsHash($referenceNodeDimensions);
		$thisDimensions = $this->getDimensions();
		$thisNodeDimensionsHash = NodeData::sortDimensionValueArrayAndReturnDimensionsHash($thisDimensions);
		if ($detachedCopy === FALSE && $referenceNodeDimensionsHash !== $thisNodeDimensionsHash && $referenceNode->getContext()->getNodeByIdentifier($this->getIdentifier()) === NULL) {
			// If the target dimensions are different than this one, and there is no node shadowing this one in the target dimension yet, we use the same
			// node identifier, effectively creating a new node variant.
			$identifier = $this->getIdentifier();
		}

		$copiedNode = $referenceNode->createSingleNode($nodeName, NULL, $identifier);

		$copiedNode->similarize($this, TRUE);
		/** @var $childNode Node */
		foreach ($this->getChildNodes() as $childNode) {
			// Prevent recursive copy when copying into itself
			if ($childNode->getIdentifier() !== $copiedNode->getIdentifier()) {
				$childNode->copyIntoInternal($copiedNode, $childNode->getName(), $detachedCopy);
			}
		}

		return $copiedNode;
	}

	/**
	 * The NodeData matches the context if the workspace matches exactly.
	 * Needs to be adjusted for further context dimensions.
	 *
	 * @return boolean
	 */
	protected function isNodeDataMatchingContext() {
		if ($this->nodeDataIsMatchingContext === NULL) {
			$workspacesMatch = $this->nodeData->getWorkspace() !== NULL && $this->context->getWorkspace() !== NULL && $this->nodeData->getWorkspace()->getName() === $this->context->getWorkspace()->getName();
			$this->nodeDataIsMatchingContext = $workspacesMatch && $this->dimensionsAreMatchingTargetDimensionValues();
		}
		return $this->nodeDataIsMatchingContext;
	}

	/**
	 * For internal use in createRecursiveCopy.
	 *
	 * @param NodeInterface $sourceNode
	 * @param boolean $isCopy
	 * @return void
	 */
	public function similarize(NodeInterface $sourceNode, $isCopy = FALSE) {
		$this->nodeData->similarize($sourceNode->getNodeData(), $isCopy);
	}

	/**
	 * @return NodeData
	 */
	public function getNodeData() {
		return $this->nodeData;
	}

	/**
	 * Returns a string which distinctly identifies this object and thus can be used as an identifier for cache entries
	 * related to this object.
	 *
	 * @return string
	 */
	public function getCacheEntryIdentifier() {
		return $this->getContextPath();
	}

	/**
	 * Return the assigned content dimensions of the node.
	 *
	 * @return array
	 */
	public function getDimensions() {
		return $this->nodeData->getDimensionValues();
	}

	/**
	 * For debugging purposes, the node can be converted to a string.
	 *
	 * @return string
	 */
	public function __toString() {
		return 'Node ' . $this->getContextPath() . '[' . $this->getNodeType()->getName() . ']';
	}

	/**
	 * Given a context a new node is returned that is like this node, but
	 * lives in the new context.
	 *
	 * @param Context $context
	 * @return NodeInterface
	 */
	public function createVariantForContext($context) {
		$autoCreatedChildNodes = array();
		$nodeType = $this->getNodeType();
		foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeConfiguration) {
			$childNode = $this->getNode($childNodeName);
			if ($childNode !== NULL) {
				$autoCreatedChildNodes[$childNodeName] = $childNode;
			}
		}

		$nodeData = new NodeData($this->nodeData->getPath(), $context->getWorkspace(), $this->nodeData->getIdentifier(), $context->getTargetDimensionValues());
		$nodeData->similarize($this->nodeData);

		if ($this->context !== $context) {
			$node = $this->nodeFactory->createFromNodeData($nodeData, $context);
		} else {
			$this->setNodeData($nodeData);
			$node = $this;
		}

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($node);

		/**
		 * @var $autoCreatedChildNode NodeInterface
		 */
		foreach ($autoCreatedChildNodes as $autoCreatedChildNode) {
			$autoCreatedChildNode->createVariantForContext($context);
		}

		return $node;
	}

	/**
	 * Internal method
	 *
	 * The dimension value of this node has to match the current target dimension value (must be higher in priority or equal)
	 *
	 * @return boolean
	 */
	public function dimensionsAreMatchingTargetDimensionValues() {
		$dimensions = $this->getDimensions();
		$contextDimensions = $this->context->getDimensions();
		foreach ($this->context->getTargetDimensions() as $dimensionName => $targetDimensionValue) {
			if (!isset($dimensions[$dimensionName])) {
				return FALSE;
			} elseif (!in_array($targetDimensionValue, $dimensions[$dimensionName], TRUE)) {
				$contextDimensionValues = $contextDimensions[$dimensionName];
				$targetPositionInContext = array_search($targetDimensionValue, $contextDimensionValues, TRUE);
				$nodePositionInContext = min(array_map(function ($value) use ($contextDimensionValues) { return array_search($value, $contextDimensionValues, TRUE); }, $dimensions[$dimensionName]));

				$val = $targetPositionInContext !== FALSE && $nodePositionInContext !== FALSE && $targetPositionInContext >= $nodePositionInContext;
				if ($val === FALSE) {
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * Set the associated NodeData in regards to the Context.
	 *
	 * NOTE: This is internal only and should not be used outside of the TYPO3CR.
	 *
	 * @param NodeData $nodeData
	 * @return void
	 */
	public function setNodeData(NodeData $nodeData) {
		$this->nodeData = $nodeData;
		$this->nodeDataIsMatchingContext = NULL;
	}

	/**
	 * Checks if the given $nodeType would be allowed as a child node of this node according to the configured constraints.
	 *
	 * @param NodeType $nodeType
	 * @return boolean TRUE if the passed $nodeType is allowed as child node
	 */
	public function isNodeTypeAllowedAsChildNode(NodeType $nodeType) {
		if ($this->isAutoCreated()) {
			return $this->getParent()->getNodeType()->allowsGrandchildNodeType($this->getName(), $nodeType);
		} else {
			return $this->getNodeType()->allowsChildNodeType($nodeType);
		}
	}

	/**
	 * Determine if this node is configured as auto-created childNode of the parent node. If that is the case, it
	 * should not be deleted.
	 *
	 * @return boolean TRUE if this node is auto-created by the parent.
	 */
	public function isAutoCreated() {
		$parent = $this->getParent();
		if ($parent === NULL) {
			return FALSE;
		}

		if (array_key_exists($this->getName(), $parent->getNodeType()->getAutoCreatedChildNodes())) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Determine if this node is a shadow node of a moved node.
	 *
	 * @return boolean TRUE if this node is a shadow node of a moved node.
	 */
	public function isShadowNode() {
		return $this->nodeData->getMovedTo() !== NULL;
	}

	/**
	 * Set the status of the associated NodeData in regards to the Context.
	 *
	 * NOTE: This is internal only and should not be used outside of the TYPO3CR.
	 *
	 * @param boolean $status
	 * @return void
	 */
	public function setNodeDataIsMatchingContext($status) {
		$this->nodeDataIsMatchingContext = $status;
	}

	/**
	 * Create a node for the given NodeData, given that it is a variant of the current node
	 *
	 * @param NodeData $nodeData
	 * @return Node
	 */
	protected function createNodeForVariant($nodeData) {
		$contextProperties = $this->context->getProperties();
		$contextProperties['dimensions'] = $nodeData->getDimensionValues();
		unset($contextProperties['targetDimensions']);
		$adjustedContext = $this->contextFactory->create($contextProperties);
		return $this->nodeFactory->createFromNodeData($nodeData, $adjustedContext);
	}

	/**
	 * Signals that a node will be created.
	 *
	 * @Flow\Signal
	 * @param NodeInterface $node
	 * @param string $name
	 * @param string $nodeType
	 * @param string $identifier
	 * @return void
	 */
	protected function emitBeforeNodeCreate(NodeInterface $node, $name, $nodeType, $identifier) {
	}

	/**
	 * Signals that a node was created.
	 *
	 * @Flow\Signal
	 * @param NodeInterface $node
	 * @return void
	 */
	protected function emitAfterNodeCreate(NodeInterface $node) {
	}

	/**
	 * Signals that a node was added.
	 *
	 * @Flow\Signal
	 * @param NodeInterface $node
	 * @return void
	 */
	protected function emitNodeAdded(NodeInterface $node) {
	}

	/**
	 * Signals that a node was updated.
	 *
	 * @Flow\Signal
	 * @param NodeInterface $node
	 * @return void
	 */
	protected function emitNodeUpdated(NodeInterface $node) {
	}

	/**
	 * Signals that a node was removed.
	 *
	 * @Flow\Signal
	 * @param NodeInterface $node
	 * @return void
	 */
	protected function emitNodeRemoved(NodeInterface $node) {
	}

	/**
	 * Signals that the property of a node will be changed.
	 *
	 * @Flow\Signal
	 * @param NodeInterface $node
	 * @param string $propertyName name of the property that has been changed/added
	 * @param mixed $oldValue the property value before it was changed or NULL if the property is new
	 * @param mixed $newValue the new property value
	 * @return void
	 */
	protected function emitBeforeNodePropertyChange(NodeInterface $node, $propertyName, $oldValue, $newValue) {
	}

	/**
	 * Signals that the property of a node was changed.
	 *
	 * @Flow\Signal
	 * @param NodeInterface $node
	 * @param string $propertyName name of the property that has been changed/added
	 * @param mixed $oldValue the property value before it was changed or NULL if the property is new
	 * @param mixed $newValue the new property value
	 * @return void
	 */
	protected function emitNodePropertyChanged(NodeInterface $node, $propertyName, $oldValue, $newValue) {
	}
}

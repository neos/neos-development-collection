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
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;
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
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeData
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $nodeData
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
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if you try to set the name of the root node.
	 * @throws \InvalidArgumentException if $newName is invalid
	 * @api
	 */
	public function setName($newName) {
		if (!is_string($newName) || preg_match(NodeInterface::MATCH_PATTERN_NAME, $newName) !== 1) {
			throw new \InvalidArgumentException('Invalid node name "' . $newName . '" (a node name must only contain characters, numbers and the "-" sign).', 1364290748);
		}

		if ($this->getPath() === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be renamed.', 1346778388);
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
	 * This method is only for internal use by the content repository. Changing
	 * the path of a node manually may lead to unexpected behavior.
	 *
	 * @param string $path
	 * @param boolean $recursive
	 * @return void
	 */
	public function setPath($path, $recursive = TRUE) {
		if ($this->nodeData->getPath() === $path) {
			return;
		}
		if ($recursive === TRUE) {
			/** @var $childNode NodeInterface */
			foreach ($this->getChildNodes() as $childNode) {
				$childNode->setPath($path . '/' . $childNode->getNodeData()->getName(), TRUE);
			}
		}
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		$this->nodeData->setPath($path, FALSE);
		$this->context->getFirstLevelNodeCache()->flush();
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
	 * Returns an up to LABEL_MAXIMUM_LENGTH characters long plain text description of this node
	 *
	 * @return string
	 */
	public function getLabel() {
		return $this->nodeData->getLabel();
	}

	/**
	 * Returns a full length plain text description of this node
	 *
	 * @return string
	 */
	public function getFullLabel() {
		return $this->nodeData->getFullLabel();
	}

	/**
	 * Sets the workspace of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the workspace of a node manually may lead to unexpected behavior.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function setWorkspace(Workspace $workspace) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
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
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The parent node or NULL if this is the root node
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if you try to move the root node
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 * @api
	 */
	public function moveBefore(NodeInterface $referenceNode) {
		if ($referenceNode === $this) {
			return;
		}

		if ($this->getPath() === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1285005924);
		}

		if ($referenceNode->getParent() !== $this->getParent() && $referenceNode->getParent()->getNode($this->getName()) !== NULL) {
			throw new \TYPO3\TYPO3CR\Exception\NodeExistsException('Node with path "' . $this->getName() . '" already exists.', 1292503468);
		}

		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($referenceNode->getParentPath() !== $this->getParentPath()) {
			$parentPath = $referenceNode->getParentPath();
			$this->setPath($parentPath . ($parentPath === '/' ? '' : '/') . $this->getName());
			$this->nodeDataRepository->persistEntities();
		}

		$this->nodeDataRepository->setNewIndex($this->nodeData, NodeDataRepository::POSITION_BEFORE, $referenceNode);
		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Moves this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 * @return void
	 * @api
	 */
	public function moveAfter(NodeInterface $referenceNode) {
		if ($referenceNode === $this) {
			return;
		}

		if ($this->getPath() === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1316361483);
		}

		if ($referenceNode->getParent() !== $this->getParent() && $referenceNode->getParent()->getNode($this->getName()) !== NULL) {
			throw new \TYPO3\TYPO3CR\Exception\NodeExistsException('Node with path "' . $this->getName() . '" already exists.', 1292503469);
		}

		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		if ($referenceNode->getParentPath() !== $this->getParentPath()) {
			$parentPath = $referenceNode->getParentPath();
			$this->setPath($parentPath . ($parentPath === '/' ? '' : '/') . $this->getName());
			$this->nodeDataRepository->persistEntities();
		}

		$this->nodeDataRepository->setNewIndex($this->nodeData, NodeDataRepository::POSITION_AFTER, $referenceNode);
		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Moves this node into the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 * @return void
	 * @api
	 */
	public function moveInto(NodeInterface $referenceNode) {
		if ($referenceNode === $this || $referenceNode === $this->getParent()) {
			return;
		}

		if ($this->getPath() === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1346769001);
		}

		if ($referenceNode !== $this->getParent() && $referenceNode->getNode($this->getName()) !== NULL) {
			throw new \TYPO3\TYPO3CR\Exception\NodeExistsException('Node with path "' . $this->getName() . '" already exists.', 1292503470);
		}

		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		$parentPath = $referenceNode->getPath();
		$this->setPath($parentPath . ($parentPath === '/' ? '' : '/') . $this->getName());
		$this->nodeDataRepository->persistEntities();

		$this->nodeDataRepository->setNewIndex($this->nodeData, NodeDataRepository::POSITION_LAST);
		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Copies this node before the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 * @throws NodeExistsException
	 * @api
	 */
	public function copyBefore(NodeInterface $referenceNode, $nodeName) {
		if ($referenceNode->getParent()->getNode($nodeName) !== NULL) {
			throw new NodeExistsException('Node with path "' . $referenceNode->getParent()->getPath() . '/' . $nodeName . '" already exists.', 1292503465);
		}
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}

		$copiedNode = $this->createRecursiveCopy($referenceNode, $nodeName);
		$copiedNode->moveBefore($referenceNode);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($copiedNode);

		return $copiedNode;
	}

	/**
	 * Copies this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 * @throws NodeExistsException
	 * @api
	 */
	public function copyAfter(NodeInterface $referenceNode, $nodeName) {
		if ($referenceNode->getParent()->getNode($nodeName) !== NULL) {
			throw new NodeExistsException('Node with path "' . $referenceNode->getParent()->getPath() . '/' . $nodeName . '" already exists.', 1292503466);
		}
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}

		$copiedNode = $this->createRecursiveCopy($referenceNode, $nodeName);
		$copiedNode->moveAfter($referenceNode);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($copiedNode);

		return $copiedNode;
	}

	/**
	 * Copies this node into the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 * @throws NodeExistsException
	 * @api
	 */
	public function copyInto(NodeInterface $referenceNode, $nodeName) {
		if ($referenceNode->getNode($nodeName) !== NULL) {
			throw new NodeExistsException('Node with path "' . $referenceNode->getPath() . '/' . $nodeName . '" already exists.', 1292503467);
		}
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}

		$copiedNode = $this->createRecursiveCopy($referenceNode, $nodeName);

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
		$this->nodeData->setProperty($propertyName, $value);

		$this->context->getFirstLevelNodeCache()->flush();
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
		if (!empty($value) && $returnNodesAsIdentifiers === FALSE) {
			switch($this->getNodeType()->getPropertyType($propertyName)) {
				case 'references' :
					$nodes = array();
					foreach ($value as $nodeData) {
						$node = $this->nodeFactory->createFromNodeData($nodeData, $this->context);
						// $node can be NULL if the node is not visible according to the current content context:
						if ($node !== NULL) {
							$nodes[] = $node;
						}
					}
					$value = $nodes;
				break;
				case 'reference' :
					$value = $this->nodeFactory->createFromNodeData($value, $this->context);
				break;
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
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if the node does not contain the specified property
	 */
	public function removeProperty($propertyName) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType
	 * @return void
	 * @api
	 */
	public function setNodeType(NodeType $nodeType) {
		if (!$this->isNodeDataMatchingContext()) {
			$this->materializeNodeData();
		}
		$this->nodeData->setNodeType($nodeType);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeUpdated($this);
	}

	/**
	 * Returns the node type of this node.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeType
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType Node type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @param array $dimensions Content dimension values to set on the node (Array of dimension names to array of values)
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 * @api
	 */
	public function createNode($name, NodeType $nodeType = NULL, $identifier = NULL, array $dimensions = NULL) {
		$newNode = $this->createSingleNode($name, $nodeType, $identifier, $dimensions);
		if ($nodeType !== NULL) {
			foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
				$newNode->setProperty($propertyName, $propertyValue);
			}

			foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeType) {
				$newNode->createNode($childNodeName, $childNodeType, NULL, $dimensions);
			}
		}

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($newNode);

		return $newNode;
	}

	/**
	 * Creates, adds and returns a child node of this node, without setting default
	 * properties or creating subnodes. Only used internally.
	 *
	 * For internal use only!
	 * TODO: Check if we can change the import service to avoid making this public.
	 *
	 * @param string $name Name of the new node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType Node type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @param array $dimensions Content dimension values to set on the node (Array of dimension names to array of values)
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 */
	public function createSingleNode($name, NodeType $nodeType = NULL, $identifier = NULL, array $dimensions = NULL) {
		if ($dimensions === NULL || $dimensions === array()) {
			$targetDimensions = $this->context->getTargetDimensions();
			$dimensions = array_map(function ($value) { return array ($value); }, $targetDimensions);
		}

		$nodeData = $this->nodeData->createSingleNodeData($name, $nodeType, $identifier, $this->context->getWorkspace(), $dimensions);
		$node = $this->nodeFactory->createFromNodeData($nodeData, $this->context);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($node);

		return $node;
	}

	/**
	 * Creates and persists a node from the given $nodeTemplate as child node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeTemplate $nodeTemplate
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
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
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
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node The primary child node or NULL if no such node exists
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
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
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
		$dimensions = array_map(function($value) { return array($value); }, $this->context->getTargetDimensions());
		$newNodeData = new NodeData($this->nodeData->getPath(), $this->context->getWorkspace(), $this->nodeData->getIdentifier(), $dimensions);
		$this->nodeDataRepository->add($newNodeData);

		$newNodeData->similarize($this->nodeData);

		$this->nodeData = $newNodeData;
		$this->nodeDataIsMatchingContext = TRUE;
	}

	/**
	 * Create a recursive copy of this node below $referenceNode with $nodeName.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected function createRecursiveCopy(NodeInterface $referenceNode, $nodeName) {
		$copiedNode = $referenceNode->createSingleNode($nodeName);
		$copiedNode->similarize($this);
		/** @var $childNode Node */
		foreach ($this->getChildNodes() as $childNode) {
			// Prevent recursive copy when copying into itself
			if ($childNode !== $copiedNode) {
				$childNode->copyInto($copiedNode, $childNode->getName());
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
	 * @return void
	 */
	public function similarize(NodeInterface $sourceNode) {
		$this->nodeData->similarize($sourceNode->getNodeData());
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
	 * @param \TYPO3\TYPO3CR\Domain\Service\Context $context
	 * @return NodeInterface
	 */
	public function createVariantForContext($context) {
		$nodeData = clone $this->nodeData;
		$nodeData->adjustToContext($context);

		$this->nodeDataRepository->add($nodeData);
		$node = $this->nodeFactory->createFromNodeData($nodeData, $context);

		$this->context->getFirstLevelNodeCache()->flush();
		$this->emitNodeAdded($node);

		return $node;
	}

	/**
	 * The dimension value of this node has to match the current target dimension value (must be higher in priority or equal)
	 *
	 * @return boolean
	 */
	protected function dimensionsAreMatchingTargetDimensionValues() {
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

}

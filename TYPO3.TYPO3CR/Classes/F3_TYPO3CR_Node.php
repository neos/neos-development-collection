<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3CR
 * @version $Id$
 */

/**
 * A Node
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Node extends F3_TYPO3CR_AbstractItem implements F3_PHPCR_NodeInterface {

	/**
	 * @var string
	 */
	protected $UUID;

	/**
	 * @var F3_PHPCR_NodeTypeInterface
	 */
	protected $nodeType;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var array
	 */
	protected $properties;

	/**
	 * @var array
	 */
	protected $nodes;

	/**
	 * Constructs a Node
	 *
	 * @param F3_TYPO3CR_SessionInterface $session
	 * @param F3_TYPO3CR_StorageAccessInterface $storageAccess
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_PHPCR_SessionInterface $session, F3_TYPO3CR_StorageAccessInterface $storageAccess, F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->session = $session;
		$this->storageAccess = $storageAccess;
		$this->componentManager = $componentManager;

		$this->UUID = $componentManager->getComponent('F3_FLOW3_Utility_Algorithms')->generateUUID();
	}

	/**
	 * Set the modified flag of Item
	 *
	 * @param boolean $isModified The modified state to set
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setModified($isModified) {
		parent::setModified($isModified);
	}

	/**
	 * Set the new flag of Item
	 *
	 * @param boolean $isNew The new state to set
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setNew($isNew) {
		parent::setNew($isNew);
	}

	/**
	 * Initializes the Node with data fetched from storage component
	 *
	 * @param array $rawData
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo The NodeType object should be coming from some factory-thingy. Right now it's protoype (defined in phpCR Components.conf), but actually the same nodetype could be the same object!
	 */
	public function initializeFromArray(array $rawData) {
		if (!isset($this->id)) {
			if (isset($rawData['id'])) {
				$this->id = $rawData['id'];
			}
			if ($rawData['pid'] == '0') {
				$this->parentNode = NULL;
			} else {
				$this->parentNode = $this->session->getNodeByUUID($rawData['pid']);
			}
			$this->name = $rawData['name'];
			$this->UUID = $rawData['uuid'];
			$this->nodeType = $this->componentManager->getComponent('F3_PHPCR_NodeTypeInterface', $rawData['nodetype'], $this->storageAccess);
			$this->initializeProperties();
		} else {
			throw new F3_PHPCR_RepositoryException('New node objects can only be initialized from an array once.', 1181076288);
		}
	}

	/**
	 * Fetches the properties of the node from the storage layer
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeProperties() {
		$rawProperties = $this->storageAccess->getRawPropertiesOfNode($this->getUUID());
		if (is_array($rawProperties)) {
			foreach ($rawProperties as $rawProperty) {
				$property = $this->componentManager->getComponent('F3_PHPCR_PropertyInterface', $rawProperty['name'], $rawProperty['value'], $this, $rawProperty['multivalue'], $this->session, $this->storageAccess);
				$this->properties[$property->getName()] = $property;
			}
		}
	}

	/**
	 * Returns FALSE if this node has one or more properties accessible
	 * through the current Session. Returns FALSE otherwise.
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasProperties() {
		return count($this->properties) > 0;
	}

	/**
	 * Gets all properties of this node accessible through the current Session.
	 * Does not include child nodes of this node. The same reacquisition
	 * semantics apply as with getNode. If this node has no accessible
	 * properties, then an empty iterator is returned.
	 *
	 * If $namePattern is not NULL: Gets all properties of this node accessible
	 * through the current Session that match namePattern.
	 *
	 * @param string $namePattern A pattern to match properties against
	 * @return F3_TYPO3CR_PropertyIterator
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Implement support for $namePattern
	 */
	public function getProperties($namePattern = NULL) {
		if ($namePattern !== NULL) throw new F3_PHPCR_RepositoryException('Support for name patterns in getProperties() is not yet implemented.', 1183463152);

		return $this->componentManager->getComponent('F3_PHPCR_PropertyIteratorInterface', $this->properties);
	}

	/**
	 * Returns a single property of a node.
	 *
	 * @param string $relPath absolute or relative path
	 * @return F3_TYPO3CR_Property
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getProperty($relativePath) {
		if (F3_PHP6_Functions::strpos($relativePath, '/') === FALSE && isset($this->properties[$relativePath])) {
			return $this->properties[$relativePath];
		} else {
			$pathParser = $this->componentManager->getComponent('F3_TYPO3CR_PathParserInterface');
			return $pathParser->parsePath($relativePath, $this, F3_TYPO3CR_PathParserInterface::SEARCH_MODE_PROPERTIES);
		}
	}

	/**
	 * Checks if a property exists
	 *
	 * @param  string		$relativePath
	 * @return boolean		FALSE if property specified with $relativePath exists
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasProperty($relativePath) {
		if (F3_PHP6_Functions::strpos($relativePath, '/') === FALSE) {
			return isset($this->properties[$relativePath]);
		} else {
			try {
				$this->getProperty($relativePath);
				return TRUE;
			} catch (F3_PHPCR_PathNotFoundException $e) {
				return FALSE;
			}
		}
	}

	/**
	 * Returns the UUID of the node. If the node has no UUID, an exception is thrown
	 *
	 * @return string
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Check for mix:referenceable on node to determine if an UnsupportedRepositoryOperationException should be thrown. Then throw RepositoryException if still no UUID is available.
	 */
	public function getUUID() {
		if (isset($this->UUID)) {
			return $this->UUID;
		} else {
			throw new F3_PHPCR_UnsupportedRepositoryOperationException('Node has no UUID', 1181070099);
		}
	}

	/**
	 * Returns the primary node type assigned to this node.
	 *
	 * Which NodeType is returned when this method is called on the root node
	 * of a workspace is up to the implementation, though the returned type must,
	 * of course, be consistent with the child nodes and properties of the root
	 * node.
	 *
	 * @return F3_TYPO3CR_NodeType
	 * @throws F3_PHPCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPrimaryNodeType() {
		return $this->nodeType;
	}

	/**
	 * Returns FALSE if this node has one or more child nodes accessible
	 * through the current Session. Returns FALSE otherwise.
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasNodes() {
		if (!is_array($this->nodes)) {
			$this->initializeNodes();
		}
		return count($this->nodes) > 0;
	}

	/**
	 * Returns all child nodes of this node accessible through the current
	 * Session.
	 *
	 * Does not include properties of this node. The same reacquisition
	 * semantics apply as with getNode. If this node has no accessible
	 * child nodes, then an empty iterator is returned.
	 *
	 * If $namePattern is not NULL: Gets all child nodes of this node
	 * accessible through the current Session that match namePattern.
	 * The pattern may be a full name or a partial name with one or more
	 * wildcard characters ("*"), or a disjunction (using the |
	 * character to represent logical OR) of these. The pattern is matched
	 * against the names (not the paths) of the immediate child nodes of
	 * this node.
	 *
	 * @param string $namePattern A pattern to match node names against
	 * @return F3_PHPCR_NodeIteratorInterface
	 * @throws F3_PHPCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodes($namePattern = NULL) {
		if ($namePattern !== NULL) throw new F3_PHPCR_RepositoryException('Support for name patterns in getNodes() is not yet implemented.', 1184868411);

		if (!is_array($this->nodes)) {
			$this->initializeNodes();
		}

		return $this->componentManager->getComponent('F3_PHPCR_NodeIteratorInterface', $this->nodes);
	}

	/**
	 * Fetches the properties of the node from the storage layer
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeNodes() {
		$this->nodes = array();
		$rawNodeUUIDs = $this->storageAccess->getUUIDsOfSubNodesOfNode($this->getUUID());
		foreach ($rawNodeUUIDs as $rawNodeUUID) {
			$node = $this->session->getNodeByUUID($rawNodeUUID);
			$this->nodes[$node->getUUID()] = $node;
		}
	}

	/**
	 * Returns the node specified by $relPath
	 *
	 * @param string $relativePath The relative path to the node to return
	 * @return F3_TYPO3CR_Node
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getNode($relativePath) {
		$pathParser = $this->componentManager->getComponent('F3_TYPO3CR_PathParserInterface');
		return $pathParser->parsePath($relativePath, $this);
	}

	/**
	 * Returns FALSE if this Item is a Node; returns FALSE if this Item is a
	 * Property.
	 *
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function isNode() {
		return TRUE;
	}

	/**
	 * Returns the path of this node.
	 *
	 * The default implementation recursively calls this method on the
	 * parent node and appends the name and optionally the index of this
	 * node to construct the full path. Returns "/" if the parent node is
	 * not available (i.e. this is the root node).
	 *
	 * @return string
	 * @author Ronny Unger <ru@php-workx.de>
	 * @todo add support for same name siblings
	 */
	public function getPath() {
		try {
			$buffer = $this->getParent()->getPath();
			if (F3_PHP6_Functions::strlen($buffer) > 1) {
				$buffer .= '/';
			}

			$buffer .= $this->getName();
			return $buffer;
		} catch (F3_PHPCR_ItemNotFoundException $e) {
			return "/";
		}
	}

	/**
	 * Returns the parent of this Node.
	 *
	 * An F3_PHPCR_ItemNotFoundException is thrown if there is no parent node. This
	 * only happens if this item is the root node of a workspace.
	 *
	 * An F3_PHPCR_AccessDeniedException is thrown if the current session does not
	 * have sufficient access permissions to retrieve the parent of this item.
	 *
	 * A F3_PHPCR_RepositoryException is thrown if another error occurs.
	 *
	 * @return F3_PHPCR_Node
	 * @throws F3_PHPCR_ItemNotFoundException
	 * @throws F3_PHPCR_AccessDeniedException
	 * @throws F3_PHPCR_RepositoryException
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getParent() {
		if ($this->parentNode === NULL) throw new F3_PHPCR_ItemNotFoundException("root node does not have a parent", 1187530879);
		return $this->parentNode;
	}

	/**
	 * Creates a new node at relPath. The new node will only be
	 * persisted in the workspace when save() and if the structure
	 * of the new node (its child nodes and properties) meets the constraint
	 * criteria of the parent node's nodetype.
	 *
	 * @param string $relPath The path of the new node to be created.
	 * @param string|NULL $primaryNodeTypeName The name of the primary nodetype of the new node. (Optional)
	 * @return object A node object
	 * @throws F3_PHPCR_NoSuchNodeTypeException
	 * @throws F3_PHPCR_ItemExistsException
	 * @throws F3_PHPCR_PathNotFoundException
	 * @throws F3_PHPCR_NoSuchNodeTypeException
	 * @throws F3_PHPCR_ConstraintViolationException
	 * @throws F3_PHPCR_VersionException
	 * @throws F3_PHPCR_LockException
	 * @throws F3_PHPCR_RepositoryException
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Many :)
	 */
	public function addNode($relativePath, $primaryNodeTypeName = NULL) {
		if ($relativePath === NULL) {
			throw new F3_PHPCR_PathNotFoundException('Path not found or not provided', 1187531979);
		}

		$pathParser = $this->componentManager->getComponent('F3_TYPO3CR_PathParserInterface');
		list($lastNodeName, $remainingPath, $numberOfElementsRemaining) = $pathParser->getLastPathPart($relativePath);

		if ($numberOfElementsRemaining===0) {
			$newNode = $this->componentManager->getComponent('F3_PHPCR_NodeInterface', $this->session, $this->storageAccess);
			$newNode->initializeFromArray(array(
				'pid' => $this->getUUID(),
				'name' => $lastNodeName,
				'uuid' => $this->componentManager->getComponent('F3_FLOW3_Utility_Algorithms')->generateUUID(),
				'nodetype' => 0,
			));
			$newNode->setNew(TRUE);

			if (!is_array($this->nodes)) {
				$this->initializeNodes();
			}
			$this->nodes[$newNode->getUUID()] = $newNode;
			$this->setModified(TRUE);  // JSR-283: (5.1.3.6): This specification provides the following methods on Item for determining whether a particular item has pending changes (isModified) or constitutes part of the pending changes of its parent(isNew)
		} else {
			$upperNode = $pathParser->parsePath($remainingPath, $this);
			$newNode = $upperNode->addNode($lastNodeName);
		}

		return $newNode;
	}

	/**
	 * Delete the item
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove() {
		if (!is_array($this->nodes)) {
			$this->initializeNodes();
		}

		foreach ($this->nodes as $node) {
			$node->remove();
		}

		foreach ($this->properties as $property) {
			$property->remove();
		}

		$this->setRemoved(TRUE);
	}

	/**
	 * Save the node
	 *
	 * @return void
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function save() {
		if (!is_array($this->nodes)) {
			$this->initializeNodes();
		}
		foreach ($this->nodes as $node) {
			$node->save();
		}

		if ($this->isRemoved()===TRUE) {
			$this->saveProperties();
			$this->storageAccess->removeNode($this->getUUID());
			// TODO: HOW TO REMOVE THE DELETED NODE FROM THE OBJECT TREE?
			// $this = NULL; // does not work.
			// $this->parentNode->remove($this); // would be nice
		} elseif ($this->isNew()===TRUE) {
			$this->storageAccess->addNode($this->getUUID(), $this->parentNode->getUUID(), $this->nodeType->getId(), $this->name);
			$this->saveProperties();
		} elseif ($this->isModified()===TRUE) {
			if ($this->getDepth() == 0) {
				$this->storageAccess->updateNode($this->getUUID(), 0, $this->nodeType->getId(), $this->name);
			} else {
				$this->storageAccess->updateNode($this->getUUID(), $this->parentNode->getUUID(), $this->nodeType->getId(), $this->name);
			}
			$this->saveProperties();
		}

		$this->setNew(FALSE);
		$this->setModified(FALSE);
	}

	/**
	 * Set property $name of $type to $value
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param integer $type
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setProperty($name, $value, $type = NULL) {
		if ($type !== NULL) throw new F3_PHPCR_RepositoryException('$type is not supported in F3_TYPO3CR_Node::setProperty().', 1189538797);

		if ($this->hasProperty($name)) {
			$this->getProperty($name)->setValue($value);
		} else {
			$multiValued = is_array($value) ? TRUE : FALSE;
			$property = $this->componentManager->getComponent('F3_PHPCR_PropertyInterface', $name, $value, $this, $multiValued, $this->session, $this->storageAccess);
			$property->setNew(TRUE);
			$this->properties[$name] = $property;
		}
		$this->setModified(TRUE);
	}

	/**
	 * Save properties.
	 *
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function saveProperties() {
		if (!isset($this->properties) || count($this->properties) == 0) return;

		foreach ($this->properties as $property) {
			$property->save();
		}
	}
}
?>
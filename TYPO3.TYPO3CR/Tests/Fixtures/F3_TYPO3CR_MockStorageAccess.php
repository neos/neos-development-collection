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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * A mock Storage Access
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_MockStorageAccess implements F3_TYPO3CR_Storage_BackendInterface {

	/**
	 * @var array This array can be set from tests to mock raw node arrays
	 */
	public $rawNodesByIdentifierGroupedByWorkspace = array();

	/**
	 * @var array This array can be set from tests to mock raw node arrays
	 */
	public $rawNodesByIDGroupedByWorkspace = array();

	/**
	 * @var array Raw root nodes data for different workspaces
	 */
	public $rawRootNodesByWorkspace = array();

	/**
	 * @var array Raw properties of nodes
	 */
	public $rawPropertiesByIdentifierGroupedByWorkspace = array();

	/**
	 * @var F3_TYPO3CR_Workspace
	 */
	protected $workspaceName = 'default';

	/**
	 * @var F3_TYPO3CR_NamespaceRegistryInterface
	 */
	protected $namespaceRegistry;

	/**
	 * To satisfy the interface
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function connect() {}

	/**
	 * To satisfy the interface
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function disconnect() {}

	/**
	 * Sets the name of the current workspace
	 *
	 * @param  string $workspaceName Name of the workspace which should be used for all storage operations
	 * @return void
	 * @throws InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setWorkspaceName($workspaceName) {
		if ($workspaceName == '' || !is_string($workspaceName)) throw new InvalidArgumentException('"' . $workspaceName . '" is not a valid workspace name.', 1200614986);
		$this->workspaceName = $workspaceName;
	}

	/**
	 * Fetches raw node data from the database
	 *
	 * @param  string $identifier The Identifier of the node to fetch
	 * @return array|FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawNodeByIdentifier($identifier) {
		if (key_exists($this->workspaceName, $this->rawNodesByIdentifierGroupedByWorkspace)) {
			if (key_exists($identifier, $this->rawNodesByIdentifierGroupedByWorkspace[$this->workspaceName])) {
				return $this->rawNodesByIdentifierGroupedByWorkspace[$this->workspaceName][$identifier];
			}
		}
		return FALSE;
	}

	/**
	 * Fetches raw node data of the root node of the current workspace.
	 *
	 * @return array|FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawRootNode() {
		if (key_exists($this->workspaceName, $this->rawRootNodesByWorkspace)) {
			return $this->rawRootNodesByWorkspace[$this->workspaceName];
		}
		return FALSE;
	}

	/**
	 * Fetches raw namespace data from the database
	 *
	 * @return array
	 */
	public function getRawNamespaces() {

	}

	/**
	 * Adds a namespace identified by prefix and URI
	 *
	 * @param string $prefix The namespace prefix to register
	 * @param string $uri The namespace URI to register
	 */
	public function addNamespace($prefix, $uri) {

	}

	/**
	 * Updates the prefix for the namespace identified by $uri
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 */
	public function updateNamespacePrefix($prefix, $uri) {

	}

	/**
	 * Updates the URI for the namespace identified by $prefix
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 */
	public function updateNamespaceURI($prefix, $uri) {

	}

	/**
	 * Deletes the namespace identified by $prefix.
	 *
	 * @param string $prefix The prefix of the namespace to delete
	 */
	public function deleteNamespace($prefix) {

	}

	/**
	 * Fetches sub node Identifiers from the database
	 *
	 * @param integer $nodeId The node uid to fetch (sub-)nodes for
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifiersOfSubNodesOfNode($nodeId) {
		$identifiers = array();
		if (key_exists($this->workspaceName, $this->rawNodesByIdentifierGroupedByWorkspace)) {
			if (key_exists($nodeId, $this->rawNodesByIdentifierGroupedByWorkspace[$this->workspaceName])) {
				foreach ($this->rawNodesByIdentifierGroupedByWorkspace[$this->workspaceName] as $identifier => $rawNode) {
					if ($rawNode['parent'] == $nodeId) {
						$identifiers[] = $identifier;
					}
				}
			}
		}
		return $identifiers;
	}

	/**
	 * Returns raw property data for the specified node
	 *
	 * @param string $identifier The node Identifier to fetch properties for
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawPropertiesOfNode($identifier) {
		if (key_exists($this->workspaceName, $this->rawPropertiesByIdentifierGroupedByWorkspace)) {
			if (key_exists($identifier, $this->rawPropertiesByIdentifierGroupedByWorkspace[$this->workspaceName])) {
				return $this->rawPropertiesByIdentifierGroupedByWorkspace[$this->workspaceName][$identifier];
			}
		}
		return array();
	}

	/**
	 * Fetches raw data for all nodetypes from the database
	 *
	 * @return array
	 */
	public function getRawNodeTypes() {

	}

	/**
	 * Fetches raw nodetype data from the database
	 *
	 * @param string $nodeTypeName The name of the nodetype record to fetch
	 * @return array
	 */
	public function getRawNodeType($nodeTypeName) {

	}

	/**
	 * Adds the given nodetype to the database
	 *
	 * @param F3_PHPCR_NodeType_NodeTypeDefinitionInterface $nodeTypeDefinition
	 * @return void
	 */
	public function addNodeType(F3_PHPCR_NodeType_NodeTypeDefinitionInterface $nodeTypeDefinition) {

	}

	/**
	 * Deletes the named nodetype from the database
	 *
	 * @param string $name
	 * @return void
	 */
	public function deleteNodeType($name) {

	}

	/**
	 * Adds a node to the storage
	 *
	 * @param F3_PHPCR_NodeInterface $node node to insert
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addNode(F3_PHPCR_NodeInterface $node) {
		$this->rawNodesByIdentifierGroupedByWorkspace[$this->workspaceName][$node->getIdentifier()] = array(
			'identifier' => $node->getIdentifier(),
			'parent' => $node->getParent()->getIdentifier(),
			'nodetype' => $node->getPrimaryNodeType()->getName(),
			'name' => $node->getName()
		);
	}

	/**
	 * Updates a node in the storage
	 *
	 * @param F3_PHPCR_NodeInterface $node node to update
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNode(F3_PHPCR_NodeInterface $node) {
		$this->addNode($node);
	}

	/**
	 * Deletes a node in the repository
	 *
	 * @param F3_PHPCR_NodeInterface $node node to delete
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeNode(F3_PHPCR_NodeInterface $node) {
		unset($this->rawNodesByIdentifierGroupedByWorkspace[$this->workspaceName][$node->getIdentifier()]);
	}

	/**
	 * Adds a property in the storage
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to insert
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo implement support for multi-value properties
	 */
	public function addProperty(F3_PHPCR_PropertyInterface $property) {
		$this->rawPropertiesByIdentifierGroupedByWorkspace[$this->workspaceName][$property->getParent()->getIdentifier()][$property->getName()] = array(
			'name' => $property->getName(),
			'value' => $property->getString(),
			'namespace' => '',
			'multivalue' => FALSE,
			'type' => $property->getType()
		);
	}

	/**
	 * Updates a property in the repository identified by identifier and name
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to update
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateProperty(F3_PHPCR_PropertyInterface $property) {
		$this->addProperty($property);
	}

	/**
	 * Removes a property in the storage
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to remove
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeProperty(F3_PHPCR_PropertyInterface $property) {
		unset($this->rawPropertiesByIdentifierGroupedByWorkspace[$this->workspaceName][$property->getParent()->getIdentifier()][$property->getName()]);
	}

	/**
	 * Returns an array with identifiers matching the query
	 *
	 * @param F3_PHPCR_Query_QOM_QueryObjectModelInterface $query
	 * @return array
	 */
	public function findNodeIdentifiers(F3_PHPCR_Query_QOM_QueryObjectModelInterface $query) {
		return array();
	}

	/**
	 * Sets the search engine used by the storage backend.
	 *
	 * @param F3_TYPO3CR_Storage_SearchInterface $searchEngine
	 * @return void
	 */
	public function setSearchEngine(F3_TYPO3CR_Storage_SearchInterface $searchEngine) {
	}

	/**
	 * Sets the namespace registry used by the storage backend
	 *
	 * @param F3_PHPCR_NamespaceRegistryInterface $namespaceRegistry
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function setNamespaceRegistry(F3_PHPCR_NamespaceRegistryInterface $namespaceRegistry) {
		$this->namespaceRegistry = $namespaceRegistry;
	}

	/**
	 * Returns TRUE if the given identifier is used in storage.
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function hasIdentifier($identifier) {
	}

}
?>
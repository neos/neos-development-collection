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
 * @subpackage Storage
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDO.php 888 2008-05-30 16:00:05Z k-fish $
 */

/**
 * A Storage backend using PDO
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDO.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Storage_Backend_PDO implements F3_TYPO3CR_Storage_BackendInterface {

	/**
	 * @var PDO
	 */
	protected $databaseHandle;

	/**
	 * @var string Name of the current workspace
	 */
	protected $workspaceName = 'default';

	/**
	 * Connects to the database using the provided DSN and (optional) user data
	 *
	 * @param string $dsn The DSN to use for connecting to the DB
	 * @param string $username The username to use for connecting to the DB
	 * @param string $password The password to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($dsn, $username = NULL, $password = NULL) {
		$this->databaseHandle = new PDO($dsn, $username, $password);
		$this->databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Sets the name of the current workspace
	 *
	 * @param  string $workspaceName Name of the workspace which should be used for all storage operations
	 * @return void
	 * @throws InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setWorkspaceName($workspaceName) {
		if ($workspaceName == '' || !is_string($workspaceName)) throw new InvalidArgumentException('"' . $workspaceName . '" is not a valid workspace name.', 1200614989);
		$this->workspaceName = $workspaceName;
	}

	/**
	 * Fetches raw node data from the database
	 *
	 * @param integer $nodeId The (internal) ID of the node to fetch
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawNodeById($nodeId) {
		$statementHandle = $this->databaseHandle->prepare('SELECT id, pid, name, identifier, nodetype FROM nodes WHERE id = ?');
		$statementHandle->execute(array($nodeId));
		return $statementHandle->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Fetches raw node data from the database
	 *
	 * @param string $identifier The Identifier of the node to fetch
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawNodeByIdentifier($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT id, pid, name, identifier, nodetype FROM nodes WHERE identifier = ?');
		$statementHandle->execute(array($identifier));
		return $statementHandle->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Fetches raw node data of the root node of the current workspace.
	 *
	 * @return array|FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawRootNode() {
		$statementHandle = $this->databaseHandle->prepare('SELECT id, pid, name, identifier, nodetype FROM nodes WHERE pid = 0');
		$statementHandle->execute();
		return $statementHandle->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Fetches sub node Identifiers from the database
	 *
	 * @param integer $nodeId The node Identifier to fetch (sub-)nodes for
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifiersOfSubNodesOfNode($nodeId) {
		$nodeIdentifiers = array();
		$statementHandle = $this->databaseHandle->prepare('SELECT identifier FROM nodes WHERE pid = ?');
		$statementHandle->execute(array($nodeId));
		$rawNodes = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rawNodes as $k => $rawNode) {
			$nodeIdentifiers[] = $rawNode['identifier'];
		}
		return $nodeIdentifiers;
	}

	/**
	 * Fetches raw property data from the database
	 *
	 * @param integer $nodeIdentifier The node Identifier to fetch properties for
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawPropertiesOfNode($nodeIdentifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT name, value, namespace, multivalue FROM properties WHERE nodeidentifier = ?');
		$statementHandle->execute(array($nodeIdentifier));
		$properties = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
		if (is_array($properties) && $properties['multivalue']) {
			$properties['value'] = unserialize($properties['value']);
		}
		return $properties;
	}

	/**
	 * Fetches raw data for all nodetypes from the database
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawNodeTypes() {
		$statementHandle = $this->databaseHandle->query('SELECT name FROM nodetypes');
		$nodetypes = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
		return $nodetypes;
	}

	/**
	 * Fetches raw nodetype data from the database
	 *
	 * @param string $nodeTypeNme The name of the nodetype record to fetch
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawNodeType($nodeTypeName) {
		$statementHandle = $this->databaseHandle->prepare('SELECT name FROM nodetypes WHERE name = ?');
		$statementHandle->execute(array($nodeTypeName));
		return $statementHandle->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Adds the given nodetype to the database
	 *
	 * @param F3_PHPCR_NodeType_NodeTypeDefinitionInterface $nodeTypeDefinition
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addNodeType(F3_PHPCR_NodeType_NodeTypeDefinitionInterface $nodeTypeDefinition) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO nodetypes (name) VALUES (?)');
		$statementHandle->execute(array(
			$nodeTypeDefinition->getName()
		));
	}

		/**
	 * Deletes the named nodetype from the database
	 *
	 * @param string $name
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function deleteNodeType($name) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM nodetypes WHERE name=?');
		$statementHandle->execute(array($name));
	}

	/**
	 * Adds a node to the repository
	 *
	 * @param string $identifier Identifier to insert
	 * @param string $pid Identifier of the parent node
	 * @param string $nodetype Nodetype to insert
	 * @param string $name Name to insert
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function addNode($identifier, $pid, $nodetype, $name) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO nodes (identifier, pid, nodetype, name) VALUES (?, ?, ?, ?)');
		$statementHandle->execute(array($identifier, $pid, $nodetype, $name));
	}

	/**
	 * Updates a node in the repository
	 *
	 * @param string $identifier Identifier of the node to update
	 * @param string $pid Identifier of the parent node
	 * @param string $nodetype Nodetype to update
	 * @param string $name Name to update
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function updateNode($identifier, $pid, $nodetype, $name) {
		$statementHandle = $this->databaseHandle->prepare('UPDATE nodes SET pid=?, nodetype=?, name=? WHERE identifier=?');
		$statementHandle->execute(array($pid, $nodetype, $name, $identifier));
	}

	/**
	 * Deletes a node in the repository
	 *
	 * @param string $identifier Identifier of the node to delete
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function removeNode($identifier) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM nodes WHERE identifier=?');
		$statementHandle->execute(array($identifier));
	}

	/**
	 * Adds a property in the repository
	 *
	 * @param string $identifier Identifier of parent node
	 * @param string $name Name of property
	 * @param string $value Value of property
	 * @param boolean $isMultiValued
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function addProperty($identifier, $name, $value, $isMultiValued) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO properties (nodeidentifier, name, value, namespace, multivalue) VALUES (?, ?, ?, 0, ?)');
		$statementHandle->execute(array($identifier, $name, $value, $isMultiValued));
	}

	/**
	 * Updates a property in the repository identified by identifier and name
	 *
	 * @param string $identifier Identifier of parent node
	 * @param string $name Name of property
	 * @param string $value Value of property
	 * @param boolean $isMultiValued
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function updateProperty($identifier, $name, $value, $isMultiValued) {
		$statementHandle = $this->databaseHandle->prepare('UPDATE properties SET value=?, multivalue=? WHERE nodeidentifier=? AND name=?');
		$statementHandle->execute(array($value, $isMultiValued, $identifier, $name));
	}

	/**
	 * Deletes a property in the repository identified by identifier and name
	 *
	 * @param string $identifier Identifier of parent node
	 * @param string $name Name of property
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function removeProperty($identifier, $name) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM properties WHERE nodeidentifier=? AND name=?');
		$statementHandle->execute(array($identifier, $name));
	}

	/**
	 * Fetches raw namespace data from the database
	 *
	 * @return array
	 * @author Sebastian Kurfürst <sebastian@ŧypo3.org>
	 */
	public function getRawNamespaces() {
		$statementHandle = $this->databaseHandle->query('SELECT prefix, uri FROM namespaces');
		$namespaces = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
		return $namespaces;
	}

	/**
	 * Updates the prefix for the namespace identified by $uri
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNamespacePrefix($prefix, $uri) {
		$statementHandle = $this->databaseHandle->prepare('UPDATE namespaces SET prefix=? WHERE uri=?');
		$statementHandle->execute(array($prefix,$uri));
	}

	/**
	 * Updates the URI for the namespace identified by $prefix
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNamespaceURI($prefix, $uri) {
		$statementHandle = $this->databaseHandle->prepare('UPDATE namespaces SET uri=? WHERE prefix=?');
		$statementHandle->execute(array($uri,$prefix));
	}

	/**
	 * Adds a namespace identified by prefix and URI
	 *
	 * @param string $prefix The namespace prefix to register
	 * @param string $uri The namespace URI to register
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function addNamespace($prefix, $uri) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO namespaces (prefix,uri) VALUES (?, ?)');
		$statementHandle->execute(array($prefix,$uri));
	}

	/**
	 * Deletes the namespace identified by $prefix.
	 *
	 * @param string $prefix The prefix of the namespace to delete
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function deleteNamespace($prefix) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM namespaces WHERE prefix=?');
		$statementHandle->execute(array($prefix));
	}
}

?>
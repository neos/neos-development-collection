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
class F3_TYPO3CR_MockStorageAccess implements F3_TYPO3CR_StorageAccessInterface {

	/**
	 * @var array This array can be set from tests to mock raw node arrays
	 */
	public $rawNodesByUUIDGroupedByWorkspace = array();

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
	public $rawPropertiesByUUIDGroupedByWorkspace = array();

	/**
	 * @var F3_TYPO3CR_Workspace
	 */
	protected $workspaceName = 'default';

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
	 * @param  integer $id The (internal) ID of the node to fetch
	 * @return array|FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawNodeById($Id) {
		if (key_exists($this->workspaceName, $this->rawNodesByIDGroupedByWorkspace)) {
			if (key_exists($id, $this->rawNodesByIDGroupedByWorkspace[$this->workspaceName])) {
				return $this->rawNodesByIDGroupedByWorkspace[$this->workspaceName][$id];
			}
		}
		return FALSE;
	}

	/**
	 * Fetches raw node data from the database
	 *
	 * @param  string $uuid The UUID of the node to fetch
	 * @return array|FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawNodeByUUID($uuid) {
		if (key_exists($this->workspaceName, $this->rawNodesByUUIDGroupedByWorkspace)) {
			if (key_exists($uuid, $this->rawNodesByUUIDGroupedByWorkspace[$this->workspaceName])) {
				return $this->rawNodesByUUIDGroupedByWorkspace[$this->workspaceName][$uuid];
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
	 * Fetches sub node UUIDs from the database
	 *
	 * @param integer $nodeId The node uid to fetch (sub-)nodes for
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getUUIDsOfSubNodesOfNode($nodeId) {
		$uuids = array();
		if (key_exists($this->workspaceName, $this->rawNodesByUUIDGroupedByWorkspace)) {
			if (key_exists($nodeId, $this->rawNodesByUUIDGroupedByWorkspace[$this->workspaceName])) {
				foreach ($this->rawNodesByUUIDGroupedByWorkspace[$this->workspaceName] as $uuid => $rawNode) {
					if ($rawNode['pid'] == $nodeId) {
						$uuids[] = $uuid;
					}
				}
			}
		}
		return $uuids;
	}

	/**
	 * Returns raw property data for the specified node
	 *
	 * @param string $nodeUUID The node UUID to fetch properties for
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawPropertiesOfNode($nodeUUID) {
		if (key_exists($this->workspaceName, $this->rawPropertiesByUUIDGroupedByWorkspace)) {
			if (key_exists($nodeUUID, $this->rawPropertiesByUUIDGroupedByWorkspace[$this->workspaceName])) {
				return $this->rawPropertiesByUUIDGroupedByWorkspace[$this->workspaceName][$nodeUUID];
			}
		}
		return FALSE;
	}

	/**
	 * Fetches raw nodetype data from the database
	 *
	 * @param integer $nodeTypeId The (internal) id of the nodetype record to fetch
	 * @return array
	 */
	public function getRawNodeTypeById($nodeTypeId) {

	}

	/**
	 * Adds a node to the storage
	 *
	 * @param string $uuid UUID to insert
	 * @param string $pid UUID of the parent node
	 * @param integer $nodetype Nodetype to insert
	 * @param string $name Name to insert
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addNode($uuid, $pid, $nodetype, $name) {
		$this->rawNodesByUUIDGroupedByWorkspace[$this->workspaceName][$uuid] = array(
			'uuid' => $uuid,
			'pid' => $pid,
			'nodetype' => $nodetype,
			'name' => $name
		);
	}

	/**
	 * Updates a node in the storage
	 *
	 * @param string $uuid UUID of the node to update
	 * @param string $pid UUID of the parent node
	 * @param integer $nodetype new nodetype
	 * @param string $name new name
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNode($uuid, $pid, $nodetype, $name) {
		$this->addNode($uuid, $pid, $nodetype, $name);
	}

	/**
	 * Adds a property in the storage
	 *
	 * @param string $uuid UUID of parent node
	 * @param string $name Name of property
	 * @param string $value Value of property
	 * @param boolean $isMultiValued
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addProperty($uuid, $name, $value, $isMultiValued) {
		$this->rawPropertiesByUUIDGroupedByWorkspace[$this->workspaceName][$uuid][] = array(
			'name' => $name,
			'value' => $value,
			'namespace' => '',
			'multivalue' => $isMultiValued
		);
	}

	/**
	 * Updates a property in the repository identified by uuid and name
	 *
	 * @param string $uuid UUID of parent node
	 * @param string $name Name of property
	 * @param string $value Value of property
	 * @param boolean $isMultiValued
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateProperty($uuid, $name, $value, $isMultiValued) {
		$this->addProperty($uuid, $name, $value, $isMultiValued);
	}

}
?>
<?php
declare(encoding = 'utf-8');

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
 * A mock Storage Access
 *
 * @package		TYPO3CR
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3CR_MockStorageAccess implements T3_TYPO3CR_StorageAccessInterface {

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
	 * @var T3_TYPO3CR_Workspace
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
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawNodeById($Id) {
		if (key_exists($this->workspaceName, $this->rawNodesByIDGroupedByWorkspace)) {
			if (key_exists($id, $this->rawNodesByIDGroupedByWorkspace[$this->workspaceName])) {
				return $this->rawNodesByIDGroupedByWorkspace[$this->workspaceName][$id];
			}
		}
	}

	/**
	 * Fetches raw node data from the database
	 *
	 * @param  string $uuid The UUID of the node to fetch
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawNodeByUUID($uuid) {
		if (key_exists($this->workspaceName, $this->rawNodesByUUIDGroupedByWorkspace)) {
			if (key_exists($uuid, $this->rawNodesByUUIDGroupedByWorkspace[$this->workspaceName])) {
				return $this->rawNodesByUUIDGroupedByWorkspace[$this->workspaceName][$uuid];
			}
		}
		return array();
	}

	/**
	 * Fetches raw node data of the root node of the current workspace.
	 * 
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawRootNode() {
		if (key_exists($this->workspaceName, $this->rawRootNodesByWorkspace)) {
			return $this->rawRootNodesByWorkspace[$this->workspaceName];
		}
		return array();
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
}
?>
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
 * StorageAccess interface
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_TYPO3CR_StorageAccessInterface {

	/**
	 * Sets the name of the current workspace
	 *
	 * @param  string $workspaceName Name of the workspace which should be used for all storage operations
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function setWorkspaceName($workspaceName);

	/**
	 * Fetches raw node data from the database
	 *
	 * @param  integer $id The (internal) ID of the node to fetch
	 * @return array|FALSE
	 */
	public function getRawNodeById($Id);

	/**
	 * Fetches raw node data from the database
	 *
	 * @param  string $identifier The Identifier of the node to fetch
	 * @return array|FALSE
	 */
	public function getRawNodeByIdentifier($identifier);

	/**
	 * Fetches raw node data of the root node of the current workspace.
	 *
	 * @return array|FALSE
	 */
	public function getRawRootNode();

	/**
	 * Fetches raw namespace data from the database
	 *
	 * @return array
	 */
	public function getRawNamespaces();

	/**
	 * Adds a namespace identified by prefix and URI
	 *
	 * @param string $prefix The namespace prefix to register
	 * @param string $uri The namespace URI to register
	 */
	public function addNamespace($prefix, $uri);

	/**
	 * Updates the prefix for the namespace identified by $uri
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 */
	public function updateNamespacePrefix($prefix, $uri);

	/**
	 * Updates the URI for the namespace identified by $prefix
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 */
	public function updateNamespaceURI($prefix, $uri);

	/**
	 * Deletes the namespace identified by $prefix.
	 *
	 * @param string $prefix The prefix of the namespace to delete
	 */
	public function deleteNamespace($prefix);

	/**
	 * Fetches sub node Identifiers from the database
	 *
	 * @param integer $nodeId The node Identifier to fetch (sub-)nodes for
	 * @return array
	 */
	public function getIdentifiersOfSubNodesOfNode($nodeId);

	/**
	 * Fetches raw property data from the database
	 *
	 * @param integer $nodeIdentifier The node Identifier to fetch properties for
	 * @return array|FALSE
	 */
	public function getRawPropertiesOfNode($nodeIdentifier);

	/**
	 * Fetches raw nodetype data from the database
	 *
	 * @param integer $nodeTypeId The (internal) id of the nodetype record to fetch
	 * @return array|FALSE
	 */
	public function getRawNodeTypeById($nodeTypeId);

	/**
	 * Adds a node to the storage
	 *
	 * @param string $identifier Identifier to insert
	 * @param string $pid Identifier of the parent node
	 * @param integer $nodetype Nodetype to insert
	 * @param string $name Name to insert
	 * @return void
	 */
	public function addNode($identifier, $pid, $nodetype, $name);

	/**
	 * Adds a property in the storage
	 *
	 * @param string $identifier Identifier of parent node
	 * @param string $name Name of property
	 * @param string $value Value of property
	 * @param boolean $isMultiValued
	 * @return void
	 */
	public function addProperty($identifier, $name, $value, $isMultiValued);

	/**
	 * Updates a node in the storage
	 *
	 * @param string $identifier Identifier of the node to update
	 * @param string $pid Identifier of the parent node
	 * @param integer $nodetype new nodetype
	 * @param string $name new name
	 * @return void
	 */
	public function updateNode($identifier, $pid, $nodetype, $name);

	/**
	 * Updates a property in the repository identified by identifier and name
	 *
	 * @param string $identifier Identifier of parent node
	 * @param string $name Name of property
	 * @param string $value Value of property
	 * @param boolean $isMultiValued
	 * @return void
	 */
	public function updateProperty($identifier, $name, $value, $isMultiValued);
}
?>
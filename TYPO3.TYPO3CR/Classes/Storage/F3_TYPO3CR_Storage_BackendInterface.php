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
 * @version $Id:F3_TYPO3CR_Storage_BackendInterface.php 888 2008-05-30 16:00:05Z k-fish $
 */

/**
 * Storage backend interface
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id:F3_TYPO3CR_Storage_BackendInterface.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_TYPO3CR_Storage_BackendInterface {

	/**
	 * Sets the name of the current workspace
	 *
	 * @param string $workspaceName Name of the workspace which should be used for all storage operations
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function setWorkspaceName($workspaceName);

	/**
	 * Fetches raw node data from the database
	 *
	 * @param string $identifier The Identifier of the node to fetch
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
	 * @param integer $identifier The node Identifier to fetch (sub-)nodes for
	 * @return array
	 */
	public function getIdentifiersOfSubNodesOfNode($identifier);

	/**
	 * Fetches raw property data from the database
	 *
	 * @param integer $identifier The node Identifier to fetch properties for
	 * @return array
	 */
	public function getRawPropertiesOfNode($identifier);

	/**
	 * Fetches raw data for all nodetypes from the database
	 *
	 * @return array
	 */
	public function getRawNodeTypes();

	/**
	 * Fetches raw nodetype data from the database
	 *
	 * @param string $nodeTypeName The name of the nodetype record to fetch
	 * @return array|FALSE
	 */
	public function getRawNodeType($nodeTypeName);

	/**
	 * Adds the given nodetype to the storage
	 *
	 * @param F3_PHPCR_NodeType_NodeTypeDefinitionInterface $nodeTypeDefinition
	 * @return void
	 */
	public function addNodeType(F3_PHPCR_NodeType_NodeTypeDefinitionInterface $nodeTypeDefinition);

	/**
	 * Deletes the named nodetype from the storage
	 *
	 * @param string $name
	 * @return void
	 */
	public function deleteNodeType($name);

	/**
	 * Adds a node to the storage
	 *
	 * @param F3_PHPCR_NodeInterface $node node to insert
	 * @return void
	 */
	public function addNode(F3_PHPCR_NodeInterface $node);

	/**
	 * Adds a property in the storage
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to insert
	 * @return void
	 */
	public function addProperty(F3_PHPCR_PropertyInterface $property);

	/**
	 * Updates a node in the storage
	 *
	 * @param F3_PHPCR_NodeInterface $node node to update
	 * @return void
	 */
	public function updateNode(F3_PHPCR_NodeInterface $node);

	/**
	 * Updates a property in the storage
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to update
	 * @return void
	 */
	public function updateProperty(F3_PHPCR_PropertyInterface $property);

	/**
	 * Deletes a node in the storage
	 *
	 * @param F3_PHPCR_NodeInterface $node node to delete
	 * @return void
	 */
	public function removeNode(F3_PHPCR_NodeInterface $node);

	/**
	 * Removes a property in the storage
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to remove
	 * @return void
	 */
	public function removeProperty(F3_PHPCR_PropertyInterface $property);
}
?>
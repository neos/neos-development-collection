<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Storage;

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
 * @version $Id:F3::TYPO3CR::Storage::BackendInterface.php 888 2008-05-30 16:00:05Z k-fish $
 */

/**
 * Storage backend interface
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id:F3::TYPO3CR::Storage::BackendInterface.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface BackendInterface {

	/**
	 * Connect to the storage backend
	 *
	 * @return void
	 */
	public function connect();

	/**
	 * Disconnect from the storage backend
	 *
	 * @return void
	 */
	public function disconnect();

	/**
	 * Sets the name of the current workspace
	 *
	 * @param string $workspaceName Name of the workspace which should be used for all storage operations
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function setWorkspaceName($workspaceName);

	/**
	 * Sets the search engine used by the storage backend.
	 *
	 * @param F3::TYPO3CR::Storage::SearchInterface $searchEngine
	 * @return void
	 */
	public function setSearchEngine(F3::TYPO3CR::Storage::SearchInterface $searchEngine);

	/**
	 * Sets the namespace registry used by the backend to translate prefixed names into (URI, name) tuples
	 *
	 * @param F3::PHPCR::NamespaceRegistryInterface $namespaceRegistry
	 * @return void
	 */
	public function setNamespaceRegistry(F3::PHPCR::NamespaceRegistryInterface $namespaceRegistry);

	/**
	 * Returns TRUE if the given identifier is used in storage.
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function hasIdentifier($identifier);



	/**
	 * Fetches raw node data of the root node of the current workspace.
	 *
	 * @return array|FALSE
	 */
	public function getRawRootNode();

	/**
	 * Fetches raw node data from the database
	 *
	 * @param string $identifier The Identifier of the node to fetch
	 * @return array|FALSE
	 */
	public function getRawNodeByIdentifier($identifier);

	/**
	 * Fetches sub node Identifiers from the database
	 *
	 * @param string $identifier The node Identifier to fetch (sub-)nodes for
	 * @return array
	 */
	public function getIdentifiersOfSubNodesOfNode($identifier);

	/**
	 * Adds a node to the storage
	 *
	 * @param F3::PHPCR::NodeInterface $node node to insert
	 * @return void
	 */
	public function addNode(F3::PHPCR::NodeInterface $node);

	/**
	 * Updates a node in the storage
	 *
	 * @param F3::PHPCR::NodeInterface $node node to update
	 * @return void
	 */
	public function updateNode(F3::PHPCR::NodeInterface $node);

	/**
	 * Deletes a node in the storage
	 *
	 * @param F3::PHPCR::NodeInterface $node node to delete
	 * @return void
	 */
	public function removeNode(F3::PHPCR::NodeInterface $node);

	/**
	 * Returns an array with identifiers matching the query
	 *
	 * @param F3::PHPCR::Query::QOM::QueryObjectModelInterface $query
	 * @return array
	 */
	public function findNodeIdentifiers(F3::PHPCR::Query::QOM::QueryObjectModelInterface $query);



	/**
	 * Fetches raw property data from the database
	 *
	 * @param string $identifier The node Identifier to fetch properties for
	 * @return array
	 */
	public function getRawPropertiesOfNode($identifier);

	/**
	 * Fetches raw properties with the given type and value from the database
	 *
	 * @param string $name name of the reference properties considered, if NULL properties of any name will be returned
	 * @param integer $type one of the types defined in F3::PHPCR::PropertyType
	 * @param $value a value of the given type
	 * @return array
	 */
	public function getRawPropertiesOfTypedValue($name, $type, $value);

	/**
	 * Adds a property in the storage
	 *
	 * @param F3::PHPCR::PropertyInterface $property property to insert
	 * @return void
	 */
	public function addProperty(F3::PHPCR::PropertyInterface $property);

	/**
	 * Updates a property in the storage
	 *
	 * @param F3::PHPCR::PropertyInterface $property property to update
	 * @return void
	 */
	public function updateProperty(F3::PHPCR::PropertyInterface $property);

	/**
	 * Removes a property in the storage
	 *
	 * @param F3::PHPCR::PropertyInterface $property property to remove
	 * @return void
	 */
	public function removeProperty(F3::PHPCR::PropertyInterface $property);



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
	 * @param F3::PHPCR::NodeType::NodeTypeDefinitionInterface $nodeTypeDefinition
	 * @return void
	 */
	public function addNodeType(F3::PHPCR::NodeType::NodeTypeDefinitionInterface $nodeTypeDefinition);

	/**
	 * Deletes the named nodetype from the storage
	 *
	 * @param string $name
	 * @return void
	 */
	public function deleteNodeType($name);

}
?>
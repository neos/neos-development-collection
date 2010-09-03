<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Storage;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Storage backend interface
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
interface BackendInterface {

	/**
	 * Connect to the storage backend
	 *
	 * @return void
	 * @api
	 */
	public function connect();

	/**
	 * Disconnect from the storage backend
	 *
	 * @return void
	 * @api
	 */
	public function disconnect();

	/**
	 * Sets the name of the current workspace
	 *
	 * @param string $workspaceName Name of the workspace which should be used for all storage operations
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setWorkspaceName($workspaceName);

	/**
	 * Sets the search backend used by the storage backend.
	 *
	 * @param \F3\TYPO3CR\Storage\SearchInterface $searchBackend
	 * @return void
	 * @api
	 */
	public function setSearchBackend(\F3\TYPO3CR\Storage\SearchInterface $searchBackend);

	/**
	 * Returns the search backend used by the storage backend.
	 *
	 * @return \F3\TYPO3CR\Storage\SearchInterface
	 * @api
	 */
	public function getSearchBackend();

	/**
	 * Sets the namespace registry used by the backend to translate prefixed names into (URI, name) tuples
	 *
	 * @param \F3\PHPCR\NamespaceRegistryInterface $namespaceRegistry
	 * @return void
	 * @api
	 */
	public function setNamespaceRegistry(\F3\PHPCR\NamespaceRegistryInterface $namespaceRegistry);

	/**
	 * Returns TRUE if the given identifier is used in storage.
	 *
	 * @param string $identifier
	 * @return boolean
	 * @api
	 */
	public function hasIdentifier($identifier);

	/**
	 * Returns TRUE of the node with the given identifier is a REFERENCE target
	 *
	 * @param string $identifier The UUID of the node to check for
	 * @return boolean
	 * @api
	 */
	public function isReferenceTarget($identifier);



	/**
	 * Fetches raw node data of the root node of the current workspace.
	 *
	 * @return array|FALSE
	 * @api
	 */
	public function getRawRootNode();

	/**
	 * Fetches raw node data from the database
	 *
	 * @param string $identifier The Identifier of the node to fetch
	 * @return array|FALSE
	 * @api
	 */
	public function getRawNodeByIdentifier($identifier);

	/**
	 * Fetches sub node Identifiers from the database
	 *
	 * @param string $identifier The node Identifier to fetch (sub-)nodes for
	 * @return array
	 * @api
	 */
	public function getIdentifiersOfSubNodesOfNode($identifier);

	/**
	 * Adds a node to the storage
	 *
	 * @param \F3\PHPCR\NodeInterface $node node to insert
	 * @return void
	 * @api
	 */
	public function addNode(\F3\PHPCR\NodeInterface $node);

	/**
	 * Updates a node in the storage
	 *
	 * @param \F3\PHPCR\NodeInterface $node node to update
	 * @return void
	 * @api
	 */
	public function updateNode(\F3\PHPCR\NodeInterface $node);

	/**
	 * Deletes a node in the storage
	 *
	 * @param \F3\PHPCR\NodeInterface $node node to delete
	 * @return void
	 * @api
	 */
	public function removeNode(\F3\PHPCR\NodeInterface $node);

	/**
	 * Checks whether the node with the given $identifier has a child node with
	 * the given $nodeName.
	 *
	 * @param string $identifier the identifier of the parent
	 * @param string $nodeName the name of the childnode
	 * @return boolean
	 * @api
	 */
	public function hasChildNodeWithName($identifier, $nodeName);



	/**
	 * Fetches raw property data from the database
	 *
	 * @param string $identifier The node Identifier to fetch properties for
	 * @return array
	 * @api
	 */
	public function getRawPropertiesOfNode($identifier);

	/**
	 * Fetches raw properties with the given type and value from the database
	 *
	 * @param string $name name of the reference properties considered, if NULL properties of any name will be returned
	 * @param integer $type one of the types defined in \F3\PHPCR\PropertyType
	 * @param mixed $value a value of the given type
	 * @return array
	 * @api
	 */
	public function getRawPropertiesOfTypedValue($name, $type, $value);

	/**
	 * Adds a property in the storage
	 *
	 * @param \F3\PHPCR\PropertyInterface $property property to insert
	 * @return void
	 * @api
	 */
	public function addProperty(\F3\PHPCR\PropertyInterface $property);

	/**
	 * Updates a property in the storage
	 *
	 * @param \F3\PHPCR\PropertyInterface $property property to update
	 * @return void
	 * @api
	 */
	public function updateProperty(\F3\PHPCR\PropertyInterface $property);

	/**
	 * Removes a property in the storage
	 *
	 * @param \F3\PHPCR\PropertyInterface $property property to remove
	 * @return void
	 * @api
	 */
	public function removeProperty(\F3\PHPCR\PropertyInterface $property);



	/**
	 * Fetches raw namespace data from the database
	 *
	 * @return array
	 * @api
	 */
	public function getRawNamespaces();

	/**
	 * Adds a namespace identified by prefix and URI
	 *
	 * @param string $prefix The namespace prefix to register
	 * @param string $uri The namespace URI to register
	 * @api
	 */
	public function addNamespace($prefix, $uri);

	/**
	 * Updates the prefix for the namespace identified by $uri
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 * @api
	 */
	public function updateNamespacePrefix($prefix, $uri);

	/**
	 * Updates the URI for the namespace identified by $prefix
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 * @api
	 */
	public function updateNamespaceURI($prefix, $uri);

	/**
	 * Deletes the namespace identified by $prefix.
	 *
	 * @param string $prefix The prefix of the namespace to delete
	 * @api
	 */
	public function deleteNamespace($prefix);



	/**
	 * Fetches raw data for all nodetypes from the database
	 *
	 * @return array
	 * @api
	 */
	public function getRawNodeTypes();

	/**
	 * Fetches raw nodetype data from the database
	 *
	 * @param string $nodeTypeName The name of the nodetype record to fetch
	 * @return array|FALSE
	 * @api
	 */
	public function getRawNodeType($nodeTypeName);

	/**
	 * Adds the given nodetype to the storage
	 *
	 * @param \F3\PHPCR\NodeType\NodeTypeDefinitionInterface $nodeTypeDefinition
	 * @return void
	 * @api
	 */
	public function addNodeType(\F3\PHPCR\NodeType\NodeTypeDefinitionInterface $nodeTypeDefinition);

	/**
	 * Deletes the named nodetype from the storage
	 *
	 * @param string $name
	 * @return void
	 * @api
	 */
	public function deleteNodeType($name);

}
?>
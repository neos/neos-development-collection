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
 * @subpackage NodeType
 * @version $Id$
 */

/**
 * Allows for the retrieval and (in implementations that support it) the
 * registration of node types. Accessed via Workspace.getNodeTypeManager().
 *
 * @package TYPO3CR
 * @subpackage NodeType
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_NodeType_NodeTypeManager implements F3_PHPCR_NodeType_NodeTypeManagerInterface {

	/**
	 * @var F3_TYPO3CR_Storage_BackendInterface
	 */
	protected $storageAccess;

	/**
	 * @var F3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * Constructs a NodeTypeManager object
	 *
	 * @param string $name
	 * @param F3_PHPCR_StorageAccess_StorageAccessInterface $storageAccess
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_TYPO3CR_Storage_BackendInterface $storageAccess, F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->storageAccess = $storageAccess;
		$this->componentManager = $componentManager;
	}

	/**
	 * Returns the named node type.
	 *
	 * @param string $nodeTypeName the name of an existing node type.
	 * @return F3_TYPO3CR_NodeType_NodeType A NodeType object.
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException if no node type by the given name exists.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function getNodeType($nodeTypeName) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012218);
	}

	/**
	 * Returns true if a node type with the specified name is registered. Returns
	 * false otherwise.
	 *
	 * @param string $name a String.
	 * @return boolean a boolean
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function hasNodeType($name) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012219);
	}

	/**
	 * Returns an iterator over all available node types (primary and mixin).
	 *
	 * @return F3_TYPO3CR_NodeType_NodeTypeIterator An NodeTypeIterator.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function getAllNodeTypes() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012220);
	}

	/**
	 * Returns an iterator over all available primary node types.
	 *
	 * @return F3_TYPO3CR_NodeType_NodeTypeIterator An NodeTypeIterator.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function getPrimaryNodeTypes() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012221);
	}

	/**
	 * Returns an iterator over all available mixin node types. If none are available,
	 * an empty iterator is returned.
	 *
	 * @return F3_TYPO3CR_NodeType_NodeTypeIterator An NodeTypeIterator.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function getMixinNodeTypes() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012222);
	}

	/**
	 * Returns an empty NodeTypeTemplate which can then be used to define a node type
	 * and passed to NodeTypeManager.registerNodeType.
	 *
	 * If $ntd is given:
	 * Returns a NodeTypeTemplate holding the specified node type definition. This
	 * template can then be altered and passed to NodeTypeManager.registerNodeType.
	 *
	 * @param F3_TYPO3CR_NodeType_NodeTypeDefinition $ntd a NodeTypeDefinition.
	 * @return F3_TYPO3CR_NodeType_NodeTypeTemplate A NodeTypeTemplate.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createNodeTypeTemplate($ntd = NULL) {
		if ($ntd !== NULL) {
			throw new F3_PHPCR_UnsupportedRepositoryOperationException('Updating node types is not yet implemented, sorry!', 1213013720);
		}

		return $this->componentManager->getComponent('F3_TYPO3CR_NodeType_NodeTypeTemplate');
	}

	/**
	 * Returns an empty NodeDefinitionTemplate which can then be used to create a
	 * child node definition and attached to a NodeTypeTemplate.
	 * Throws an UnsupportedRepositoryOperationException if this implementation does
	 * not support node type registration.
	 *
	 * @return F3_TYPO3CR_NodeType_NodeDefinitionTemplate A NodeDefinitionTemplate.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function createNodeDefinitionTemplate() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012224);
	}

	/**
	 * Returns an empty PropertyDefinitionTemplate which can then be used to create
	 * a property definition and attached to a NodeTypeTemplate.
	 *
	 * @return F3_TYPO3CR_NodeType_PropertyDefinitionTemplate A PropertyDefinitionTemplate.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function createPropertyDefinitionTemplate() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012225);
	}

	/**
	 * Registers a new node type or updates an existing node type using the specified
	 * definition and returns the resulting NodeType object.
	 * Typically, the object passed to this method will be a NodeTypeTemplate (a
	 * subclass of NodeTypeDefinition) acquired from NodeTypeManager.createNodeTypeTemplate
	 * and then filled-in with definition information.
	 *
	 * @param F3_PHPCR_NodeType_NodeTypeDefinitionInterface $ntd an NodeTypeDefinition.
	 * @param boolean $allowUpdate a boolean
	 * @return F3_TYPO3CR_NodeType_NodeType the registered node type
	 * @throws F3_PHPCR_InvalidNodeTypeDefinitionException if the NodeTypeDefinition is invalid.
	 * @throws F3_PHPCR_NodeType_NodeTypeExistsException if allowUpdate is false and the NodeTypeDefinition specifies a node type name that is already registered.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerNodeType(F3_PHPCR_NodeType_NodeTypeDefinitionInterface $ntd, $allowUpdate) {
		if ($allowUpdate === TRUE) {
			throw new F3_PHPCR_UnsupportedRepositoryOperationException('Updating node types is not yet implemented, sorry!', 1213014462);
		}

		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012226);
	}

	/**
	 * Registers or updates the specified Collection of NodeTypeDefinition objects.
	 * This method is used to register or update a set of node types with mutual
	 * dependencies. Returns an iterator over the resulting NodeType objects.
	 * The effect of the method is "all or nothing"; if an error occurs, no node
	 * types are registered or updated.
	 *
	 * @param array $definitions a collection of NodeTypeDefinitions
	 * @param boolean $allowUpdate a boolean
	 * @return F3_PHPCR_NodeType_NodeTypeIteratorInterface the registered node types.
	 * @throws F3_PHPCR_InvalidNodeTypeDefinitionException if a NodeTypeDefinition within the Collection is invalid or if the Collection contains an object of a type other than NodeTypeDefinition.
	 * @throws F3_PHPCR_NodeType_NodeTypeExistsException if allowUpdate is false and a NodeTypeDefinition within the Collection specifies a node type name that is already registered.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function registerNodeTypes(array $definitions, $allowUpdate) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012227);
	}

	/**
	 * Unregisters the specified node type.
	 *
	 * @param string $name a String.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException if no registered node type exists with the specified name.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function unregisterNodeType($name) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012228);
	}

	/**
	 * Unregisters the specified set of node types. Used to unregister a set of node
	 * types with mutual dependencies.
	 *
	 * @param array $names a String array
	 * @return void
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException if one of the names listed is not a registered node type.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function unregisterNodeTypes(array $names) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213012229);
	}
}
?>
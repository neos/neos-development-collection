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
	protected $storageBackend;

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * @var array
	 */
	protected $registeredPrimaryTypes = array();

	/**
	 * @var array
	 */
	protected $registeredMixinTypes = array();

	/**
	 * Constructs a NodeTypeManager object
	 *
	 * @param string $name
	 * @param F3_PHPCR_StorageAccess_StorageAccessInterface $storageBackend
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_TYPO3CR_Storage_BackendInterface $storageBackend, F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->storageBackend = $storageBackend;
		$this->componentFactory = $componentFactory;

		$this->loadKnownNodeTypes();
	}

	/**
	 * Loads all known nodetypes through the storage backend
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function loadKnownNodeTypes() {
		$rawNodeTypes = $this->storageBackend->getRawNodeTypes();
		if (is_array($rawNodeTypes)) {
			foreach ($rawNodeTypes as $rawNodeType) {
				$nodeTypeName = $rawNodeType['name'];
				$nodeType = $this->componentFactory->getComponent('F3_PHPCR_NodeType_NodeTypeInterface', $nodeTypeName);
				if($nodeType->isMixin()) {
					$this->registeredMixinTypes[$nodeTypeName] = $nodeType;
				} else {
					$this->registeredPrimaryTypes[$nodeTypeName] = $nodeType;
				}
			}
		}
	}

	/**
	 * Returns the named node type.
	 *
	 * @param string $nodeTypeName the name of an existing node type.
	 * @return F3_PHPCR_NodeType_NodeTypeInterface A NodeType object.
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException if no node type by the given name exists.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function getNodeType($nodeTypeName) {
		if (isset($this->registeredPrimaryTypes[$nodeTypeName])) {
			return $this->registeredPrimaryTypes[$nodeTypeName];
		} elseif (isset($this->registeredMixinTypes[$nodeTypeName])) {
			return $this->registeredMixinTypes[$nodeTypeName];
		} else {
			$rawNodeType = $this->storageBackend->getRawNodeType($nodeTypeName);
			if($rawNodeType === FALSE) {
				throw new F3_PHPCR_NodeType_NoSuchNodeTypeException('Nodetype "' . $nodeTypeName .'" is not registered', 1213012218);
			} else {
				$nodeType = $this->componentFactory->getComponent('F3_PHPCR_NodeType_NodeTypeInterface', $nodeTypeName);
				if($nodeType->isMixin()) {
					$this->registeredMixinTypes[$nodeTypeName] = $nodeType;
				} else {
					$this->registeredPrimaryTypes[$nodeTypeName] = $nodeType;
				}
				return $nodeType;
			}
		}
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
		if (isset($this->registeredPrimaryTypes[$name]) || isset($this->registeredMixinTypes[$name])) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an iterator over all available node types (primary and mixin).
	 *
	 * @return F3_PHPCR_NodeType_NodeTypeIteratorInterface An NodeTypeIterator.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getAllNodeTypes() {
		return $this->componentFactory->getComponent('F3_PHPCR_NodeType_NodeTypeIteratorInterface', array_merge($this->registeredPrimaryTypes, $this->registeredMixinTypes));
	}

	/**
	 * Returns an iterator over all available primary node types.
	 *
	 * @return F3_PHPCR_NodeType_NodeTypeIteratorInterface An NodeTypeIterator.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPrimaryNodeTypes() {
		return $this->componentFactory->getComponent('F3_PHPCR_NodeType_NodeTypeIteratorInterface', $this->registeredPrimaryTypes);
	}

	/**
	 * Returns an iterator over all available mixin node types. If none are available,
	 * an empty iterator is returned.
	 *
	 * @return F3_PHPCR_NodeType_NodeTypeIteratorInterface An NodeTypeIterator.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getMixinNodeTypes() {
		return $this->componentFactory->getComponent('F3_PHPCR_NodeType_NodeTypeIteratorInterface', $this->registeredMixinTypes);
	}

	/**
	 * Returns an empty NodeTypeTemplate which can then be used to define a node type
	 * and passed to NodeTypeManager.registerNodeType.
	 *
	 * If $ntd is given:
	 * Returns a NodeTypeTemplate holding the specified node type definition. This
	 * template can then be altered and passed to NodeTypeManager.registerNodeType.
	 *
	 * @param F3_PHPCR_NodeType_NodeTypeDefinitionInterface $ntd a NodeTypeDefinition.
	 * @return F3_PHPCR_NodeType_NodeTypeTemplateInterface A NodeTypeTemplate.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createNodeTypeTemplate($ntd = NULL) {
		if ($ntd !== NULL) {
			throw new F3_PHPCR_UnsupportedRepositoryOperationException('Updating node types is not yet implemented, sorry!', 1213013720);
		}

		return $this->componentFactory->getComponent('F3_PHPCR_NodeType_NodeTypeTemplateInterface');
	}

	/**
	 * Returns an empty NodeDefinitionTemplate which can then be used to create a
	 * child node definition and attached to a NodeTypeTemplate.
	 * Throws an UnsupportedRepositoryOperationException if this implementation does
	 * not support node type registration.
	 *
	 * @return F3_PHPCR_NodeType_NodeDefinitionTemplateInterface A NodeDefinitionTemplate.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createNodeDefinitionTemplate() {
		return $this->componentFactory->getComponent('F3_PHPCR_NodeType_NodeDefinitionTemplateInterface');
	}

	/**
	 * Returns an empty PropertyDefinitionTemplate which can then be used to create
	 * a property definition and attached to a NodeTypeTemplate.
	 *
	 * @return F3_PHPCR_NodeType_PropertyDefinitionTemplateInterface A PropertyDefinitionTemplate.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createPropertyDefinitionTemplate() {
		return $this->componentFactory->getComponent('F3_PHPCR_NodeType_PropertyDefinitionTemplateInterface');
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
	 * @return F3_PHPCR_NodeType_NodeTypeInterface the registered node type
	 * @throws F3_PHPCR_InvalidNodeTypeDefinitionException if the NodeTypeDefinition is invalid.
	 * @throws F3_PHPCR_NodeType_NodeTypeExistsException if allowUpdate is false and the NodeTypeDefinition specifies a node type name that is already registered.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo check validity of definition
	 */
	public function registerNodeType(F3_PHPCR_NodeType_NodeTypeDefinitionInterface $ntd, $allowUpdate) {
		if ($allowUpdate === TRUE) {
			throw new F3_PHPCR_UnsupportedRepositoryOperationException('Updating node types is not yet implemented, sorry!', 1213014462);
		}
		$this->storageBackend->addNodeType($ntd);

		return $this->getNodeType($ntd->getName());
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
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo check existence (in case of allowUpdate being false) and validity of definitions before handing over to registerNodeType (all-or-nothing effect)
	 */
	public function registerNodeTypes(array $definitions, $allowUpdate) {
		foreach ($definitions as $definition) {
			if(!($definition instanceof F3_PHPCR_NodeType_NodeTypeDefinitionInterface)) {
				throw new F3_PHPCR_NodeType_InvalidNodeTypeDefinitionException('Cannot register type as NodeType: ' . gettype($definition), 1213178848);
			}
		}

		$nodeTypes = array();
		foreach ($definitions as $definition) {
			$nodeTypes[] = $this->registerNodeType($definition, $allowUpdate);
		}

		return $this->componentFactory->getComponent('F3_PHPCR_NodeType_NodeTypeIteratorInterface', $nodeTypes);
	}

	/**
	 * Unregisters the specified node type.
	 *
	 * @param string $name a String.
	 * @return void
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException if no registered node type exists with the specified name.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterNodeType($name) {
			// make sure we have this nodetype
		$nodeType = $this->getNodeType($name);

		if($nodeType->isMixin()) {
			unset($this->registeredMixinTypes[$name]);
		} else {
			unset($this->registeredPrimaryTypes[$name]);
		}
		$this->storageBackend->deleteNodeType($name);
	}

	/**
	 * Unregisters the specified set of node types. Used to unregister a set of node
	 * types with mutual dependencies.
	 *
	 * @param array $names a String array
	 * @return void
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException if one of the names listed is not a registered node type.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterNodeTypes(array $names) {
		foreach ($names as $name) {
			$this->unregisterNodeType($name);
		}
	}

}
?>
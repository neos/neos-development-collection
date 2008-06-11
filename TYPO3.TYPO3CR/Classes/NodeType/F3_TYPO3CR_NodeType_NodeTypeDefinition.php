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
 * The NodeTypeDefinition interface provides methods for discovering the
 * static definition of a node type. These are accessible both before and
 * after the node type is registered. Its subclass NodeType adds methods
 * that are relevant only when the node type is "live"; that is, after it
 * has been registered. Note that the separate NodeDefinition interface only
 * plays a significant role in implementations that support node type
 * registration. In those cases it serves as the superclass of both NodeType
 * and NodeTypeTemplate. In implementations that do not support node type
 * registration, only objects implementing the subinterface NodeType will
 * be encountered.
 *
 * @package TYPO3CR
 * @subpackage NodeType
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_TYPO3CR_NodeType_NodeTypeDefinition implements F3_PHPCR_NodeType_NodeTypeDefinitionInterface {

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var F3_TYPO3CR_Storage_BackendInterface
	 */
	protected $storageAccess;

	/**
	 * @var integer
	 */
	protected $nodeTypeId;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $declaredSuperTypeNames = array('nt:base');

	/**
	 * @var boolean
	 */
	protected $abstract = FALSE;

	/**
	 * @var boolean
	 */
	protected $mixin = FALSE;

	/**
	 * @var boolean
	 */
	protected $orderableChildNodes = FALSE;

	/**
	 * @var string
	 */
	protected $primaryItemName;

	/**
	 * @var array of PropertyDefinition
	 */
	protected $declaredPropertyDefinitions;

	/**
	 * @var array of NodeDefinition
	 */
	protected $declaredChildNodeDefinitions;

	/**
	 * Returns the name of the node type.
	 * In implementations that support node type registration, if this
	 * NodeTypeDefinition object is actually a newly-created empty
	 * NodeTypeTemplate, then this method will return null.
	 *
	 * @return string a String
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the names of the supertypes actually declared in this node type.
	 * In implementations that support node type registration, if this
	 * NodeTypeDefinition object is actually a newly-created empty
	 * NodeTypeTemplate, then this method will return an array containing a
	 * single string indicating the node type nt:base.
	 *
	 * @return array an array of Strings
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDeclaredSupertypeNames() {
		return $this->declaredSuperTypeNames;
	}

	/**
	 * Returns true if this is an abstract node type; returns false otherwise.
	 * An abstract node type is one that cannot be assigned as the primary or
	 * mixin type of a node but can be used in the definitions of other node
	 * types as a superclass.
	 *
	 * In implementations that support node type registration, if this
	 * NodeTypeDefinition object is actually a newly-created empty
	 * NodeTypeTemplate, then this method will return false.
	 *
	 * @return boolean a boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isAbstract() {
		return $this->abstract;
	}

	/**
	 * Returns true if this is a mixin type; returns false if it is primary.
	 * In implementations that support node type registration, if this
	 * NodeTypeDefinition object is actually a newly-created empty
	 * NodeTypeTemplate, then this method will return false.
	 *
	 * @return boolean a boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isMixin() {
		return $this->mixin;
	}

	/*
	 * Returns true if nodes of this type must support orderable child nodes;
	 * returns false otherwise. If a node type returns true on a call to this
	 * method, then all nodes of that node type must support the method
	 * Node.orderBefore. If a node type returns false on a call to this method,
	 * then nodes of that node type may support Node.orderBefore. Only the primary
	 * node type of a node controls that node's status in this regard. This setting
	 * on a mixin node type will not have any effect on the node.
	 * In implementations that support node type registration, if this
	 * NodeTypeDefinition object is actually a newly-created empty
	 * NodeTypeTemplate, then this method will return false.
	 *
	 * @return boolean a boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasOrderableChildNodes() {
		return $this->orderableChildNodes;
	}

	/**
	 * Returns the name of the primary item (one of the child items of the nodes
	 * of this node type). If this node has no primary item, then this method
	 * returns null. This indicator is used by the method Node.getPrimaryItem().
	 * In implementations that support node type registration, if this
	 * NodeTypeDefinition object is actually a newly-created empty
	 * NodeTypeTemplate, then this method will return null.
	 *
	 * @return string a String
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPrimaryItemName() {
		return $this->primaryItemName;
	}

	/**
	 * Returns an array containing the property definitions actually declared
	 * in this node type.
	 * In implementations that support node type registration, if this
	 * NodeTypeDefinition object is actually a newly-created empty
	 * NodeTypeTemplate, then this method will return null.
	 *
	 * @return array an array of PropertyDefinitions
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDeclaredPropertyDefinitions() {
		return $this->declaredPropertyDefinitions;
	}

	/**
	 * Returns an array containing the child node definitions actually
	 * declared in this node type.
	 * In implementations that support node type registration, if this
	 * NodeTypeDefinition object is actually a newly-created empty
	 * NodeTypeTemplate, then this method will return null.
	 *
	 * @return array an array of NodeDefinitions
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDeclaredChildNodeDefinitions() {
		return $this->declaredChildNodeDefinitions;
	}

	// non-JSR-283 methods below

	/**
	 * Returns the node type ID
	 *
	 * @return integer ID of node type
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function getId() {
		return $this->nodeTypeId;
	}
}

?>
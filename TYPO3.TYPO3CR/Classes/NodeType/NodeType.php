<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\NodeType;

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
 * A NodeType object represents a "live" node type that is registered in the repository.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class NodeType extends \F3\TYPO3CR\NodeType\NodeTypeDefinition implements \F3\PHPCR\NodeType\NodeTypeInterface {

	/**
	 * Constructs a NodeType
	 *
	 * @param string $name The name of the nodetype
	 * @return void
	 * @author Tamas Ilsinszki <ilsinszkitamas@yahoo.com>
	 */
	public function __construct(\F3\TYPO3CR\NodeType\NodeTypeDefinition $nodeTypeDefinition) {
		$this->name = $nodeTypeDefinition->getName();
		$this->declaredSuperTypeNames = $nodeTypeDefinition->getDeclaredSupertypeNames();
		$this->abstract = $nodeTypeDefinition->isAbstract();
		$this->mixin = $nodeTypeDefinition->isMixin();
		$this->orderableChildNodes = $nodeTypeDefinition->hasOrderableChildNodes();
		$this->primaryItemName = $nodeTypeDefinition->getPrimaryItemName();
		$this->declaredPropertyDefinitions = $nodeTypeDefinition->getDeclaredPropertyDefinitions();
		$this->declaredChildNodeDefinitions = $nodeTypeDefinition->getDeclaredChildNodeDefinitions();
	}

	/**
	 * Returns all supertypes of this node type in the node type inheritance
	 * hierarchy. For primary types apart from nt:base, this list will always
	 * include at least nt:base. For mixin types, there is no required supertype.
	 *
	 * @return array of \F3\PHPCR\NodeType\NodeType objects.
	 * @api
	 */
	public function getSupertypes() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212400223);
	}

	/**
	 * Returns the direct supertypes of this node type in the node type
	 * inheritance hierarchy, that is, those actually declared in this node
	 * type. In single-inheritance systems, this will always be an array of
	 * size 0 or 1. In systems that support multiple inheritance of node
	 * types this array may be of size greater than 1.
	 *
	 * @return array of \F3\PHPCR\NodeType\NodeType objects.
	 * @api
	 */
	public function getDeclaredSupertypes() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212400224);
	}

	/**
	 * Returns all subtypes of this node type in the node type inheritance
	 * hierarchy.
	 *
	 * @see getDeclaredSubtypes()
	 *
	 * @return \F3\PHPCR\NodeType\NodeTypeIteratorInterface a NodeTypeIterator.
	 * @api
	 */
	public function getSubtypes() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224589838);
	}

	/**
	 * Returns the direct subtypes of this node type in the node type inheritance
	 * hierarchy, that is, those which actually declared this node type in their
	 * list of supertypes.
	 *
	 * @see getSubtypes()
	 *
	 * @return \F3\PHPCR\NodeType\NodeTypeIteratorInterface a NodeTypeIterator.
	 * @api
	 */
	public function getDeclaredSubtypes() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224589890);
	}

	/**
	 * Returns true if the name of this node type or any of its direct or
	 * indirect supertypes is equal to nodeTypeName, otherwise returns false.
	 *
	 * @param string $nodeTypeName the name of a node type.
	 * @return boolean
	 * @todo Rewrite function when node type hierarchie is implemented
	 * @api
	 */
	public function isNodeType($nodeTypeName) {
		if ($this->name === $nodeTypeName) {
			return TRUE;
		}

		if (is_array($this->declaredSuperTypeNames)) {
			foreach ($this->declaredSuperTypeNames as $declaredSuperTypeName) {
				if ($nodeTypeName === $declaredSuperTypeName) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Returns an array containing the property definitions of this node
	 * type. This includes both those property definitions actually declared
	 * in this node type and those inherited from the supertypes of this type.
	 *
	 * @return array an array of \F3\PHPCR\NodeType\PropertyDefinition containing the property definitions.
	 * @api
	 */
	public function getPropertyDefinitions() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212400226);
	}

	/**
	 * Returns an array containing the child node definitions of this node type.
	 * This includes both those child node definitions actually declared in this
	 * node type and those inherited from the supertypes of this node type.
	 *
	 * @return array an array of \F3\PHPCR\NodeType\NodeDefinition containing the child node definitions.
	 * @api
	 */
	public function getChildNodeDefinitions() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212400227);
	}

	/**
	 * Returns true if setting propertyName to value is allowed by this node type.
	 * Otherwise returns false.
	 *
	 * @param string $propertyName The name of the property
	 * @param \F3\PHPCR\ValueInterface|array $value A \F3\PHPCR\ValueInterface object or an array of \F3\PHPCR\ValueInterface objects.
	 * @return boolean
	 * @api
	 */
	public function canSetProperty($propertyName, $value) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212400228);
	}

	/**
	 * Returns true if this node type allows the addition of a child node called
	 * childNodeName without specific node type information (that is, given the
	 * definition of this parent node type, the child node name is sufficient to
	 * determine the intended child node type). Returns false otherwise.
	 * If $nodeTypeName is given returns true if this node type allows the
	 * addition of a child node called childNodeName of node type nodeTypeName.
	 * Returns false otherwise.
	 *
	 * @param string $childNodeName The name of the child node.
	 * @param string $nodeTypeName The name of the node type of the child node.
	 * @return boolean
	 * @api
	 */
	public function canAddChildNode($childNodeName, $nodeTypeName = NULL) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212400229);
	}

	/**
	 * Returns true if removing the child node called nodeName is allowed by this
	 * node type. Returns false otherwise.
	 *
	 * @param string $nodeName The name of the child node
	 * @return boolean
	 * @api
	 */
	public function canRemoveNode($nodeName) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212400230);
	}

	/**
	 * Returns true if removing the property called propertyName is allowed by this
	 * node type. Returns false otherwise.
	 *
	 * @param string $propertyName The name of the property
	 * @return boolean
	 * @api
	 */
	public function canRemoveProperty($propertyName) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212400231);
	}
}

?>
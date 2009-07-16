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
 * The NodeDefinitionTemplate interface extends NodeDefinition with the addition
 * of write methods, enabling the characteristics of a child node definition to
 * be set, after which the NodeDefinitionTemplate is added to a NodeTypeTemplate.
 *
 * See the corresponding get methods for each attribute in NodeDefinition for the
 * default values assumed when a new empty NodeDefinitionTemplate is created (as
 * opposed to one extracted from an existing NodeType).
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class NodeDefinitionTemplate extends \F3\TYPO3CR\NodeType\NodeDefinition implements \F3\PHPCR\NodeType\NodeDefinitionTemplateInterface {

	/**
	 * Sets the name of the node.
	 *
	 * @param string $name a String.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Sets the auto-create status of the node.
	 *
	 * @param boolean $autoCreated a boolean.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setAutoCreated($autoCreated) {
		$this->autoCreated = $autoCreated;
	}

	/**
	 * Sets the mandatory status of the node.
	 *
	 * @param boolean $mandatory a boolean.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMandatory($mandatory) {
		$this->mandatory = $mandatory;
	}

	/**
	 * Sets the on-parent-version status of the node.
	 *
	 * @param integer $opv an int constant member of OnParentVersionAction.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setOnParentVersion($opv) {
		$this->onParentVersion = $opv;
	}

	/**
	 * Sets the protected status of the node.
	 *
	 * @param boolean $protected a boolean.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setProtected($protected) {
		$this->protected = $protected;
	}

	/**
	 * Sets the names of the required primary types of this node.
	 *
	 * @param array $requiredPrimaryTypeNames a String array.
	 * @return void
	 */
	public function setRequiredPrimaryTypeNames(array $requiredPrimaryTypeNames) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213097328);
	}

	/**
	 * Sets the name of the default primary type of this node.
	 *
	 * @param string $defaultPrimaryTypeName a String.
	 * @return void
	 */
	public function setDefaultPrimaryTypeName($defaultPrimaryTypeName) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213097329);
	}

	/**
	 * Sets the same-name sibling status of this node.
	 *
	 * @param boolean $allowSameNameSiblings a boolean.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setSameNameSiblings($allowSameNameSiblings) {
		$this->sameNameSiblings = $allowSameNameSiblings;
	}

}

?>
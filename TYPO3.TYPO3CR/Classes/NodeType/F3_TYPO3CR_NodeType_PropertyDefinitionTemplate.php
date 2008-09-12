<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::NodeType;

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
 * The PropertyDefinitionTemplate interface extends PropertyDefinition with the
 * addition of write methods, enabling the characteristics of a child property
 * definition to be set, after which the PropertyDefinitionTemplate is added to
 * a NodeTypeTemplate.
 *
 * See the corresponding get methods for each attribute in PropertyDefinition for
 * the default values assumed when a new empty PropertyDefinitionTemplate is created
 * (as opposed to one extracted from an existing NodeType).
 *
 * @package TYPO3CR
 * @subpackage NodeType
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class PropertyDefinitionTemplate extends F3::TYPO3CR::NodeType::PropertyDefinition implements F3::PHPCR::NodeType::PropertyDefinitionTemplateInterface {

	/**
	 * Sets the name of the property.
	 *
	 * @param string $name a String.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Sets the auto-create status of the property.
	 *
	 * @param boolean $autoCreated a boolean.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setAutoCreated($autoCreated) {
		$this->autoCreated = $autoCreated;
	}

	/**
	 * Sets the mandatory status of the property.
	 *
	 * @param boolean $mandatory a boolean.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMandatory($mandatory) {
		$this->mandatory = $mandatory;
	}

	/**
	 * Sets the on-parent-version status of the property.
	 *
	 * @param integer $opv an int constant member of OnParentVersionAction.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setOnParentVersion($opv) {
		$this->onParentVersion = $opv;
	}

	/**
	 * Sets the protected status of the property.
	 *
	 * @param boolean $protected a boolean.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setProtected($protected) {
		$this->protected = $protected;
	}

	/**
	 * Sets the required type of the property.
	 *
	 * @param integer $type an int constant member of PropertyType.
	 * @return void
	 */
	public function setRequiredType($type) {
		$this->requiredType = $type;
	}

	/**
	 * Sets the value constraints of the property.
	 *
	 * @param array $constraints a String array.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueConstraints(array $constraints) {
		$this->valueConstraints = $constraints;
	}

	/**
	 * Sets the default value (or values, in the case of a multi-value property)
	 * of the property.
	 *
	 * @param array $defaultValues a Value array.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setDefaultValues(array $defaultValues) {
		$this->defaultValues = $defaultValues;
	}

	/**
	 * Sets the multi-value status of the property.
	 *
	 * @param boolean $multiple a boolean.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMultiple($multiple) {
		$this->multiple = $multiple;
	}

}

?>
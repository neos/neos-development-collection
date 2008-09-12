<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Query::QOM;

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
 * @subpackage Query
 * @version $Id$
 */

/**
 * Evaluates to the value (or values, if multi-valued) of a property.
 *
 * If, for a node-tuple, the selector node does not have a property named property,
 * the operand evaluates to null.
 *
 * The query is invalid if:
 *
 * selector is not the name of a selector in the query, or
 * property is not a syntactically valid JCR name.
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class PropertyValue extends F3::TYPO3CR::Query::QOM::DynamicOperand implements F3::PHPCR::Query::QOM::PropertyValueInterface {

	/**
	 * @var string
	 */
	protected $selectorName;

	/**
	 * @var string
	 */
	protected $propertyName;

	/**
	 * Constructs this PropertyValue instance
	 *
	 * @param string $propertyName
	 * @param string $selectorName
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($propertyName, $selectorName = '') {
		$this->propertyName = $propertyName;
		$this->selectorName = $selectorName;
	}

	/**
	 * Gets the name of the selector against which to evaluate this operand.
	 *
	 * @return string the selector name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSelectorName() {
		return $this->selectorName;
	}

	/**
	 * Gets the name of the property.
	 *
	 * @return string the property name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPropertyName() {
		return $this->propertyName;
	}

}

?>
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
 * @version $Id$
 */

/**
 * A generic holder for the value of a property. A Value object can be used
 * without knowing the actual property type (STRING, DOUBLE, BINARY etc.).
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_TYPO3CR_Value implements F3_PHPCR_ValueInterface {

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var integer
	 */
	protected $type;

	/**
	 * Constructs a Value object from the given $value and $type arguments
	 *
	 * @param mixed $value The value of the Value object
	 * @param integer $type A type, see constants in F3_PHPCR_PropertyType
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($value, $type) {
		$this->value = $value;
		$this->type = $type;
	}

	/**
	 * Returns a Binary representation of this value. The Binary object in turn provides
	 * methods to access the binary data itself. Uses the standard conversion to binary
	 * (see JCR specification).
	 *
	 * @return F3_TYPO3CR_Binary A Binary representation of this value.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBinary() {

	}

	/**
	 * Returns a string representation of this value. For Value objects being
	 * of type DATE the string will conform to ISO8601 format.
	 *
	 * @return string A String representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a String is not possible.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getString() {
		switch ($this->type) {
			case F3_PHPCR_PropertyType::DATE:
				if (is_a($this->value, 'DateTime')) {
					return date_format($this->value, DATE_ISO8601);
				} else {
					return date_format(new DateTime($this->value), DATE_ISO8601);
				}
				break;
			default:
				return (string)$this->value;
		}
	}

	/**
	 * Returns the value as string, alias for getString()
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getString();
	}

	/**
	 * Returns a long (integer) representation of this value.
	 *
	 * @return string A long representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a long is not possible.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getLong() {
		return (int)$this->value;
	}

	/**
	 * Returns a BigDecimal representation of this value (aliased to getDouble()).
	 *
	 * @return float A double representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion is not possible.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function getDecimal() {
		return $this->getDouble();
	}

	/**
	 * Returns a double (floating point) representation of this value.
	 *
	 * @return float A double representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a double is not possible.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDouble() {
		return (double)$this->value;
	}

	/**
	 * Returns a DateTime representation of this value.
	 *
	 * @return DateTime A DateTime representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a DateTime is not possible.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDate() {
		if (is_a($this->value, 'DateTime')) {
			return clone($this->value);
		}

		try {
			return new DateTime($this->value);
		} catch (Exception $e) {
			throw new F3_PHPCR_ValueFormatException('Conversion to a DateTime object is not possible. Cause: ' . $e->getMessage(), 1190034628);
		}
	}

	/**
	 * Returns a boolean representation of this value.
	 *
	 * @return string A boolean representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a boolean is not possible.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBoolean() {
		return (boolean)$this->value;
	}

	/**
	 * Returns the type of this Value. One of:
	 * * F3_PHPCR_PropertyType::STRING
	 * * F3_PHPCR_PropertyType::DATE
	 * * F3_PHPCR_PropertyType::BINARY
	 * * F3_PHPCR_PropertyType::DOUBLE
	 * * F3_PHPCR_PropertyType::DECIMAL
	 * * F3_PHPCR_PropertyType::LONG
	 * * F3_PHPCR_PropertyType::BOOLEAN
	 * * F3_PHPCR_PropertyType::NAME
	 * * F3_PHPCR_PropertyType::PATH
	 * * F3_PHPCR_PropertyType::REFERENCE
	 * * F3_PHPCR_PropertyType::WEAKREFERENCE
	 * * F3_PHPCR_PropertyType::URI
	 *
	 * The type returned is that which was set at property creation.
	 * @return integer The type of the value
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getType() {
		return $this->type;
	}

}

?>
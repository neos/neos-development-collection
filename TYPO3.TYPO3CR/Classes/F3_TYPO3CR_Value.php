<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

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
class Value implements \F3\PHPCR\ValueInterface {

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
	 * @param integer $type A type, see constants in \F3\PHPCR\PropertyType
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
	 * @return \F3\TYPO3CR\Binary A Binary representation of this value.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBinary() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217843676);
	}

	/**
	 * Returns a string representation of this value. For Value objects being
	 * of type DATE the string will conform to ISO8601 format.
	 *
	 * @return string A String representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a String is not possible.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function getString() {
		switch ($this->type) {
			case \F3\PHPCR\PropertyType::DATE:
				if (is_a($this->value, 'DateTime')) {
					return date_format($this->value, DATE_ISO8601);
				} else {
					return date_format(new \DateTime($this->value), DATE_ISO8601);
				}
			case \F3\PHPCR\PropertyType::BOOLEAN:
				return (string)(int)$this->value;
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
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a long is not possible.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getLong() {
		return (int)$this->value;
	}

	/**
	 * Returns a BigDecimal representation of this value (aliased to getDouble()).
	 *
	 * @return float A double representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion is not possible.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 */
	public function getDecimal() {
		return $this->getDouble();
	}

	/**
	 * Returns a double (floating point) representation of this value.
	 *
	 * @return float A double representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a double is not possible.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDouble() {
		return (double)$this->value;
	}

	/**
	 * Returns a \DateTime representation of this value.
	 *
	 * @return \DateTime A \DateTime representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a \DateTime is not possible.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDate() {
		if (is_a($this->value, 'DateTime')) {
			return clone($this->value);
		}

		try {
			return new \DateTime($this->value);
		} catch (\Exception $e) {
			throw new \F3\PHPCR\ValueFormatException('Conversion to a \DateTime object is not possible. Cause: ' . $e->getMessage(), 1190034628);
		}
	}

	/**
	 * Returns a boolean representation of this value.
	 *
	 * @return string A boolean representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a boolean is not possible.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBoolean() {
		return (boolean)$this->value;
	}

	/**
	 * Returns the type of this Value. One of:
	 * * \F3\PHPCR\PropertyType::STRING
	 * * \F3\PHPCR\PropertyType::DATE
	 * * \F3\PHPCR\PropertyType::BINARY
	 * * \F3\PHPCR\PropertyType::DOUBLE
	 * * \F3\PHPCR\PropertyType::DECIMAL
	 * * \F3\PHPCR\PropertyType::LONG
	 * * \F3\PHPCR\PropertyType::BOOLEAN
	 * * \F3\PHPCR\PropertyType::NAME
	 * * \F3\PHPCR\PropertyType::PATH
	 * * \F3\PHPCR\PropertyType::REFERENCE
	 * * \F3\PHPCR\PropertyType::WEAKREFERENCE
	 * * \F3\PHPCR\PropertyType::URI
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
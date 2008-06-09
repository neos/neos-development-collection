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
 * A ValueFactory, used to create Value objects.
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_ValueFactory implements F3_PHPCR_ValueFactoryInterface {

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * Constructs a ValueFactory
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Returns a F3_TYPO3CR_Binary object with a value consisting of the content of
	 * the specified resource handle.
	 * The passed resource handle is closed before this method returns either normally
	 * or because of an exception.
	 *
	 * @param resource $handle
	 * @return F3_TYPO3CR_Binary
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function createBinary($handle) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213025145);
	}

	/**
	 * Returns a Value object with the specified value. If $type is given,
	 * conversion from string is attempted before creating the Value object.
	 *
	 * If no type is given, the type is guessed intelligently.
	 * * if the given $value is a Node object, it's Identifier is fetched for the
	 *   Value object and the type of that object will be REFERENCE
	 * * if the given $value is a DateTime object, the Value type will be DATE.
	 * * if the given $value is a Binary object, the Value type will be BINARY
	 * If guessing fails the type will be UNDEFINED.
	 *
	 * @param mixed $value
	 * @param integer $type
	 * @return F3_PHPCR_ValueInterface
	 * @throws F3_PHPCR_ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 * @throws F3_PHPCR_RepositoryException if the specified Node is not referenceable, the current Session is no longer active, or another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createValue($value, $type = F3_PHPCR_PropertyType::UNDEFINED) {
			// try to do requested conversion, else guess the type
		if ($type !== F3_PHPCR_PropertyType::UNDEFINED) {
			return $this->createValueWithGivenType($value, $type);
		} else {
			return $this->createValueAndGuessType($value);
		}
	}

	/**
	 * Returns a Value object with the specified value. Conversion from string
	 * is attempted before creating the Value object.
	 *
	 * @param mixed $value
	 * @param integer $type
	 * @return F3_PHPCR_ValueInterface
	 * @throws F3_PHPCR_ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Make sure the given value is a valid Identifier for reference types
	 */
	protected function createValueWithGivenType($value, $type) {
		if (!is_string($value)) {
			throw new F3_PHPCR_ValueFormatException('Requesting type conversion is only allowed for string values.', 1203676334);
		}

		switch ($type) {
			case F3_PHPCR_PropertyType::REFERENCE:
			case F3_PHPCR_PropertyType::WEAKREFERENCE:
					// for REFERENCE make sure we really have a node with that Identifier
				break;
			case F3_PHPCR_PropertyType::DATE:
				try {
					$value = new DateTime($value);
				} catch (Exception $e) {
					throw new F3_PHPCR_ValueFormatException('The given value could not be converted to a DateTime object.', 1211372741);
				}
				break;
			case F3_PHPCR_PropertyType::BINARY:
					// we do not do anything here, getBinary on Value objects does the hard work
				break;
			case F3_PHPCR_PropertyType::DECIMAL:
			case F3_PHPCR_PropertyType::DOUBLE:
				$value = (float)$value;
				break;
			case F3_PHPCR_PropertyType::BOOLEAN:
				$value = (boolean)$value;
				break;
			case F3_PHPCR_PropertyType::LONG:
				$value = (int)$value;
				break;
			case F3_PHPCR_PropertyType::URI:
					// we cannot really use parse_url to check for a syntactically valid URI
					// as it emits an E_WARNING on failure and "correctly" parses about everything
					// so we just leave the value as it is
				break;
		}
		return $this->componentManager->getComponent('F3_PHPCR_ValueInterface', $value, $type);
	}

	/**
	 * Returns a Value object with the specified value.
	 *
	 * * if the given $value is a Node object, it's Identifier is fetched for the
	 *   Value object and the type of that object will be REFERENCE
	 * * if the given $value is a DateTime object, the Value type will be DATE.
	 * * if the given $value is a Binary object, the Value type will be BINARY
	 * If guessing fails the type will be UNDEFINED.
	 *
	 * @param mixed $value
	 * @return F3_PHPCR_ValueInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Check type guessing/conversion when we go for PHP6
	 * @todo Make sure conversion is checked for possibility
	 */
	public function createValueAndGuessType($value) {
		if (is_a($value, 'F3_PHPCR_NodeInterface')) {
			$value = $value->getIdentifier();
			$type = F3_PHPCR_PropertyType::REFERENCE;
		} elseif (is_a($value, 'DateTime')) {
			$type = F3_PHPCR_PropertyType::DATE;
		} elseif (is_a($value, 'F3_PHPCR_BinaryInterface')) {
			$type = F3_PHPCR_PropertyType::BINARY;
		} elseif (F3_PHP6_Functions::is_binary($value)) {
			$type = F3_PHPCR_PropertyType::BINARY;
		} elseif (is_double($value)) {
			$type = F3_PHPCR_PropertyType::DOUBLE;
		} elseif (is_bool($value)) {
			$type = F3_PHPCR_PropertyType::BOOLEAN;
		} elseif (is_long($value)) {
			$type = F3_PHPCR_PropertyType::LONG;
		} elseif (is_string($value)) {
			$type = F3_PHPCR_PropertyType::STRING;
		}

		return $this->componentManager->getComponent('F3_TYPO3CR_Value', $value, $type);
	}
}

?>
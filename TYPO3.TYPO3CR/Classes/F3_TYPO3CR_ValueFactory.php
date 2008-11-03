<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR;

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
class ValueFactory implements F3::PHPCR::ValueFactoryInterface {

	/**
	 * @var F3::FLOW3::Component::Manager
	 */
	protected $componentFactory;

	/**
	 * Constructs a ValueFactory
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3::FLOW3::Component::FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Returns a F3::TYPO3CR::Binary object with a value consisting of the content of
	 * the specified resource handle.
	 * The passed resource handle is closed before this method returns either normally
	 * or because of an exception.
	 *
	 * @param resource $handle
	 * @return F3::TYPO3CR::Binary
	 * @throws F3::PHPCR::RepositoryException if an error occurs.
	 */
	public function createBinary($handle) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213025145);
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
	 * @return F3::PHPCR::ValueInterface
	 * @throws F3::PHPCR::ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 * @throws F3::PHPCR::RepositoryException if the specified Node is not referenceable, the current Session is no longer active, or another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createValue($value, $type = F3::PHPCR::PropertyType::UNDEFINED) {
			// try to do requested conversion, else guess the type
		if ($type !== F3::PHPCR::PropertyType::UNDEFINED) {
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
	 * @return F3::PHPCR::ValueInterface
	 * @throws F3::PHPCR::ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Make sure the given value is a valid Identifier for reference types
	 */
	protected function createValueWithGivenType($value, $type) {
		switch ($type) {
			case F3::PHPCR::PropertyType::REFERENCE:
			case F3::PHPCR::PropertyType::WEAKREFERENCE:
					// for REFERENCE make sure we really have a node with that Identifier
				break;
			case F3::PHPCR::PropertyType::DATE:
				try {
					$value = new DateTime($value);
				} catch (::Exception $e) {
					throw new F3::PHPCR::ValueFormatException('The given value could not be converted to a DateTime object.', 1211372741);
				}
				break;
			case F3::PHPCR::PropertyType::BINARY:
					// we do not do anything here, getBinary on Value objects does the hard work
				break;
			case F3::PHPCR::PropertyType::DECIMAL:
			case F3::PHPCR::PropertyType::DOUBLE:
				$value = (float)$value;
				break;
			case F3::PHPCR::PropertyType::BOOLEAN:
				$value = (boolean)$value;
				break;
			case F3::PHPCR::PropertyType::LONG:
				$value = (int)$value;
				break;
			case F3::PHPCR::PropertyType::URI:
					// we cannot really use parse_url to check for a syntactically valid URI
					// as it emits an E_WARNING on failure and "correctly" parses about everything
					// so we just leave the value as it is
				break;
		}
		return $this->componentFactory->create('F3::PHPCR::ValueInterface', $value, $type);
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
	 * @return F3::PHPCR::ValueInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Check type guessing/conversion when we go for PHP6
	 */
	protected function createValueAndGuessType($value) {
		$type = self::guessType($value);
		if ($type === F3::PHPCR::PropertyType::REFERENCE) {
			$value = $value->getIdentifier();
		}

		return $this->componentFactory->create('F3::PHPCR::ValueInterface', $value, $type);
	}

	/**
	 * Guesses the type for the given value
	 *
	 * @param mixed $value
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public static function guessType($value) {
		$type = F3::PHPCR::PropertyType::UNDEFINED;

		if ($value instanceof F3::PHPCR::NodeInterface) {
			$type = F3::PHPCR::PropertyType::REFERENCE;
		} elseif ($value instanceof DateTime) {
			$type = F3::PHPCR::PropertyType::DATE;
		} elseif ($value instanceof F3::PHPCR::BinaryInterface) {
			$type = F3::PHPCR::PropertyType::BINARY;
		} elseif (F3::PHP6::Functions::is_binary($value)) {
			$type = F3::PHPCR::PropertyType::BINARY;
		} elseif (is_double($value)) {
			$type = F3::PHPCR::PropertyType::DOUBLE;
		} elseif (is_bool($value)) {
			$type = F3::PHPCR::PropertyType::BOOLEAN;
		} elseif (is_long($value)) {
			$type = F3::PHPCR::PropertyType::LONG;
		} elseif (is_string($value)) {
			$type = F3::PHPCR::PropertyType::STRING;
		}

		return $type;
	}
}

?>
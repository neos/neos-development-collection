<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

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
 * A ValueFactory, used to create Value objects.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class ValueFactory implements \F3\PHPCR\ValueFactoryInterface {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\PHPCR\SessionInterface
	 */
	protected $session;

	/**
	 * Constructs a ValueFactory
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\ObjectManagerInterface $objectManager, \F3\PHPCR\SessionInterface $session) {
		$this->objectManager = $objectManager;
		$this->session = $session;
	}

	/**
	 * Returns a \F3\TYPO3CR\Binary object with a value consisting of the content of
	 * the specified resource handle.
	 * The passed resource handle is closed before this method returns either normally
	 * or because of an exception.
	 *
	 * @param resource $handle
	 * @return \F3\TYPO3CR\Binary
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @api
	 */
	public function createBinary($handle) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213025145);
	}

	/**
	 * Returns a Value object with the specified value. If $type is given,
	 * conversion is attempted before creating the Value object.
	 *
	 * If no type is given, the value is stored as is, i.e. it's type is
	 * preserved. Exceptions are:
	 * * if the given $value is a Node object, it's Identifier is fetched for the
	 *   Value object and the type of that object will be REFERENCE
	 * * if the given $value is a Node object, it's Identifier is fetched for the
	 *   Value object and the type of that object will be WEAKREFERENCE if $weak
	 *   is set to TRUE
	 * * if the given $Value is a \DateTime object, the Value type will be DATE.
	 *
	 * @param mixed $value The value to use when creating the Value object
	 * @param integer $type Type request for the Value object
	 * @param boolean $weak When a Node is given as $value this can be given as TRUE to create a WEAKREFERENCE, $type is ignored in that case!
	 * @return \F3\PHPCR\ValueInterface
	 * @throws \F3\PHPCR\ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 * @throws \F3\PHPCR\RepositoryException if the specified Node is not referenceable, the current Session is no longer active, or another error occurs.
	 * @throws \IllegalArgumentException if the specified DateTime value cannot be expressed in the ISO 8601-based format defined in the JCR 2.0 specification and the implementation does not support dates incompatible with that format.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function createValue($value, $type = \F3\PHPCR\PropertyType::UNDEFINED, $weak = FALSE) {
		if ($value instanceof \F3\PHPCR\NodeInterface) {
			$value = $value->getIdentifier();
			$type = ($weak === TRUE ? \F3\PHPCR\PropertyType::WEAKREFERENCE : \F3\PHPCR\PropertyType::REFERENCE);
		}

		if ($type === \F3\PHPCR\PropertyType::UNDEFINED) {
			return $this->objectManager->create('F3\PHPCR\ValueInterface', $value, self::guessType($value));
		} else {
			return $this->createValueWithGivenType($value, $type);
		}
	}

	/**
	 * Returns a Value object with the specified value. Conversion from string
	 * is attempted before creating the Value object.
	 *
	 * @param mixed $value
	 * @param integer $type
	 * @return \F3\PHPCR\ValueInterface
	 * @throws \F3\PHPCR\ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createValueWithGivenType($value, $type) {
		switch ($type) {
			case \F3\PHPCR\PropertyType::REFERENCE:
				if (preg_match(\F3\TYPO3CR\Node::PATTERN_MATCH_REFERENCE, $value) === 0 || !$this->session->hasIdentifier($value)) {
					throw new \F3\PHPCR\ValueFormatException('REFERENCE properties must point to a valid, existing identifier.', 1231765408);
				}
				break;
			case \F3\PHPCR\PropertyType::WEAKREFERENCE:
				if (preg_match(\F3\TYPO3CR\Node::PATTERN_MATCH_WEAKREFERENCE, $value) === 0) {
					throw new \F3\PHPCR\ValueFormatException('WEAKREFERENCE properties must point to a syntactically valid identifier.', 1231765585);
				}
				break;
			case \F3\PHPCR\PropertyType::DATE:
				try {
					$value = new \DateTime($value);
				} catch (\Exception $e) {
					throw new \F3\PHPCR\ValueFormatException('The given value could not be converted to a \DateTime object.', 1211372741);
				}
				break;
			case \F3\PHPCR\PropertyType::BINARY:
					// we do not do anything here, getBinary on Value objects does the hard work
				break;
			case \F3\PHPCR\PropertyType::DECIMAL:
			case \F3\PHPCR\PropertyType::DOUBLE:
				$value = (float)$value;
				break;
			case \F3\PHPCR\PropertyType::BOOLEAN:
				$value = (boolean)$value;
				break;
			case \F3\PHPCR\PropertyType::LONG:
				$value = (int)$value;
				break;
			case \F3\PHPCR\PropertyType::URI:
					// we cannot really use parse_url to check for a syntactically valid URI
					// as it emits an E_WARNING on failure and "correctly" parses about everything
					// so we just leave the value as it is
				break;
		}
		return $this->objectManager->create('F3\PHPCR\ValueInterface', $value, $type);
	}

	/**
	 * Guesses the type for the given value
	 *
	 * @param mixed $value
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Check type guessing/conversion when we go for PHP6
	 */
	static public function guessType($value) {
		$type = \F3\PHPCR\PropertyType::UNDEFINED;

		if ($value instanceof \DateTime) {
			$type = \F3\PHPCR\PropertyType::DATE;
		} elseif ($value instanceof \F3\PHPCR\BinaryInterface) {
			$type = \F3\PHPCR\PropertyType::BINARY;
		} elseif (is_double($value)) {
			$type = \F3\PHPCR\PropertyType::DOUBLE;
		} elseif (is_bool($value)) {
			$type = \F3\PHPCR\PropertyType::BOOLEAN;
		} elseif (is_long($value)) {
			$type = \F3\PHPCR\PropertyType::LONG;
		} elseif (is_string($value)) {
			$type = \F3\PHPCR\PropertyType::STRING;
		}

		return $type;
	}
}

?>
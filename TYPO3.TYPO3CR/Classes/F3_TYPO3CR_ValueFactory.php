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
class F3_TYPO3CR_ValueFactory implements F3_phpCR_ValueFactoryInterface {

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * Constructs a ValueFactory
	 *
	 * @param F3_FLOW3_Component_Manager $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Returns a Value object with the specified value. If $type is given,
	 * conversion from string is attempted before creating the Value object.
	 *
	 * If the given $value is a resource, it is assumed to be a file handle
	 * and the file's content will be fetched for the Value object. The
	 * file pointer will be closed before returning the Value object. The
	 * Value object will be of type BINARY.
	 *
	 * If no type is given, the type is guessed intelligently.
	 * * if the given $value is a Node object, it's UUID is fetched for the
	 *   Value object and the type of that object will be REFERENCE
	 * * if the given $Value is a DateTime object, the Value type will be DATE.
	 * If guessing fails the type will be UNDEFINED.
	 *
	 * @param mixed $value
	 * @param integer $type
	 * @return F3_phpCR_ValueInterface
	 * @throws F3_phpCR_ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 * @throws F3_phpCR_RepositoryException if the specified Node is not referenceable, the current Session is no longer active, or another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Check type guessing/conversion when we go for PHP6
	 * @todo Make sure conversion is checked for possibility
	 */
	public function createValue($value, $type = F3_phpCR_PropertyType::UNDEFINED) {
			// we handle resources "the PHP way" by just fetching their contents
		if (is_resource($value)) {
			$data = '';
			while (!feof($value)) {
				$data .= fread($value, 8192);
			}
			fclose($value);
			$value &= $data;
			$type = F3_phpCR_PropertyType::BINARY;
		} else {
				// try to do requested conversion, else guess the type
			if($type !== F3_phpCR_PropertyType::UNDEFINED) {
				if(!is_string($value)) {
					throw new F3_phpCR_ValueFormatException('Type conversion in Valuefactory only allowed for string values.', 1203676334);
				}

				switch($type) {
					case F3_phpCR_PropertyType::REFERENCE:
					case F3_phpCR_PropertyType::WEAKREFERENCE:
							// for REFERENCE make sure we really have a node with that UUID
						break;
					case F3_phpCR_PropertyType::DATE:
						$value = new DateTime($value);
						break;
					case F3_phpCR_PropertyType::BINARY:
							// make sure it is binary for PHP6
						break;
					case F3_phpCR_PropertyType::DOUBLE:
						$value = (float)$value;
						break;
					case F3_phpCR_PropertyType::BOOLEAN:
						$value = (boolean)$value;
						break;
					case F3_phpCR_PropertyType::LONG:
						$value = (int)$value;
						break;
					case F3_phpCR_PropertyType::URI:
							// we cannot really use parse_url to check for a syntactically valid URI
							// as it emits an E_WARNING on failure and "correctly" parses about everything
						break;
				}
			} else {
				if (is_a($value, 'F3_phpCR_NodeInterface')) {
					$value = $value->getUUID();
					$type = F3_phpCR_PropertyType::REFERENCE;
				} elseif (is_a($value, 'DateTime')) {
					$type = F3_phpCR_PropertyType::DATE;
				} elseif (F3_PHP6_Functions::is_binary($value)) {
					$type = F3_phpCR_PropertyType::BINARY;
				} elseif (is_double($value)) {
					$type = F3_phpCR_PropertyType::DOUBLE;
				} elseif (is_bool($value)) {
					$type = F3_phpCR_PropertyType::BOOLEAN;
				} elseif (is_long($value)) {
					$type = F3_phpCR_PropertyType::LONG;
				} elseif (is_string($value)) {
					$type = F3_phpCR_PropertyType::STRING;
				}
			}
		}

		return $this->componentManager->getComponent('F3_phpCR_ValueInterface', $value, $type);
	}
}

?>

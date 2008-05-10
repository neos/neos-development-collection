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
 * A Property
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Property extends F3_TYPO3CR_AbstractItem implements F3_phpCR_PropertyInterface {

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var F3_phpCR_Node
	 */
	protected $parentNode;

	/**
	 * Constructs a Property
	 *
	 * @param string $name The name of the property
	 * @param string $value The raw value of the property
	 * @param F3_TYPO3CR_NodeInterface $parentNode
	 * @param boolean $isMultiValued Whether this property is multivalued
	 * @param F3_TYPO3CR_SessionInterface $session
	 * @param F3_TYPO3CR_StorageAccessInterface $storageAccess
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($name, $value, F3_phpCR_NodeInterface $parentNode, $isMultiValued, F3_phpCR_SessionInterface $session, F3_TYPO3CR_StorageAccessInterface $storageAccess, F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->session = $session;
		$this->storageAccess = $storageAccess;
		$this->componentManager = $componentManager;

		if ($value === NULL) throw new F3_TYPO3CR_RepositoryException('Constructing a Property with a NULL value is not allowed', 1203336959);

		$this->name = $name;
		$valueFactory = $this->componentManager->getComponent('F3_phpCR_ValueFactoryInterface');
		if ($isMultiValued) {
			foreach ($value as $singleValue) {
				$this->value[] = $valueFactory->createValue($singleValue);
			}
		} else {
			$this->value = $valueFactory->createValue($value);
		}
		$this->parentNode = $parentNode;
	}

	/**
	 * Returns the value of this property as a Value object.
	 *
	 * The object returned is a copy of the stored value and is immutable.
	 *
	 * @return F3_TYPO3CR_Value
	 * @throws F3_phpCR_ValueFormatException
	 * @throws F3_phpCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getValue() {
		if (is_array($this->value)) throw new F3_phpCR_ValueFormatException('getValue() cannot be called on multi-valued properties.', 1181084521);

		return clone $this->value;
	}

	/**
	 * Returns an array of all the values of this property. This method is
	 * used to access multi-value properties.
	 * If the property is single-valued, this method throws a
	 * ValueFormatException.
	 * The array returned is a copy of the stored values, so changes to it
	 * are not reflected in internal storage.
	 *
	 * @return array Array of F3_TYPO3CR_Value
	 * @throws F3_phpCR_ValueFormatException
	 * @throws F3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getValues() {
		if (!is_array($this->value)) throw new F3_phpCR_ValueFormatException('getValues() cannot be used to access single-valued properties.', 1189512545);

		$values = array();
		foreach ($this->value as $singleValue) {
			$values[] = clone $singleValue;
		}

		return $values;
	}

	/**
	 * Returns a String representation of the value of this property.
	 *
	 * @return string tring representation of the value of this property
	 * @throws F3_phpCR_ValueFormatException
	 * @throws F3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getString() {
		if (is_array($this->value)) throw new F3_phpCR_ValueFormatException('getString() cannot be called on multi-valued properties.', 1203338111);

		return $this->value->getString();
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
	 * Returns a Long (double) representation of the value of this property.
	 *
	 * @return long Long representation of the value of this property
	 * @throws F3_phpCR_ValueFormatException
	 * @throws F3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getLong() {
		if (is_array($this->value)) throw new F3_phpCR_ValueFormatException('getLong() cannot be called on multi-valued properties.', 1203338188);

		return $this->value->getLong();
	}

	/**
	 * Returns a double representation of the value of this property.
	 *
	 * @return double Double representation of the value of this property
	 * @throws F3_phpCR_ValueFormatException
	 * @throws F3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDouble() {
		if (is_array($this->value)) throw new F3_phpCR_ValueFormatException('getDouble() cannot be called on multi-valued properties.', 1203338188);

		return $this->value->getDouble();
	}

	/**
	 * Returns a boolean representation of the value of this property.
	 *
	 * @return boolean Boolean representation of the value of this property
	 * @throws F3_phpCR_ValueFormatException
	 * @throws F3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBoolean() {
		if (is_array($this->value)) throw new F3_phpCR_ValueFormatException('getBoolean() cannot be called on multi-valued properties.', 1203338188);

		return $this->value->getBoolean();
	}

	/**
	 * Returns a DateTime representation of the value of this property. A
	 * shortcut for Property.getValue().getDate()
	 * The object returned is a copy of the stored value, so changes to it
	 * are not reflected in internal storage.
	 *
	 * @return DateTime DateTime representation of the value of this property
	 * @throws F3_phpCR_ValueFormatException if the property is multi-valued or cannot be converted to a DateTime object
	 * @throws F3_phpCR_RepositoryException on any other error
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDate() {
		if (is_array($this->value)) throw new F3_phpCR_ValueFormatException('getDate() cannot be called on multi-valued properties.', 1203338327);

		return $this->value->getDate();
	}

	/**
	 * Returns a stream representation of the value of this
	 * property. A shortcut for Property.getValue().getStream().
	 * It is the responsibility of the caller to close the returned
	 * InputStream.
	 *
	 * @return unknown Stream representation of the value of this property
	 * @throws F3_phpCR_ValueFormatException if the property is multi-valued
	 * @throws F3_phpCR_RepositoryException on any other error
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getStream() {
		if (is_array($this->value)) throw new F3_phpCR_ValueFormatException('getStream() cannot be called on multi-valued properties.', 1203338571);

		return $this->value->getStream();
	}

	/**
	 * Returns FALSE if this Item is a Node; returns FALSE if this Item is a
	 * Property.
	 *
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function isNode() {
		return FALSE;
	}

	/**
	 * Get path of property
	 *
	 * @return string Path to the property
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getPath() {
		$buffer = $this->getParent()->getPath();
		if (F3_PHP6_Functions::strlen($buffer) > 1) {
			$buffer .= '/';
		}
		$buffer .= $this->getName();
		return $buffer;
	}

	/**
	 * Return parent node
	 *
	 * @return F3_phpCR_Node The Parent Node
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getParent() {
		return $this->parentNode;
	}

	/**
	 * Remove property
	 *
	 * @todo
	 */
	public function remove() {
		$this->setRemoved(TRUE);
	}

	/**
	 * Returns an array with data ready to be written into the DB
	 *
	 * @return array
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function save() {
		$value = $this->value;
		if (is_array($value)) {
			$value = serialize($value);
		}

		if ($this->isRemoved()) {
			$this->storageAccess->removeProperty($this->getParent()->getUUID(), $this->getName());
		} elseif ($this->isModified()) {
			$this->storageAccess->updateProperty($this->getParent()->getUUID(), $this->getName(), $value, is_array($this->value));
		} elseif ($this->isNew()) {
			$this->storageAccess->addProperty($this->getParent()->getUUID(), $this->getName(), $value, is_array($this->value));
		}

		$this->setModified(FALSE);
		$this->setNew(FALSE);
	}

	/**
	 * Set a value.
	 *
	 * @param mixed $value The value to set
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValue($value) {
		if ($value === NULL) {
			$this->value = NULL;
			$this->setRemoved(TRUE);
		} else {
			$valueFactory = $this->componentManager->getComponent('F3_phpCR_ValueFactoryInterface');
			if (is_array($value)) {
				foreach ($value as $singleValue) {
					$this->value[] = $valueFactory->createValue($singleValue);
				}
			} else {
				$this->value = $valueFactory->createValue($value);
			}
			$this->setModified(TRUE);
		}
	}
}

?>

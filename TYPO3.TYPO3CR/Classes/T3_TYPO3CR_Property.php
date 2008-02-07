<?php
declare(encoding = 'utf-8');

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
 * A Property
 *
 * @package		TYPO3CR
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3CR_Property extends T3_TYPO3CR_Item implements T3_phpCR_PropertyInterface {

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var T3_phpCR_Node
	 */
	protected $parentNode;

	/**
	 * @var T3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * Constructs a Property
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($name, $value, $parentNode, $isMultiValued, T3_FLOW3_Component_ManagerInterface $componentManager, T3_TYPO3CR_StorageAccessInterface $storageAccess, T3_phpCR_SessionInterface $session) {
		parent::__construct($storageAccess, $session);

		$this->name = $name;
		if ($isMultiValued) {
			$this->value = unserialize($value);
		} else {
			$this->value = $value;
		}
		$this->parentNode = $parentNode;
		$this->componentManager = $componentManager;
	}

	/**
	 * Returns the value of this property as a Value object.
	 * 
	 * The object returned is a copy of the stored value and is immutable.
	 *
	 * @return T3_TYPO3CR_Value
	 * @throws T3_phpCR_ValueFormatException
	 * @throws T3_phpCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Make sure the returned Value object is made immutable!
	 */
	public function getValue() {
		if(is_array($this->value)) throw new T3_phpCR_ValueFormatException('getValue() cannot be called on multi-valued properties.', 1181084521);
		
		return $this->componentManager->getComponent('T3_phpCR_ValueInterface', $this->value);
	}

	/**
	 * Returns an array of all the values of this property. This method is 
	 * used to access multi-value properties.
	 * If the property is single-valued, this method throws a
	 * ValueFormatException.
	 * The array returned is a copy of the stored values, so changes to it
	 * are not reflected in internal storage.
	 * 
	 * @return array Array of T3_TYPO3CR_Value
	 * @throws T3_phpCR_ValueFormatException
	 * @throws T3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @todo Make sure the returned Value object is made immutable!
	 */
	public function getValues() {
		if (!is_array($this->value)) throw new T3_phpCR_ValueFormatException('getValues() cannot be used to access single-valued properties.', 1189512545);
		$values = array();
		if (count($this->value)) {
			foreach ($this->value as $singleValue) {
				$values[] = $this->componentManager->getComponent('T3_phpCR_ValueInterface', $singleValue);
			}
		}
		return $values;
	}

	/**
	 * Returns a String representation of the value of this property.
	 * 
	 * @return string tring representation of the value of this property
	 * @throws T3_phpCR_ValueFormatException
	 * @throws T3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getString() {
		return $this->getValue()->getString();
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
	 * @throws T3_phpCR_ValueFormatException
	 * @throws T3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getLong() {
		return $this->getValue()->getLong();
	}

	/**
	 * Returns a double representation of the value of this property.
	 * 
	 * @return double Double representation of the value of this property
	 * @throws T3_phpCR_ValueFormatException
	 * @throws T3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getDouble() {
		return $this->getValue()->getDouble();
	}

	/**
	 * Returns a boolean representation of the value of this property.
	 * 
	 * @return boolean Boolean representation of the value of this property
	 * @throws T3_phpCR_ValueFormatException
	 * @throws T3_phpCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getBoolean() {
		return $this->getValue()->getBoolean();
	}

	/**
	 * Returns true if this Item is a Node; returns false if this Item is a
	 * Property.
	 *
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function isNode() {
		return false;
	}

	/**
	 * Get path of property
	 * 
	 * @return string Path to the property
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getPath() {
		$buffer = $this->getParent()->getPath();
		if (T3_PHP6_Functions::strlen($buffer) > 1) {
			$buffer .= '/';
		}
		$buffer .= $this->getName();
		return $buffer;
	}

	/**
	 * Return parent node
	 * 
	 * @return T3_phpCR_Node The Parent Node
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
	 * Set a value. Only called by T3_TYPO3CR_Node.
	 * 
	 * @param mixed $value The value to set
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function setValue($value) {
		$this->value = $value;
		if ($value === null) {
			$this->setRemoved(TRUE);
		} else {
			$this->setModified(TRUE);
		}
	}
}

?>

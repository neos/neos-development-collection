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
 * @scope prototype
 */
class F3_TYPO3CR_Property extends F3_TYPO3CR_AbstractItem implements F3_PHPCR_PropertyInterface {

	/**
	 * @var F3_PHPCR_ValueInterface
	 */
	protected $value;

	/**
	 * Constructs a Property
	 *
	 * @param string $name The name of the property
	 * @param string $value The raw value of the property
	 * @param F3_PHPCR_NodeInterface $parentNode
	 * @param boolean $isMultiValued Whether this property is multivalued
	 * @param F3_PHPCR_NodeInterface $session
	 * @param F3_TYPO3CR_Storage_BackendInterface $storageAccess
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($name, $value, F3_PHPCR_NodeInterface $parentNode, $isMultiValued, F3_PHPCR_SessionInterface $session, F3_TYPO3CR_Storage_BackendInterface $storageAccess, F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->session = $session;
		$this->storageAccess = $storageAccess;
		$this->componentManager = $componentManager;

		if ($value === NULL) throw new F3_TYPO3CR_RepositoryException('Constructing a Property with a NULL value is not allowed', 1203336959);

		$this->name = $name;
		$valueFactory = $this->componentManager->getComponent('F3_PHPCR_ValueFactoryInterface');
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
	 * Sets the value of this property to value. If this property's property
	 * type is not constrained by the node type of its parent node, then the
	 * property type is changed to that of the supplied value. If the property
	 * type is constrained, then a best-effort conversion is attempted. If
	 * conversion fails, a ValueFormatException is thrown immediately (not on
	 * save). The change will be persisted (if valid) on save
	 *
	 * For Node objects as value:
	 * Sets this REFERENCE property to refer to the specified node. If this
	 * property is not of type REFERENCE or the specified node is not
	 * referenceable (i.e., is not of mixin node type mix:referenceable and
	 * therefore does not have a UUID) then a ValueFormatException is thrown.
	 *
	 * If value is an array:
	 * If this property is not multi-valued then a ValueFormatException is
	 * thrown immediately.
	 *
	 * @param mixed $value The value to set
	 * @return void
	 * @throws F3_PHPCR_ValueFormatException if the type or format of the specified value is incompatible with the type of this property.
	 * @throws F3_PHPCR_Version_VersionException if this property belongs to a node that is versionable and checked-in or is non-versionable but whose nearest versionable ancestor is checked-in and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the setting of the value and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_ConstraintViolationException if the change would violate a node-type or other constraint and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValue($value) {
		if ($value === NULL) {
			$this->value = NULL;
			$this->setRemoved(TRUE);
		} else {
			$valueFactory = $this->componentManager->getComponent('F3_PHPCR_ValueFactoryInterface');
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

	/**
	 * Returns the value of this property as a Value object.
	 *
	 * The object returned is a copy of the stored value and is immutable.
	 *
	 * @return F3_PHPCR_ValueInterface the value
	 * @throws F3_PHPCR_ValueFormatException if the property is multi-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getValue() {
		if (is_array($this->value)) throw new F3_PHPCR_ValueFormatException('getValue() cannot be called on multi-valued properties.', 1181084521);

		return clone $this->value;
	}

	/**
	 * Returns an array of all the values of this property. Used to access
	 * multi-value properties. If the property is single-valued, this method
	 * throws a ValueFormatException. The array returned is a copy of the
	 * stored values, so changes to it are not reflected in internal storage.
	 *
	 * @return array of F3_PHPCR_ValueInterface
	 * @throws F3_PHPCR_ValueFormatException if the property is single-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getValues() {
		if (!is_array($this->value)) throw new F3_PHPCR_ValueFormatException('getValues() cannot be used to access single-valued properties.', 1189512545);

		$values = array();
		foreach ($this->value as $singleValue) {
			$values[] = clone $singleValue;
		}

		return $values;
	}

	/**
	 * Returns a String representation of the value of this property. A
	 * shortcut for Property.getValue().getString(). See Value.
	 *
	 * @return string A string representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a String is not possible or if the property is multi-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getString() {
		if (is_array($this->value)) throw new F3_PHPCR_ValueFormatException('getString() cannot be called on multi-valued properties.', 1203338111);

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
	 * Returns a Binary representation of the value of this property. A
	 * shortcut for Property.getValue().getBinary(). See Value.
	 *
	 * @return F3_PHPCR_BinaryInterface A Binary representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if the property is multi-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 */
	public function getBinary() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594477);
	}

	/**
	 * Returns an integer representation of the value of this property. A shortcut
	 * for Property.getValue().getLong(). See Value.
	 *
	 * @return integer An integer representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a long is not possible or if the property is multi-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getLong() {
		if (is_array($this->value)) throw new F3_PHPCR_ValueFormatException('getLong() cannot be called on multi-valued properties.', 1203338188);

		return $this->value->getLong();
	}

	/**
	 * Returns a double representation of the value of this property. A
	 * shortcut for Property.getValue().getDouble(). See Value.
	 *
	 * @return float A float representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a double is not possible or if the property is multi-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDouble() {
		if (is_array($this->value)) throw new F3_PHPCR_ValueFormatException('getDouble() cannot be called on multi-valued properties.', 1203338189);

		return $this->value->getDouble();
	}

	/**
	 * Returns a BigDecimal representation of the value of this property. A
	 * shortcut for Property.getValue().getDecimal(). See Value.
	 *
	 * @return float A float representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a BigDecimal is not possible or if the property is multi-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 */
	public function getDecimal() {
		if (is_array($this->value)) throw new F3_PHPCR_ValueFormatException('getDecimal() cannot be called on multi-valued properties.', 1212594888);

		return $this->value->getDecimal();
	}

	/**
	 * Returns a DateTime representation of the value of this property. A
	 * shortcut for Property.getValue().getDate(). See Value.
	 * The object returned is a copy of the stored value, so changes to it
	 * are not reflected in internal storage.
	 *
	 * @return DateTime A date representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a string is not possible or if the property is multi-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDate() {
		if (is_array($this->value)) throw new F3_PHPCR_ValueFormatException('getDate() cannot be called on multi-valued properties.', 1203338327);

		return $this->value->getDate();
	}

	/**
	 * Returns a boolean representation of the value of this property. A
	 * shortcut for Property.getValue().getBoolean(). See Value.
	 *
	 * @return boolean A boolean representation of the value of this property.
	 * @throws F3_PHPCR_ValueFormatException if conversion to a boolean is not possible or if the property is multi-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBoolean() {
		if (is_array($this->value)) throw new F3_PHPCR_ValueFormatException('getBoolean() cannot be called on multi-valued properties.', 1203338188);

		return $this->value->getBoolean();
	}

	/**
	 * If this property is of type REFERENCE, WEAKREFERENCE or PATH (or
	 * convertible to one of these types) this method returns the Node to
	 * which this property refers.
	 * If this property is of type PATH and it contains a relative path, it is
	 * interpreted relative to the parent node of this property. For example "."
	 * refers to the parent node itself, ".." to the parent of the parent node
	 * and "foo" to a sibling node of this property.
	 *
	 * @return F3_PHPCR_NodeInterface the referenced Node
	 * @throws F3_PHPCR_ValueFormatException if this property cannot be converted to a referring type (REFERENCE, WEAKREFERENCE or PATH), if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
	 * @throws F3_PHPCR_ItemNotFoundException If this property is of type PATH and no node accessible by the current Session exists in this workspace at the specified path.
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 */
	public function getNode() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594798);
	}

	/**
	 * If this property is of type PATH (or convertible to this type) this
	 * method returns the Property to which this property refers.
	 * If this property contains a relative path, it is interpreted relative
	 * to the parent node of this property. For example "." refers to the
	 * parent node itself, ".." to the parent of the parent node and "foo" to a
	 * sibling property of this property or this property itself.
	 *
	 * @return F3_PHPCR_PropertyInterface the referenced property
	 * @throws F3_PHPCR_ValueFormatException if this property cannot be converted to a PATH, if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
	 * @throws F3_PHPCR_ItemNotFoundException If this property is of type PATH and no property accessible by the current Session exists in this workspace at the specified path.
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 */
	public function getProperty() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594797);
	}

	/**
	 * Returns the length of the value of this property.
	 * Returns the length in bytes if the value is a BINARY, otherwise it
	 * returns the number of characters needed to display the value in its
	 * string form.
	 *
	 * Returns -1 if the implementation cannot determine the length.
	 *
	 * @return integer an integer.
	 * @throws F3_PHPCR_ValueFormatException if this property is multi-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function getLength() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594796);
	}

	/**
	 * Returns an array holding the lengths of the values of this (multi-value)
	 * property in bytes if the values are PropertyType.BINARY, otherwise it
	 * returns the number of characters needed to display the value in its
	 * string form. The order of the length values corresponds to the order of
	 * the values in the property.
	 * Returns a -1 in the appropriate position if the implementation cannot
	 * determine the length of a value.
	 *
	 * @return array an array of lengths
	 * @throws F3_PHPCR_ValueFormatException if this property is single-valued.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function getLengths() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594795);
	}

	/**
	 * Returns the property definition that applies to this property. In some
	 * cases there may appear to be more than one definition that could apply
	 * to this node. However, it is assumed that upon creation or change of
	 * this property, a single particular definition is chosen by the
	 * implementation. It is that definition that this method returns. How this
	 * governing definition is selected upon property creation or change from
	 * among others which may have been applicable is an implementation issue
	 * and is not covered by this specification.
	 *
	 * @return F3_PHPCR_NodeType_PropertyDefinitionInterface a PropertyDefinition object.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function getDefinition() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594794);
	}

	/**
	 * Returns the type of this Property. One of:
	 * * PropertyType.STRING
	 * * PropertyType.BINARY
	 * * PropertyType.DATE
	 * * PropertyType.DOUBLE
	 * * PropertyType.LONG
	 * * PropertyType.BOOLEAN
	 * * PropertyType.NAME
	 * * PropertyType.PATH
	 * * PropertyType.REFERENCE
	 * * PropertyType.WEAKREFERENCE
	 * * PropertyType.URI
	 *
	 * The type returned is that which was set at property creation. Note that
	 * for some property p, the type returned by p.getType() will differ from
	 * the type returned by p.getDefinition.getRequiredType() only in the case
	 * where the latter returns UNDEFINED. The type of a property instance is
	 * never UNDEFINED (it must always have some actual type).
	 *
	 * @return integer an int
	 * @throws F3_PHPCR_RepositoryException if an error occurs
	 */
	public function getType() {
		return $this->value->getType();
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
	 * @return F3_PHPCR_NodeInterface The Parent Node
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
			$this->storageAccess->removeProperty($this->getParent()->getIdentifier(), $this->getName());
		} elseif ($this->isModified()) {
			$this->storageAccess->updateProperty($this->getParent()->getIdentifier(), $this->getName(), $value, is_array($this->value));
		} elseif ($this->isNew()) {
			$this->storageAccess->addProperty($this->getParent()->getIdentifier(), $this->getName(), $value, is_array($this->value));
		}

		$this->setModified(FALSE);
		$this->setNew(FALSE);
	}

	/**
	 * If keepChanges is false, this method discards all pending changes
	 * currently recorded in this Session that apply to this Item or any
	 * of its descendants (that is, the subtree rooted at this Item) and
	 * returns all items to reflect the current saved state. Outside a
	 * transaction this state is simple the current state of persistent
	 * storage. Within a transaction, this state will reflect persistent
	 * storage as modified by changes that have been saved but not yet
	 * committed.
	 * If keepChanges is true then pending change are not discarded but
	 * items that do not have changes pending have their state refreshed
	 * to reflect the current saved state, thus revealing changes made by
	 * other sessions.
	 *
	 * @param boolean $keepChanges a boolean
	 * @return void
	 * @throws InvalidItemStateException if this Item object represents a workspace item that has been removed (either by this session or another).
	 * @throws RepositoryException if another error occurs.
	*/
	public function refresh($keepChanges) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212577830);
	}

}

?>
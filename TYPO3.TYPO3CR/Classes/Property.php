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
 * A Property
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Property extends \F3\TYPO3CR\AbstractItem implements \F3\PHPCR\PropertyInterface {

	/**
	 * @var mixed The raw value of the property
	 */
	protected $value;

	/**
	 * @var integer
	 */
	protected $type;

	/**
	 * @var mixed The Value object(s) of the property
	 */
	protected $valueObject;

	/**
	 * @var \F3\PHPCR\ValueFactoryInterface
	 */
	protected $valueFactory;

	/**
	 * Constructs a Property
	 *
	 * @param string $name The name of the property
	 * @param mixed $value The raw value of the property
	 * @param integer $type The type to set for the property (see \F3\PHPCR\PropertyTypes)
	 * @param \F3\PHPCR\NodeInterface $parentNode
	 * @param \F3\PHPCR\NodeInterface $session
	 * @param \F3\PHPCR\ValueFactoryInterface $valueFactory
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($name, $value, $type, \F3\PHPCR\NodeInterface $parentNode, \F3\PHPCR\SessionInterface $session) {
		if ($value === NULL) throw new \F3\PHPCR\RepositoryException('Constructing a Property with a NULL value is not allowed', 1203336959);
		if (is_array($value)) {
			if (\F3\FLOW3\Utility\Arrays::containsMultipleTypes($value)) {
				throw new \F3\PHPCR\ValueFormatException('Mixing multiple types in a Value is not allowed.', 1214492501);
			}
		}

		$this->session = $session;
		$this->valueFactory = $session->getValueFactory();
		$this->parentNode = $parentNode;
		$this->name = $name;

		if ($type === \F3\PHPCR\PropertyType::UNDEFINED) {
			$this->type = \F3\TYPO3CR\ValueFactory::guessType($value);
		} else {
			$this->type = $type;
		}

		if ($value instanceof \DateTime) {
			$this->value = date_format($value, DATE_ISO8601);
		} else {
			$this->value = $value;
		}
	}

	/**
	 * Sets the value of this property to value. If this property's property
	 * type is not constrained by the node type of its parent node, then the
	 * property type may be changed. If the property type is constrained, then a
	 * best-effort conversion is attempted.
	 *
	 * This method is a session-write and therefore requires a <code>save</code>
	 * to dispatch the change.
	 *
	 * For Node objects as value:
	 * Sets this REFERENCE OR WEAKREFERENCE property to refer to the specified
	 * node. If this property is not of type REFERENCE or WEAKREFERENCE or the
	 * specified node is not referenceable then a ValueFormatException is thrown.
	 *
	 * If value is an array:
	 * If this property is not multi-valued then a ValueFormatException is
	 * thrown immediately.
	 *
	 * @param mixed $value The value to set
	 * @return void
	 * @throws \F3\PHPCR\ValueFormatException if the type or format of the specified value is incompatible with the type of this property.
	 * @throws \F3\PHPCR\Version\VersionException if this property belongs to a node that is read-only due to a checked-in node and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\Lock\LockException if a lock prevents the setting of the value and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\ConstraintViolationException if the change would violate a node-type or other constraint and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo implement handling of Value objects as input
	 */
	public function setValue($value) {
		if ($value === NULL) {
			$this->remove();
			return;
		}

		if (is_array($value) && !$this->isMultiple()) {
			throw new \F3\PHPCR\ValueFormatException('Only multi-valued properties can be set to an array.', 1214481182);
		} elseif (is_array($value)) {
			if (\F3\FLOW3\Utility\Arrays::containsMultipleTypes($value)) {
				throw new \F3\PHPCR\ValueFormatException('Mixing multiple types in a Value is not allowed.', 1214492501);
			}
		}

		if($value instanceof \F3\PHPCR\ValueInterface || (is_array($value) && current($value) instanceof \F3\PHPCR\ValueInterface)) {
			throw new \F3\PHPCR\UnsupportedRepositoryOperationException('setValue() can not yet be handed Value objects', 1214493495);
		}

		if ($value instanceof \DateTime) {
			$this->value = date_format($value, DATE_ISO8601);
		} else {
			$this->value = $value;
		}
		$this->valueObject = NULL;
		$this->session->registerPropertyAsDirty($this);

		$this->session->registerNodeAsDirty($this->getParent());
	}

	/**
	 * Returns the value of this property as a Value object.
	 *
	 * The object returned is a copy of the stored value and is immutable.
	 *
	 * @return \F3\PHPCR\ValueInterface the value
	 * @throws \F3\PHPCR\ValueFormatException if the property is multi-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getValue() {
		if ($this->isMultiple()) throw new \F3\PHPCR\ValueFormatException('getValue() cannot be called on multi-valued properties.', 1181084521);
		if ($this->valueObject === NULL) {
			$this->valueObject = $this->valueFactory->createValue($this->value, $this->type);
		}

		return clone $this->valueObject;
	}

	/**
	 * Returns an array of all the values of this property. Used to access
	 * multi-value properties. If the property is single-valued, this method
	 * throws a ValueFormatException. The array returned is a copy of the
	 * stored values, so changes to it are not reflected in internal storage.
	 *
	 * @return array of \F3\PHPCR\ValueInterface
	 * @throws \F3\PHPCR\ValueFormatException if the property is single-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getValues() {
		if (!$this->isMultiple()) throw new \F3\PHPCR\ValueFormatException('getValues() cannot be used to access single-valued properties.', 1189512545);

		if ($this->valueObject === NULL) {
			$this->valueObject = array();
			foreach ($this->value as $value) {
				$this->valueObject[] = $this->valueFactory->createValue($value);
			}
		}

		$values = array();
		foreach ($this->valueObject as $valueObject) {
			$values[] = clone $valueObject;
		}

		return $values;
	}

	/**
	 * Returns a String representation of the value of this property. A
	 * shortcut for Property.getValue().getString(). See Value.
	 *
	 * @return string A string representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a String is not possible or if the property is multi-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getString() {
		if ($this->isMultiple()) throw new \F3\PHPCR\ValueFormatException('getString() cannot be called on multi-valued properties.', 1203338111);

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
	 * Returns a Binary representation of the value of this property. A
	 * shortcut for Property.getValue().getBinary(). See Value.
	 *
	 * @return \F3\PHPCR\BinaryInterface A Binary representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if the property is multi-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs
	 */
	public function getBinary() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594477);
	}

	/**
	 * Returns an integer representation of the value of this property. A shortcut
	 * for Property.getValue().getLong(). See Value.
	 *
	 * @return integer An integer representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a long is not possible or if the property is multi-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getLong() {
		if ($this->isMultiple()) throw new \F3\PHPCR\ValueFormatException('getLong() cannot be called on multi-valued properties.', 1203338188);

		return $this->getValue()->getLong();
	}

	/**
	 * Returns a double representation of the value of this property. A
	 * shortcut for Property.getValue().getDouble(). See Value.
	 *
	 * @return float A float representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a double is not possible or if the property is multi-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDouble() {
		if ($this->isMultiple()) throw new \F3\PHPCR\ValueFormatException('getDouble() cannot be called on multi-valued properties.', 1203338189);

		return $this->getValue()->getDouble();
	}

	/**
	 * Returns a BigDecimal representation of the value of this property. A
	 * shortcut for Property.getValue().getDecimal(). See Value.
	 *
	 * @return float A float representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a BigDecimal is not possible or if the property is multi-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs
	 */
	public function getDecimal() {
		if ($this->isMultiple()) throw new \F3\PHPCR\ValueFormatException('getDecimal() cannot be called on multi-valued properties.', 1212594888);

		return $this->getValue()->getDecimal();
	}

	/**
	 * Returns a \DateTime representation of the value of this property. A
	 * shortcut for Property.getValue().getDate(). See Value.
	 *
	 * @return \DateTime A date representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a string is not possible or if the property is multi-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDate() {
		if ($this->isMultiple()) throw new \F3\PHPCR\ValueFormatException('getDate() cannot be called on multi-valued properties.', 1203338327);

		return $this->getValue()->getDate();
	}

	/**
	 * Returns a boolean representation of the value of this property. A
	 * shortcut for Property.getValue().getBoolean(). See Value.
	 *
	 * @return boolean A boolean representation of the value of this property.
	 * @throws \F3\PHPCR\ValueFormatException if conversion to a boolean is not possible or if the property is multi-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBoolean() {
		if ($this->isMultiple()) throw new \F3\PHPCR\ValueFormatException('getBoolean() cannot be called on multi-valued properties.', 1203338188);

		return $this->getValue()->getBoolean();
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
	 * @return \F3\PHPCR\NodeInterface the referenced Node
	 * @throws \F3\PHPCR\ValueFormatException if this property cannot be converted to a referring type (REFERENCE, WEAKREFERENCE or PATH), if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
	 * @throws \F3\PHPCR\ItemNotFoundException If this property is of type PATH or WEAKREFERENCE and no target node accessible by the current Session exists in this workspace. Note that this applies even if the property is a PATH and a property exists at the specified location. To dereference to a target property (as opposed to a target node), the method Property.getProperty is used.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNode() {
		if ($this->isMultiple()) throw new \F3\PHPCR\ValueFormatException('getNode() cannot be called on multi-valued properties.', 1217845644);

		switch ($this->type) {
			case \F3\PHPCR\PropertyType::REFERENCE:
			case \F3\PHPCR\PropertyType::WEAKREFERENCE:
				return $this->session->getNodeByIdentifier($this->getValue()->getString());
				break;
			case \F3\PHPCR\PropertyType::PATH:
				throw new \F3\PHPCR\UnsupportedRepositoryOperationException('getNode() for PATH properties is not yet supported.', 1217845501);
			default:
				throw new \F3\PHPCR\ValueFormatException('The property cannot be used as referring type for getNode().', 1217845587);
				break;
		}
	}

	/**
	 * If this property is of type PATH (or convertible to this type) this
	 * method returns the Property to which this property refers.
	 * If this property contains a relative path, it is interpreted relative
	 * to the parent node of this property. Therefore, when resolving such a
	 * relative path, the segment "." refers to the parent node itself, ".." to
	 * the parent of the parent node and "foo" to a sibling property of this
	 * property or this property itself.
	 *
	 * For example, if this property is located at /a/b/c and it has a value of
	 * "../d" then this method will return the property at /a/d if such exists.
	 *
	 * @return \F3\PHPCR\PropertyInterface the referenced property
	 * @throws \F3\PHPCR\ValueFormatException if this property cannot be converted to a PATH, if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
	 * @throws \F3\PHPCR\ItemNotFoundException If no property accessible by the current Session exists in this workspace at the specified path. Note that this applies even if a node exists at the specified location. To dereference to a target node, the method Property.getNode is used.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs
	 */
	public function getProperty() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594797);
	}

	/**
	 * Returns the length of the value of this property.
	 *
	 * For a BINARY property, getLength returns the number of bytes.
	 * For other property types, getLength returns the same value that would be
	 * returned by calling strlen() on the value when it has been converted to a
	 * STRING according to standard JCR propety type conversion.
	 *
	 * Returns -1 if the implementation cannot determine the length.
	 *
	 * @return integer an integer.
	 * @throws \F3\PHPCR\ValueFormatException if this property is multi-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 */
	public function getLength() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594796);
	}

	/**
	 * Returns an array holding the lengths of the values of this (multi-value)
	 * property in bytes where each is individually calculated as described in
	 * getLength().
	 *
	 * @return array an array of lengths
	 * @throws \F3\PHPCR\ValueFormatException if this property is single-valued.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 */
	public function getLengths() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594795);
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
	 * @return \F3\PHPCR\NodeType\PropertyDefinitionInterface a PropertyDefinition object.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getDefinition() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212594794);
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
	 * @throws \F3\PHPCR\RepositoryException if an error occurs
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Returns TRUE if this property is multi-valued and FALSE if this property
	 * is single-valued.
	 *
	 * @return boolean TRUE if this property is multi-valued; FALSE otherwise.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isMultiple() {
		return is_array($this->value);
	}


	/**
	 * Returns true if this is a new item, meaning that it exists only in
	 * transient storage on the Session and has not yet been saved. Within a
	 * transaction, isNew on an Item may return false (because the item has
	 * been saved) even if that Item is not in persistent storage (because the
	 * transaction has not yet been committed).
	 *
	 * Note that if an item returns true on isNew, then by definition is parent
	 * will return true on isModified.
	 *
	 * Note that in read-only implementations, this method will always return
	 * false.
	 *
	 * @return boolean TRUE if this item is new; FALSE otherwise.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isNew() {
		return $this->session->isRegisteredAsNewProperty($this);
	}

	/**
	 * Returns true if this Item has been saved but has subsequently been
	 * modified through the current session and therefore the state of this
	 * item as recorded in the session differs from the state of this item as
	 * saved. Within a transaction, isModified on an Item may return false
	 * (because the Item has been saved since the modification) even if the
	 * modification in question is not in persistent storage (because the
	 * transaction has not yet been committed).
	 *
	 * Note that in read-only implementations, this method will always return
	 * false.
	 *
	 * @return boolean TRUE if this item is modified; FALSE otherwise.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isModified() {
		return $this->session->isRegisteredAsDirtyProperty($this);
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
	 * Returns the normalized absolute path to this item.
	 *
	 * @returns string the normalized absolute path of this Item.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPath() {
		$buffer = $this->getParent()->getPath();
		if ($buffer !== '/') {
			$buffer .= '/';
		}
		$buffer .= $this->getName();
		return $buffer;
	}

	/**
	 * Returns the parent of this Item.
	 *
	 * @return \F3\HPPCR\NodeInterface The parent of this Item.
	 * @throws \F3\PHPCR\ItemNotFoundException if this Item< is the root node of a workspace.
	 * @throws \F3\PHPCR\AccessDeniedException if the current session does not have sufficent access to retrieve the parent of this item.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getParent() {
		return $this->parentNode;
	}

	/**
	 * Removes this item (and its subgraph).
	 *
	 * To persist a removal, a save must be performed that includes the (former)
	 * parent of the removed item within its scope.
	 *
	 * If a node with same-name siblings is removed, this decrements by one the
	 * indices of all the siblings with indices greater than that of the removed
	 * node. In other words, a removal compacts the array of same-name siblings
	 * and causes the minimal re-numbering required to maintain the original
	 * order but leave no gaps in the numbering.
	 *
	 * @return void
	 * @throws \F3\PHPCR\Version\VersionException if the parent node of this item is versionable and checked-in or is non-versionable but its nearest versionable ancestor is checked-in and this implementation performs this validation immediately instead of waiting until save.
	 * @throws \F3\PHPCR\Lock\LockException if a lock prevents the removal of this item and this implementation performs this validation immediately instead of waiting until save.
	 * @throws \F3\PHPCR\ConstraintViolationException if removing the specified item would violate a node type or implementation-specific constraint and this implementation performs this validation immediately instead of waiting until save.
	 * @throws \F3\PHPCR\AccessDeniedException if this item or an item in its subgraph is currently the target of a REFERENCE property located in this workspace but outside this item's subgraph and the current Session does not have read access to that REFERENCE property or if the current Session does not have sufficent privileges to remove the item.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @see SessionInterface::removeItem(String)
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove() {
			// removes the property, thus delegated to parent
		$this->getParent()->setProperty($this->getName(), NULL);
	}

}

?>
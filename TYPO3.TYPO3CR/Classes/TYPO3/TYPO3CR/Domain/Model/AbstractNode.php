<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Repository\NodeRepository;
use TYPO3\Flow\Annotations as Flow;

/**
 * A Node (persisted or transient)
 * Note: If this API is extended, make sure to also implement the additional
 * methods inside ProxyNode and keep NodeInterface in sync!
 */
abstract class AbstractNode implements NodeInterface {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * Properties of this Node
	 *
	 * @var array<mixed>
	 */
	protected $properties = array();

	/**
	 * An optional object which contains the content of this node
	 *
	 * @var \TYPO3\TYPO3CR\Domain\Model\ContentObjectProxy
	 */
	protected $contentObjectProxy;

	/**
	 * The name of the node type of this node
	 *
	 * @var string
	 */
	protected $nodeType = 'unstructured';

	/**
	 * If this node is hidden, it is not shown in a public place.
	 *
	 * @var boolean
	 */
	protected $hidden = FALSE;

	/**
	 * Date before which this node is automatically hidden
	 *
	 * @var \DateTime
	 */
	protected $hiddenBeforeDateTime;

	/**
	 * Date after which this node is automatically hidden
	 *
	 * @var \DateTime
	 */
	protected $hiddenAfterDateTime;

	/**
	 * If this node should be hidden in indexes, such as a website navigation
	 *
	 * @var boolean
	 */
	protected $hiddenInIndex = FALSE;

	/**
	 * List of role names which are required to access this node at all
	 *
	 * @var array<string>
	 */
	protected $accessRoles = array();

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * Returns an up to LABEL_MAXIMUM_LENGTH characters long plain text description
	 * of this node.
	 *
	 * @return string
	 */
	public function getLabel() {
		return $this->getNodeType()->getNodeLabelGenerator()->getLabel($this);
	}

	/**
	 * Returns a short abstract describing / containing summarized content of this node
	 *
	 * @return string
	 * @todo Implement real abstract rendering and use a property specified in the node type
	 */
	public function getAbstract() {
		$abstractParts = array();
		foreach ($this->getProperties() as $propertyValue) {
			if (!is_object($propertyValue) || method_exists($propertyValue, '__toString')) {
				$abstractParts[] = $propertyValue;
			}
		}
		$abstract = strip_tags(implode(' – ', $abstractParts));
		$croppedAbstract = \TYPO3\Flow\Utility\Unicode\Functions::substr($abstract, 0, 253);
		return $croppedAbstract . (strlen($croppedAbstract) < strlen($abstract) ? ' …' : '');
	}

	/**
	 * Sets the specified property.
	 * If the node has a content object attached, the property will be set there
	 * if it is settable.
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $value Value of the property
	 * @return void
	 */
	public function setProperty($propertyName, $value) {
		if (!is_object($this->contentObjectProxy)) {
			if (isset($this->properties[$propertyName]) && $this->properties[$propertyName] === $value) {
				return;
			}
			$this->properties[$propertyName] = $value;
			$this->update();
		} elseif (\TYPO3\Flow\Reflection\ObjectAccess::isPropertySettable($this->contentObjectProxy->getObject(), $propertyName)) {
			$contentObject = $this->contentObjectProxy->getObject();
			\TYPO3\Flow\Reflection\ObjectAccess::setProperty($contentObject, $propertyName, $value);
			$this->updateContentObject($contentObject);
		}
	}

	/**
	 * If this node has a property with the given name.
	 * If the node has a content object attached, the property will be checked
	 * there.
	 *
	 * @param string $propertyName
	 * @return boolean
	 */
	public function hasProperty($propertyName) {
		if (is_object($this->contentObjectProxy)) {
			return \TYPO3\Flow\Reflection\ObjectAccess::isPropertyGettable($this->contentObjectProxy->getObject(), $propertyName);
		}
		return isset($this->properties[$propertyName]);
	}

	/**
	 * Returns the specified property.
	 * If the node has a content object attached, the property will be fetched
	 * there if it is gettable.
	 *
	 * @param string $propertyName Name of the property
	 * @return mixed value of the property
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if the content object exists but does not contain the specified property.
	 */
	public function getProperty($propertyName) {
		if (!is_object($this->contentObjectProxy)) {
			return isset($this->properties[$propertyName]) ? $this->properties[$propertyName] : NULL;
		} elseif (\TYPO3\Flow\Reflection\ObjectAccess::isPropertyGettable($this->contentObjectProxy->getObject(), $propertyName)) {
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($this->contentObjectProxy->getObject(), $propertyName);
		}
		throw new \TYPO3\TYPO3CR\Exception\NodeException(sprintf('Property "%s" does not exist in content object of type %s.', $propertyName, get_class($this->contentObjectProxy->getObject())), 1291286995);
	}

	/**
	 * Removes the specified property.
	 * If the node has a content object attached, the property will not be removed on
	 * that object if it exists.
	 *
	 * @param string $propertyName Name of the property
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if the node does not contain the specified property
	 */
	public function removeProperty($propertyName) {
		if (!is_object($this->contentObjectProxy)) {
			if (isset($this->properties[$propertyName])) {
				unset($this->properties[$propertyName]);
				$this->update();
			} else {
				throw new \TYPO3\TYPO3CR\Exception\NodeException(sprintf('Property "%s" does not exist in node.', $propertyName), 1344952312);
			}
		}
	}

	/**
	 * Returns all properties of this node.
	 * If the node has a content object attached, the properties will be fetched
	 * there.
	 *
	 * @return array Property values, indexed by their name
	 */
	public function getProperties() {
		if (is_object($this->contentObjectProxy)) {
			return \TYPO3\Flow\Reflection\ObjectAccess::getGettableProperties($this->contentObjectProxy->getObject());
		}
		return $this->properties;
	}

	/**
	 * Returns the names of all properties of this node.
	 *
	 * @return array Property names
	 */
	public function getPropertyNames() {
		if (is_object($this->contentObjectProxy)) {
			return \TYPO3\Flow\Reflection\ObjectAccess::getGettablePropertyNames($this->contentObjectProxy->getObject());
		}
		return array_keys($this->properties);
	}

	/**
	 * Sets a content object for this node.
	 *
	 * @param object $contentObject The content object
	 * @return void
	 * @throws \InvalidArgumentException if the given contentObject is no object.
	 */
	public function setContentObject($contentObject) {
		if (!is_object($contentObject)) {
			throw new \InvalidArgumentException('Argument must be an object, ' . \gettype($contentObject) . ' given.', 1283522467);
		}
		if ($this->contentObjectProxy === NULL || $this->contentObjectProxy->getObject() !== $contentObject) {
			$this->contentObjectProxy = new ContentObjectProxy($contentObject);
			$this->update();
		}
	}

	/**
	 * Returns the content object of this node (if any).
	 *
	 * @return object
	 */
	public function getContentObject() {
		return ($this->contentObjectProxy !== NULL ? $this->contentObjectProxy->getObject() : NULL);
	}

	/**
	 * Unsets the content object of this node.
	 *
	 * @return void
	 */
	public function unsetContentObject() {
		if ($this->contentObjectProxy !== NULL) {
			$this->contentObjectProxy = NULL;
			$this->update();
		}
	}

	/**
	 * Sets the node type of this node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType
	 * @return void
	 */
	public function setNodeType(NodeType $nodeType) {
		if ($this->nodeType !== $nodeType->getName()) {
			$this->nodeType = $nodeType->getName();
			$this->update();
		}
	}

	/**
	 * Returns the node type of this node.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeType
	 */
	public function getNodeType() {
		return $this->nodeTypeManager->getNodeType($this->nodeType);
	}

	/**
	 * Sets the "hidden" flag for this node.
	 *
	 * @param boolean $hidden If TRUE, this Node will be hidden
	 * @return void
	 */
	public function setHidden($hidden) {
		if ($this->hidden !== (boolean)$hidden) {
			$this->hidden = (boolean)$hidden;
			$this->update();
		}
	}

	/**
	 * Returns the current state of the hidden flag
	 *
	 * @return boolean
	 */
	public function isHidden() {
		return $this->hidden;
	}

	/**
	 * Sets the date and time when this node becomes potentially visible.
	 *
	 * @param \DateTime $dateTime Date before this node should be hidden
	 * @return void
	 */
	public function setHiddenBeforeDateTime(\DateTime $dateTime = NULL) {
		if ($this->hiddenBeforeDateTime != $dateTime) {
			$this->hiddenBeforeDateTime = $dateTime;
			$this->update();
		}
	}

	/**
	 * Returns the date and time before which this node will be automatically hidden.
	 *
	 * @return \DateTime Date before this node will be hidden
	 */
	public function getHiddenBeforeDateTime() {
		return $this->hiddenBeforeDateTime;
	}

	/**
	 * Sets the date and time when this node should be automatically hidden
	 *
	 * @param \DateTime $dateTime Date after which this node should be hidden
	 * @return void
	 */
	public function setHiddenAfterDateTime(\DateTime $dateTime = NULL) {
		if ($this->hiddenAfterDateTime != $dateTime) {
			$this->hiddenAfterDateTime = $dateTime;
			$this->update();
		}
	}

	/**
	 * Returns the date and time after which this node will be automatically hidden.
	 *
	 * @return \DateTime Date after which this node will be hidden
	 */
	public function getHiddenAfterDateTime() {
		return $this->hiddenAfterDateTime;
	}

	/**
	 * Sets if this node should be hidden in indexes, such as a site navigation.
	 *
	 * @param boolean $hidden TRUE if it should be hidden, otherwise FALSE
	 * @return void
	 */
	public function setHiddenInIndex($hidden) {
		if ($this->hiddenInIndex !== (boolean)$hidden) {
			$this->hiddenInIndex = (boolean)$hidden;
			$this->update();
		}
	}

	/**
	 * If this node should be hidden in indexes
	 *
	 * @return boolean
	 */
	public function isHiddenInIndex() {
		return $this->hiddenInIndex;
	}

	/**
	 * Sets the roles which are required to access this node
	 *
	 * @param array $accessRoles
	 * @return void
	 * @throws \InvalidArgumentException if the array of roles contains something else than strings.
	 */
	public function setAccessRoles(array $accessRoles) {
		foreach ($accessRoles as $role) {
			if (!is_string($role)) {
				throw new \InvalidArgumentException('The role names passed to setAccessRoles() must all be of type string.', 1302172892);
			}
		}
		if ($this->accessRoles !== $accessRoles) {
			$this->accessRoles = $accessRoles;
			$this->update();
		}
	}

	/**
	 * Returns the names of defined access roles
	 *
	 * @return array
	 */
	public function getAccessRoles() {
		return $this->accessRoles;
	}

	/**
	 * By default this method does not do anything.
	 * For persisted nodes (PersistedNodeInterface) this updates the node in the node repository
	 *
	 * @return void
	 */
	protected function update() {
	}

	/**
	 * By default this method does not do anything.
	 * For persisted nodes (PersistedNodeInterface) this updates the content object via the PersistenceManager
	 *
	 * @param object $contentObject
	 * @return void
	 */
	protected function updateContentObject($contentObject) {
	}

}
?>
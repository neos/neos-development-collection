<?php
namespace Neos\ContentRepository\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Validation\Validator\NodeIdentifierValidator;
use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeException;

/**
 * Some NodeData (persisted or transient)
 *
 *
 * NOTE: This class is not supposed to be subclassed by userland code.
 *       If this API is modified, make sure to also implement the additional
 *       methods inside NodeData, NodeTemplate and Node and keep
 *       NodeInterface in sync!
 *
 */
abstract class AbstractNodeData
{
    /**
     * Properties of this Node
     *
     * @ORM\Column(type="flow_json_array")
     * @var array<mixed>
     */
    protected $properties = [];

    /**
     * An optional object which is used as a content container alternative to $properties
     *
     * @var ContentObjectProxy
     */
    protected $contentObjectProxy;

    /**
     * The name of the node type of this node
     *
     * @var string
     */
    protected $nodeType = 'unstructured';

    /**
     * @var \DateTimeInterface
     */
    protected $creationDateTime;

    /**
     * @var \DateTimeInterface
     */
    protected $lastModificationDateTime;

    /**
     * @var \DateTimeInterface
     */
    protected $lastPublicationDateTime;

    /**
     * If this node is hidden, it is not shown in a public place
     *
     * @var boolean
     */
    protected $hidden = false;

    /**
     * If set, this node is automatically hidden before the specified date / time
     *
     * @var \DateTimeInterface
     */
    protected $hiddenBeforeDateTime;

    /**
     * If set, this node is automatically hidden after the specified date / time
     *
     * @var \DateTimeInterface
     */
    protected $hiddenAfterDateTime;

    /**
     * If this node should be hidden in indexes, such as a website navigation
     *
     * @var boolean
     */
    protected $hiddenInIndex = false;

    /**
     * List of role names which are required to access this node at all
     *
     * @ORM\Column(type="flow_json_array")
     * @var array<string>
     */
    protected $accessRoles = [];

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * Constructs this node data container
     */
    public function __construct()
    {
        $this->creationDateTime = new \DateTime();
        $this->lastModificationDateTime = new \DateTime();
    }

    /**
     * Make sure the properties are always an array.
     *
     * If the JSON in the DB is corrupted, decoding it can fail, leading to
     * a null value. This may lead to errors later, when the value is used with
     * functions that expect an array.
     *
     * @return void
     * @ORM\PostLoad
     */
    public function ensurePropertiesIsNeverNull()
    {
        if (!is_array($this->properties)) {
            $this->properties = [];
        }
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
    public function setProperty($propertyName, $value)
    {
        if (!is_object($this->contentObjectProxy)) {
            switch ($this->getNodeType()->getPropertyType($propertyName)) {
                case 'references':
                    $nodeIdentifiers = [];
                    if (is_array($value)) {
                        foreach ($value as $nodeIdentifier) {
                            if ($nodeIdentifier instanceof NodeInterface || $nodeIdentifier instanceof AbstractNodeData) {
                                $nodeIdentifiers[] = $nodeIdentifier->getIdentifier();
                            } elseif (preg_match(NodeIdentifierValidator::PATTERN_MATCH_NODE_IDENTIFIER, $nodeIdentifier) !== 0) {
                                $nodeIdentifiers[] = $nodeIdentifier;
                            }
                        }
                    }
                    $value = $nodeIdentifiers;
                    break;
                case 'reference':
                    $nodeIdentifier = null;
                    if ($value instanceof NodeInterface || $value instanceof AbstractNodeData) {
                        $nodeIdentifier = $value->getIdentifier();
                    } elseif (preg_match(NodeIdentifierValidator::PATTERN_MATCH_NODE_IDENTIFIER, $value) !== 0) {
                        $nodeIdentifier = $value;
                    }
                    $value = $nodeIdentifier;
                    break;
            }

            $this->persistRelatedEntities($value);

            if (array_key_exists($propertyName, $this->properties) && $this->properties[$propertyName] === $value) {
                return;
            }

            $this->properties[$propertyName] = $value;

            $this->addOrUpdate();
        } elseif (ObjectAccess::isPropertySettable($this->contentObjectProxy->getObject(), $propertyName)) {
            $contentObject = $this->contentObjectProxy->getObject();
            ObjectAccess::setProperty($contentObject, $propertyName, $value);
            $this->updateContentObject($contentObject);
        }
    }

    /**
     * Checks if a property value contains an entity and persists it.
     *
     * @param mixed $value
     */
    protected function persistRelatedEntities($value)
    {
        if (!is_array($value) && !$value instanceof \Iterator) {
            $value = [$value];
        }
        foreach ($value as $element) {
            if (is_object($element) && $element instanceof PersistenceMagicInterface) {
                $this->persistenceManager->isNewObject($element) ? $this->persistenceManager->add($element) : $this->persistenceManager->update($element);
            }
        }
    }

    /**
     * If this node has a property with the given name.
     *
     * If the node has a content object attached, the property will be checked
     * there.
     *
     * @param string $propertyName Name of the property to test for
     * @return boolean
     */
    public function hasProperty($propertyName)
    {
        if (is_object($this->contentObjectProxy)) {
            return ObjectAccess::isPropertyGettable($this->contentObjectProxy->getObject(), $propertyName);
        }
        return array_key_exists($propertyName, $this->properties);
    }

    /**
     * Returns the specified property.
     *
     * If the node has a content object attached, the property will be fetched
     * there if it is gettable.
     *
     * @param string $propertyName Name of the property
     * @return mixed value of the property
     * @throws NodeException if the content object exists but does not contain the specified property.
     */
    public function getProperty($propertyName)
    {
        if (!is_object($this->contentObjectProxy)) {
            $value = isset($this->properties[$propertyName]) ? $this->properties[$propertyName] : null;
            if (!empty($value)) {
                if ($this->getNodeType()->getPropertyType($propertyName) === 'references') {
                    if (!is_array($value)) {
                        $value = [];
                    }
                }
            }
            return $value;
        } elseif (ObjectAccess::isPropertyGettable($this->contentObjectProxy->getObject(), $propertyName)) {
            return ObjectAccess::getProperty($this->contentObjectProxy->getObject(), $propertyName);
        }
        throw new NodeException(sprintf('Property "%s" does not exist in content object of type %s.', $propertyName, get_class($this->contentObjectProxy->getObject())), 1291286995);
    }

    /**
     * Removes the specified property.
     *
     * If the node has a content object attached, the property will not be removed on
     * that object if it exists.
     *
     * @param string $propertyName Name of the property
     * @return void
     * @throws NodeException if the node does not contain the specified property
     */
    public function removeProperty($propertyName)
    {
        if (!is_object($this->contentObjectProxy)) {
            if (array_key_exists($propertyName, $this->properties)) {
                unset($this->properties[$propertyName]);
                $this->addOrUpdate();
            } else {
                throw new NodeException(sprintf('Cannot remove non-existing property "%s" from node.', $propertyName), 1344952312);
            }
        }
    }

    /**
     * Returns all properties of this node.
     *
     * If the node has a content object attached, the properties will be fetched
     * there.
     *
     * @return array Property values, indexed by their name
     */
    public function getProperties()
    {
        if (is_object($this->contentObjectProxy)) {
            return ObjectAccess::getGettableProperties($this->contentObjectProxy->getObject());
        }

        $properties = [];
        foreach ($this->properties as $propertyName => $propertyValue) {
            $properties[$propertyName] = $this->getProperty($propertyName);
        }
        return $properties;
    }

    /**
     * Returns the names of all properties of this node.
     *
     * @return array Property names
     */
    public function getPropertyNames()
    {
        if (is_object($this->contentObjectProxy)) {
            return ObjectAccess::getGettablePropertyNames($this->contentObjectProxy->getObject());
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
    public function setContentObject($contentObject)
    {
        if (!is_object($contentObject)) {
            throw new \InvalidArgumentException('Argument must be an object, ' . \gettype($contentObject) . ' given.', 1283522467);
        }
        if ($this->contentObjectProxy === null || $this->contentObjectProxy->getObject() !== $contentObject) {
            $this->contentObjectProxy = new ContentObjectProxy($contentObject);
            $this->addOrUpdate();
        }
    }

    /**
     * Returns the content object of this node (if any).
     *
     * @return object The content object or NULL if none was set
     */
    public function getContentObject()
    {
        return ($this->contentObjectProxy !== null ? $this->contentObjectProxy->getObject() : null);
    }

    /**
     * Unsets the content object of this node.
     *
     * @return void
     */
    public function unsetContentObject()
    {
        if ($this->contentObjectProxy !== null) {
            $this->contentObjectProxy = null;
            $this->addOrUpdate();
        }
    }

    /**
     * Sets the node type of this node.
     *
     * @param NodeType $nodeType
     * @return void
     */
    public function setNodeType(NodeType $nodeType)
    {
        if ($this->nodeType !== $nodeType->getName()) {
            $this->nodeType = $nodeType->getName();
            $this->addOrUpdate();
        }
    }

    /**
     * Returns the node type of this node.
     *
     * @return NodeType
     * @throws NodeTypeNotFoundException
     */
    public function getNodeType()
    {
        return $this->nodeTypeManager->getNodeType($this->nodeType);
    }

    /**
     * @return \DateTime
     */
    public function getCreationDateTime()
    {
        return $this->creationDateTime;
    }

    /**
     * @return \DateTime
     */
    public function getLastModificationDateTime()
    {
        return $this->lastModificationDateTime;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastPublicationDateTime()
    {
        return $this->lastPublicationDateTime;
    }

    /**
     * @param \DateTimeInterface $lastPublicationDateTime
     * @return void
     */
    public function setLastPublicationDateTime(\DateTimeInterface $lastPublicationDateTime = null)
    {
        $this->lastPublicationDateTime = $lastPublicationDateTime;
    }

    /**
     * Sets the "hidden" flag for this node.
     *
     * @param boolean $hidden If true, this Node will be hidden
     * @return void
     */
    public function setHidden($hidden)
    {
        if ($this->hidden !== (boolean)$hidden) {
            $this->hidden = (boolean)$hidden;
            $this->addOrUpdate();
        }
    }

    /**
     * Returns the current state of the hidden flag
     *
     * @return boolean
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * Sets the date and time when this node becomes potentially visible.
     *
     * @param \DateTimeInterface $dateTime Date before this node should be hidden
     * @return void
     */
    public function setHiddenBeforeDateTime(\DateTimeInterface $dateTime = null)
    {
        if ($this->hiddenBeforeDateTime != $dateTime) {
            $this->hiddenBeforeDateTime = $dateTime;
            $this->addOrUpdate();
        }
    }

    /**
     * Returns the date and time before which this node will be automatically hidden.
     *
     * @return \DateTimeInterface Date before this node will be hidden or NULL if no such time was set
     */
    public function getHiddenBeforeDateTime()
    {
        return $this->hiddenBeforeDateTime;
    }

    /**
     * Sets the date and time when this node should be automatically hidden
     *
     * @param \DateTimeInterface $dateTime Date after which this node should be hidden or NULL if no such time was set
     * @return void
     */
    public function setHiddenAfterDateTime(\DateTimeInterface $dateTime = null)
    {
        if ($this->hiddenAfterDateTime != $dateTime) {
            $this->hiddenAfterDateTime = $dateTime;
            $this->addOrUpdate();
        }
    }

    /**
     * Returns the date and time after which this node will be automatically hidden.
     *
     * @return \DateTimeInterface Date after which this node will be hidden
     */
    public function getHiddenAfterDateTime()
    {
        return $this->hiddenAfterDateTime;
    }

    /**
     * Sets if this node should be hidden in indexes, such as a site navigation.
     *
     * @param boolean $hidden true if it should be hidden, otherwise false
     * @return void
     */
    public function setHiddenInIndex($hidden)
    {
        if ($this->hiddenInIndex !== (boolean)$hidden) {
            $this->hiddenInIndex = (boolean)$hidden;
            $this->addOrUpdate();
        }
    }

    /**
     * If this node should be hidden in indexes
     *
     * @return boolean
     */
    public function isHiddenInIndex()
    {
        return $this->hiddenInIndex;
    }

    /**
     * Sets the roles which are required to access this node
     *
     * @param array $accessRoles
     * @return void
     * @throws \InvalidArgumentException if the array of roles contains something else than strings.
     */
    public function setAccessRoles(array $accessRoles)
    {
        foreach ($accessRoles as $role) {
            if (!is_string($role)) {
                throw new \InvalidArgumentException('The role names passed to setAccessRoles() must all be of type string.', 1302172892);
            }
        }
        if ($this->accessRoles !== $accessRoles) {
            $this->accessRoles = $accessRoles;
            $this->addOrUpdate();
        }
    }

    /**
     * Returns the names of defined access roles
     *
     * @return array
     */
    public function getAccessRoles()
    {
        return $this->accessRoles;
    }

    /**
     * By default this method does not do anything.
     * For persisted nodes (PersistedNodeInterface) this updates the node in the node repository, for new nodes this
     * method will add the respective node to the repository.
     *
     * @return void
     */
    protected function addOrUpdate()
    {
    }

    /**
     * By default this method does not do anything.
     * For persisted nodes (PersistedNodeInterface) this updates the content object via the PersistenceManager
     *
     * @param object $contentObject
     * @return void
     */
    protected function updateContentObject($contentObject)
    {
    }

    /**
     * Returns the workspace this node is contained in
     *
     * @return \Neos\ContentRepository\Domain\Model\Workspace
     */
    abstract public function getWorkspace();
}

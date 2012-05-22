<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Repository\NodeRepository;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A Node inside the Content Repository. This is the main API for storing and
 * retrieving content in the system.
 *
 * Note: If this API is extended, make sure to also implement the additional
 * methods inside ProxyNode!
 *
 * @FLOW3\Entity
 * @FLOW3\Scope("prototype")
 */
class Node implements NodeInterface {

	/**
	 * @ORM\Version
	 * @var integer
	 */
	protected $version;

	/**
	 * Absolute path of this node
	 *
	 * @var string
	 * @FLOW3\Validate(type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 */
	protected $path;

	/**
	 * Absolute path of the parent path
	 *
	 * @var string
	 * @FLOW3\Validate(type="StringLength", options={ "maximum"=255 })
	 */
	protected $parentPath;

	/**
	 * Workspace this node is contained in
	 *
	 * @var \TYPO3\TYPO3CR\Domain\Model\Workspace
	 * @ORM\ManyToOne
	 * @ORM\JoinColumn(onDelete="SET NULL")
	 */
	protected $workspace;

	/**
	 * Identifier of this node which is unique within its workspace
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * Index within the nodes with the same parent
	 *
	 * @var integer
	 * @ORM\Column(name="sortingindex", nullable=true)
	 */
	protected $index;

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
	 * @ORM\ManyToOne
	 */
	protected $contentObjectProxy;

	/**
	 * The name of the content type of this node
	 *
	 * @var string
	 */
	protected $contentType = 'unstructured';

	/**
	 * If this is a removed node. This flag can and is only used in workspaces
	 * which do have a base workspace. In a bottom level workspace nodes are
	 * really removed, in other workspaces, removal is realized by this flag.
	 *
	 * @var boolean
	 */
	protected $removed = FALSE;

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
	 * @ORM\Column(nullable=true)
	 */
	protected $hiddenBeforeDateTime;

	/**
	 * Date after which this node is automatically hidden
	 *
	 * @var \DateTime
	 * @ORM\Column(nullable=true)
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
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Factory\ProxyNodeFactory
	 */
	protected $proxyNodeFactory;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Constructs this node
	 *
	 * @param string $path Absolute path of this node
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The workspace this node will be contained in
	 * @param string $identifier Uuid of this node. Specifying this only makes sense while creating corresponding nodes
	 */
	public function  __construct($path, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace, $identifier = NULL) {
		$this->setPath($path);
		$this->workspace = $workspace;
		$this->identifier = ($identifier === NULL) ? \TYPO3\FLOW3\Utility\Algorithms::generateUUID() : $identifier;
	}

	/**
	 * Sets the absolute path of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the path of a node manually may lead to unexpected behavior and bad breath.
	 *
	 * @param string $path
	 * @return void
	 * @throws \InvalidArgumentException if the given node path is invalid.
	 */
	public function setPath($path) {
		if (!is_string($path) || preg_match(self::MATCH_PATTERN_PATH, $path) !== 1) {
			throw new \InvalidArgumentException('Invalid path: A path must be a valid string, be absolute (starting with a slash) and contain only the allowed characters.', 1284369857);
		}
		$this->path = $path;
		if ($path === '/') {
			$this->parentPath = '';
		} elseif (substr_count($path, '/') === 1) {
			$this->parentPath = '/';
		} else {
			$this->parentPath = substr($path, 0, strrpos($path, '/'));
		}
	}

	/**
	 * Returns the path of this node
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Returns the absolute path of this node with additional context information (such as the workspace name).
	 *
	 * Example: /sites/mysitecom/homepage/about@user-admin
	 *
	 * @return string Node path with context information
	 */
	public function getContextPath() {
		$contextPath = $this->path;
		$workspaceName = $this->getContext()->getWorkspace()->getName();
		if ($workspaceName !== 'live') {
			$contextPath .= '@' . $workspaceName;
		}
		return $contextPath;
	}

	/**
	 * Returns the level at which this node is located.
	 * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
	 *
	 * @return integer
	 */
	public function getDepth() {
		return ($this->path === '/') ? 0 : substr_count($this->path, '/');
	}

	/**
	 * Returns the name of this node
	 *
	 * @return string
	 */
	public function getName() {
		return ($this->path === '/') ? '' : substr($this->path, strrpos($this->path, '/') + 1);
	}

	/**
	 * Returns an up to LABEL_MAXIMUM_LENGTH characters long plain text description
	 * of this node.
	 *
	 * @return string
	 * @todo Use a property specified by the content type once it supports it.
	 */
	public function getLabel() {
		$label = $this->hasProperty('title') ? strip_tags($this->getProperty('title')) : '(' . $this->getContentType() . ') '. $this->getName();
		$croppedLabel = \TYPO3\FLOW3\Utility\Unicode\Functions::substr($label, 0, self::LABEL_MAXIMUM_CHARACTERS);
		return $croppedLabel . (strlen($croppedLabel) < strlen($label) ? ' …' : '');
	}

	/**
	 * Returns a short abstract describing / containing summarized content of this node
	 *
	 * @return string
	 * @todo Implement real abstract rendering and use a property specified in the content type
	 */
	public function getAbstract() {
		$abstract = strip_tags(implode(' – ', $this->getProperties()));
		$croppedAbstract = \TYPO3\FLOW3\Utility\Unicode\Functions::substr($abstract, 0, 253);
		return $croppedAbstract . (strlen($croppedAbstract) < strlen($abstract) ? ' …' : '');
	}

	/**
	 * Sets the workspace of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the workspace of a node manually may lead to unexpected behavior.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function setWorkspace(\TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		if ($this->workspace !== $workspace) {
			$this->workspace = $workspace;
			$this->nodeRepository->update($this);
		}
	}

	/**
	 * Returns the workspace this node is contained in
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	public function getWorkspace() {
		return $this->workspace;
	}

	/**
	 * Returns the identifier of this node
	 *
	 * @return string the node's UUID (unique within the workspace)
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * Sets the index of this node
	 *
	 * NOTE: This method is meant for internal use and must only be used by other nodes.
	 *
	 * @param integer $index The new index
	 * @return void
	 */
	public function setIndex($index) {
		if ($this->index !== $index) {
			$this->index = $index;
			$this->nodeRepository->update($this);
		}
	}

	/**
	 * Returns the index of this node which determines the order among siblings
	 * with the same parent node.
	 *
	 * @return integer
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Returns the parent node of this node
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The parent node or NULL if this is the root node
	 */
	public function getParent() {
		if ($this->path === '/') {
			return NULL;
		}
		$parentNode = $this->nodeRepository->findOneByPath($this->parentPath, $this->getContext()->getWorkspace());
		$parentNode = $this->createProxyForContextIfNeeded($parentNode);
		return $this->filterNodeByContext($parentNode);
	}

	/**
	 * Returns the parent node path
	 *
	 * @return string Absolute node path of the parent node
	 */
	public function getParentPath() {
		return $this->parentPath;
	}

	/**
	 * Moves this node before the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if you try to move the root node or move to different level.
	 */
	public function moveBefore(NodeInterface $referenceNode) {
		if ($referenceNode === $this) {
			return;
		}

		if ($this->path === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1285005924);
		}

		$referenceNodePath = $referenceNode->getPath();
		if (substr($this->path, 0, strrpos($this->path, '/')) !== substr($referenceNodePath, 0, strrpos($referenceNodePath, '/'))) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('Moving to other levels is currently not supported.', 1285005926);
		}

		$this->nodeRepository->setNewIndex($this, NodeRepository::POSITION_BEFORE, $referenceNode);
	}

	/**
	 * Moves this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if you try to move the root node or move to different level.
	 */
	public function moveAfter(NodeInterface $referenceNode) {
		if ($referenceNode === $this) {
			return;
		}

		if ($this->path === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1316361483);
		}

		$referenceNodePath = $referenceNode->getPath();
		if (substr($this->path, 0, strrpos($this->path, '/')) !== substr($referenceNodePath, 0, strrpos($referenceNodePath, '/'))) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('Moving to other levels is currently not supported.', 1316361485);
		}

		$this->nodeRepository->setNewIndex($this, NodeRepository::POSITION_AFTER, $referenceNode);
	}

	/**
	 * Sets the specified property.
	 *
	 * If the node has a content object attached, the property will be set there
	 * if it is settable.
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $value Value of the property
	 * @return void
	 */
	public function setProperty($propertyName, $value) {
		if (!is_object($this->contentObjectProxy)) {
			if (!array_key_exists($propertyName, $this->properties) || $this->properties[$propertyName] !== $value) {
				$this->properties[$propertyName] = $value;
				$this->nodeRepository->update($this);
			}
		} elseif (\TYPO3\FLOW3\Reflection\ObjectAccess::isPropertySettable($this->contentObjectProxy->getObject(), $propertyName)) {
			$contentObject = $this->contentObjectProxy->getObject();
			\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($contentObject, $propertyName, $value);
			$this->persistenceManager->update($contentObject);
		}
	}

	/**
	 * If this node has a property with the given name.
	 *
	 * If the node has a content object attached, the property will be checked
	 * there.
	 *
	 * @param string $propertyName
	 * @return boolean
	 */
	public function hasProperty($propertyName) {
		if (is_object($this->contentObjectProxy)) {
			return \TYPO3\FLOW3\Reflection\ObjectAccess::isPropertyGettable($this->contentObjectProxy->getObject(), $propertyName);
		} else {
			return isset($this->properties[$propertyName]);
		}
	}

	/**
	 * Returns the specified property.
	 *
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
		} elseif (\TYPO3\FLOW3\Reflection\ObjectAccess::isPropertyGettable($this->contentObjectProxy->getObject(), $propertyName)) {
			return \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($this->contentObjectProxy->getObject(), $propertyName);
		}
		throw new \TYPO3\TYPO3CR\Exception\NodeException(sprintf('Property "%s" does not exist in content object of type %s.', $propertyName, get_class($this->contentObjectProxy->getObject())), 1291286995);
	}

	/**
	 * Returns all properties of this node.
	 *
	 * If the node has a content object attached, the properties will be fetched
	 * there.
	 *
	 * @return array Property values, indexed by their name
	 */
	public function getProperties() {
		if (is_object($this->contentObjectProxy)) {
			return \TYPO3\FLOW3\Reflection\ObjectAccess::getGettableProperties($this->contentObjectProxy->getObject());
		} else {
			return $this->properties;
		}
	}

	/**
	 * Returns the names of all properties of this node.
	 *
	 * @return array Property names
	 */
	public function getPropertyNames() {
		if (is_object($this->contentObjectProxy)) {
			return \TYPO3\FLOW3\Reflection\ObjectAccess::getGettablePropertyNames($this->contentObjectProxy->getObject());
		} else {
			return array_keys($this->properties);
		}
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
			$this->nodeRepository->update($this);
		}
	}

	/**
	 * Returns the content object of this node (if any).
	 *
	 * @return object
	 */
	public function getContentObject() {
		return ($this->contentObjectProxy !== NULL ? $this->contentObjectProxy->getObject(): NULL);
	}

	/**
	 * Unsets the content object of this node.
	 *
	 * @return void
	 */
	public function unsetContentObject() {
		if ($this->contentObjectProxy !== NULL) {
			$this->contentObjectProxy = NULL;
			$this->nodeRepository->update($this);
		}
	}

	/**
	 * Sets the content type of this node.
	 *
	 * @param string $contentType
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if you give an unknown content type.
	 */
	public function setContentType($contentType) {
		if (!$this->contentTypeManager->hasContentType($contentType)) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('Unknown content type "' . $contentType . '".', 1285519999);
		}
		if ($this->contentType !== $contentType) {
			$this->contentType = $contentType;
			$this->nodeRepository->update($this);
		}
	}

	/**
	 * Returns the content type of this node.
	 *
	 * @return string $contentType
	 */
	public function getContentType() {
		return $this->contentType;
	}

	/**
	 * Creates, adds and returns a child node of this node.
	 *
	 * @param string $name Name of the new node
	 * @param string $contentType Content type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 * @throws \InvalidArgumentException if the node name is not accepted.
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if a node with this path already exists.
	 */
	public function createNode($name, $contentType = NULL, $identifier = NULL) {
		if (!is_string($name) || preg_match(self::MATCH_PATTERN_NAME, $name) !== 1) {
			throw new \InvalidArgumentException('Invalid node name: A node name must only contain characters, numbers and the "-" sign.', 1292428697);
		}

		$newPath = $this->path . ($this->path === '/' ? '' : '/') . $name;
		if ($this->getNode($newPath) !== NULL) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('Node with path "' . $newPath . '" already exists.', 1292503465);
		}

		$newNode = new Node($newPath, $this->nodeRepository->getContext()->getWorkspace(), $identifier);
		$this->nodeRepository->add($newNode);
		$this->nodeRepository->setNewIndex($newNode, NodeRepository::POSITION_LAST);

		if ($contentType !== NULL) {
			$newNode->setContentType($contentType);
		}

		return $this->createProxyForContextIfNeeded($newNode, TRUE);
	}

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
	 */
	public function getNode($path) {
		$node = $this->nodeRepository->findOneByPath($this->normalizePath($path), $this->getContext()->getWorkspace());
		if ($node === NULL) {
			return NULL;
		} else {
			$node = $this->createProxyForContextIfNeeded($node);
			return $this->filterNodeByContext($node);
		}
	}

	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * content type. For now it is just the first child node.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The primary child node or NULL if no such node exists
	 */
	public function getPrimaryChildNode() {
		$node = $this->nodeRepository->findFirstByParentAndContentType($this->path, NULL, $this->getContext()->getWorkspace());
		if ($node === NULL) {
			return NULL;
		} else {
			$node = $this->createProxyForContextIfNeeded($node);
			return $this->filterNodeByContext($node);
		}
	}

	/**
	 * Returns all direct child nodes of this node.
	 * If a content type is specified, only nodes of that type are returned.
	 *
	 * @param string $contentTypeFilter If specified, only nodes with that content type are considered
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> An array of nodes or an empty array if no child nodes matched
	 */
	public function getChildNodes($contentTypeFilter = NULL) {
		$nodes = $this->nodeRepository->findByParentAndContentType($this->path, $contentTypeFilter, $this->getContext()->getWorkspace());
		return $this->proxyAndFilterNodesForContext($nodes);
	}

	/**
	 * Checks if this node has any child nodes.
	 *
	 * @param string $contentTypeFilter If specified, only nodes with that content type are considered
	 * @return boolean TRUE if this node has child nodes, otherwise FALSE
	 * @todo Needs proper implementation in NodeRepository which only counts nodes (considering workspaces, removed nodes etc.)
	 */
	public function hasChildNodes($contentTypeFilter = NULL) {
		return ($this->getChildNodes($contentTypeFilter) !== array());
	}

	/**
	 * Removes this node and all its child nodes.
	 *
	 * @return void
	 */
	public function remove() {
		foreach ($this->getChildNodes() as $childNode) {
			$childNode->remove();
		}

		if ($this->workspace->getBaseWorkspace() === NULL) {
			$this->nodeRepository->remove($this);
		} else {
			$this->removed = TRUE;
			$this->nodeRepository->update($this);
		}
	}

	/**
	 * If this node is a removed node.
	 *
	 * @return boolean
	 */
	public function isRemoved() {
		return $this->removed;
	}

	/**
	 * Sets the "hidden" flag for this node.
	 *
	 * @param boolean $hidden If TRUE, this Node will be hidden
	 * @return void
	 */
	public function setHidden($hidden) {
		if ($this->hidden !== (boolean) $hidden) {
			$this->hidden = (boolean)$hidden;
			$this->nodeRepository->update($this);
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
			$this->nodeRepository->update($this);
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
			$this->nodeRepository->update($this);
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
		if ($this->hiddenInIndex !== (boolean) $hidden) {
			$this->hiddenInIndex = (boolean) $hidden;
			$this->nodeRepository->update($this);
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
			$this->nodeRepository->update($this);
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
	 * Tells if this node is "visible".
	 *
	 * For this the "hidden" flag and the "hiddenBeforeDateTime" and "hiddenAfterDateTime" dates are taken into account.
	 * The fact that a node is "visible" does not imply that it can / may be shown to the user. Further modifiers
	 * such as isAccessible() need to be evaluated.
	 *
	 * @return boolean
	 */
	public function isVisible() {
		if ($this->hidden === TRUE) {
			return FALSE;
		}
		$currentDateTime = $this->getContext()->getCurrentDateTime();
		if ($this->hiddenBeforeDateTime !== NULL && $this->hiddenBeforeDateTime > $currentDateTime) {
			return FALSE;
		}
		if ($this->hiddenAfterDateTime !== NULL && $this->hiddenAfterDateTime < $currentDateTime) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Tells if this node may be accessed according to the current security context.
	 *
	 * @return boolean
	 */
	public function isAccessible() {
		if ($this->hasAccessRestrictions() === FALSE) {
			return TRUE;
		}

		foreach ($this->accessRoles as $roleName) {
			if ($this->securityContext->hasRole($roleName)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Tells if a node has access restrictions
	 *
	 * @return boolean
	 */
	public function hasAccessRestrictions() {
		if (!is_array($this->accessRoles) || empty($this->accessRoles)) {
			return FALSE;
		}

		if (count($this->accessRoles) === 1 && in_array('Everybody', $this->accessRoles)) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Returns the current context this node operates in.
	 *
	 * This is for internal use only.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	public function getContext() {
		return $this->nodeRepository->getContext();
	}

	/**
	 * Make the node "similar" to the given source node. That means,
	 *  - all properties
	 *  - index
	 *  - content type
	 *  - content object
	 * will be set to the same values as in the source node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $sourceNode
	 * @return void
	 */
	public function similarize(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $sourceNode) {
		foreach ($sourceNode->getProperties() as $propertyName => $propertyValue) {
			$this->setProperty($propertyName, $propertyValue);
		}
		$this->setIndex($sourceNode->getIndex());
		$this->setContentType($sourceNode->getContentType());
		$contentObject = $sourceNode->getContentObject();
		if ($contentObject !== NULL) {
			$this->setContentObject($contentObject);
		}
	}

	/**
	 * Normalizes the given path and returns an absolute path
	 *
	 * @param string $path The unnormalized path
	 * @return string The normalized absolute path
	 * @throws \InvalidArgumentException if your node path contains two consecutive slashes.
	 */
	protected function normalizePath($path) {
		if ($path === '.') {
			return $this->path;
		}

		if (strpos($path, '//') !== FALSE) {
			throw new \InvalidArgumentException('Paths must not contain two consecutive slashes.', 1291371910);
		}

		if ($path[0] === '/') {
			$absolutePath = $path;
		} else {
			$absolutePath = ($this->path === '/' ? '' : $this->path). '/' . $path;
		}
		$pathSegments = explode('/', $absolutePath);

		while (each($pathSegments)) {
			if (current($pathSegments) === '..') {
				prev($pathSegments);
				unset($pathSegments[key($pathSegments)]);
				unset($pathSegments[key($pathSegments)]);
				prev($pathSegments);
			} elseif (current($pathSegments) === '.') {
				unset($pathSegments[key($pathSegments)]);
				prev($pathSegments);
			}
		}
		$normalizedPath = implode('/', $pathSegments);
		return ($normalizedPath === '') ? '/' : $normalizedPath;
	}

	/**
	 * Proxy nodes for current context if needed and filter with current context.
	 * @see createProxyForContextIfNeeded
	 * @see filterNodeByContext
	 *
	 * @param array $originalNodes The nodes to proxy and filter
	 * @return array nodes filtered and proxied as needed for current context
	 */
	protected function proxyAndFilterNodesForContext(array $originalNodes) {
		$proxyNodes = array();
		foreach ($originalNodes as $index => $node) {
			$treatedNode = $this->createProxyForContextIfNeeded($node);
			$treatedNode = $this->filterNodeByContext($treatedNode);
			if ($treatedNode !== NULL) {
				$proxyNodes[$index] = $treatedNode;
			}
		}
		return $proxyNodes;
	}

	/**
	 * Will either return the same node or a proxy for current context.
	 *
	 * @param mixed $node The node to proxy
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The same node or a proxy in current context.
	 */
	protected function createProxyForContextIfNeeded(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		if ($node instanceof \TYPO3\TYPO3CR\Domain\Model\Node) {
			if ($node->getWorkspace() !== $this->nodeRepository->getContext()->getWorkspace()) {
				$node = $this->proxyNodeFactory->createFromNode($node);
			}
		}
		return $node;
	}

	/**
	 * Filter a node by the current context.
	 * Will either return the node or NULL if it is not permitted in current context.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected function filterNodeByContext(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		if ($node->isRemoved() && !$this->nodeRepository->getContext()->isRemovedContentShown()) {
			return NULL;
		}

		if (!$node->isVisible() && !$this->nodeRepository->getContext()->isInvisibleContentShown()) {
			return NULL;
		}

		if (!$node->isAccessible() && !$this->nodeRepository->getContext()->isInaccessibleContentShown()) {
			return NULL;
		}
		return $node;
	}

	/**
	 * For debugging purposes, the node can be converted to a string.
	 *
	 * @return string
	 */
	public function __toString() {
		return 'Node ' . $this->getContextPath() . '[' . $this->contentType . ']';
	}
}
?>
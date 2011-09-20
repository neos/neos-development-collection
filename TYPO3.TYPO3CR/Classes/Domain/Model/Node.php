<?php
namespace TYPO3\TYPO3CR\Domain\Model;

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
 * A Node inside the Content Repository. This is the main API for storing and
 * retrieving content in the system.
 *
 * Note: If this API is extended, make sure to also implement the additional
 * methods inside ProxyNode!
 *
 * @entity
 * @scope prototype
 */
class Node implements NodeInterface {

	/**
	 * Absolute path of this node
	 *
	 * @var string
	 * @validate StringLength(minimum = 1, maximum = 255)
	 */
	protected $path;

	/**
	 * Workspace this node is contained in
	 *
	 * @var \TYPO3\TYPO3CR\Domain\Model\Workspace
	 * @ManyToOne
	 * @JoinColumn(onDelete="SET NULL")
	 */
	protected $workspace;

	/**
	 * Identifier of this node which is unique within its workspace
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * Depth at which this node is located
	 *
	 * @var integer
	 */
	protected $depth;

	/**
	 * Index within the nodes with the same parent
	 *
	 * @var integer
	 * @Column(name="sorting_index",nullable=true)
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
	 * @ManyToOne
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
	 */
	protected $hiddenBeforeDate;

	/**
	 * Date after which this node is automatically hidden
	 *
	 * @var \DateTime
	 */
	protected $hiddenAfterDate;

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
	 * @var \TYPO3\TYPO3CR\Domain\Service\Context
	 * @transient
	 */
	protected $context;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Security\Context
	 * @transient
	 */
	protected $securityContext;

	/**
	 * @inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @inject
	 * @var \TYPO3\TYPO3CR\Domain\Factory\ProxyNodeFactory
	 */
	protected $proxyNodeFactory;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Constructs this node
	 *
	 * @param string $path Absolute path of this node
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The workspace this node will be contained in
	 * @param string $identifier Uuid of this node. Specifying this only makes sense while creating Corresponding Nodes
	 * @author Robert Lemke <robert@typo3.org>
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
	 * the path of a node manually may lead to unexpected behavior.
	 *
	 * @param string $path
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPath($path) {
		if (!is_string($path) || preg_match(self::MATCH_PATTERN_PATH, $path) !== 1) {
			throw new \InvalidArgumentException('Invalid path: A path must be a valid string, be absolute (starting with a slash) and contain only the allowed characters.', 1284369857);
		}
		$this->path = $path;
		$this->depth = ($path === '/') ? 0 : substr_count($path, '/');
	}

	/**
	 * Returns the path of this node
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContextPath() {
		$contextPath = $this->path;
		$workspaceName = $this->context->getWorkspace()->getName();
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDepth() {
		return $this->depth;
	}

	/**
	 * Returns the name of this node
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return ($this->path === '/') ? '' : substr($this->path, strrpos($this->path, '/') + 1);
	}

	/**
	 * Returns an up to LABEL_MAXIMUM_LENGTH characters long plain text description of this node
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Implement real abstract rendering ...
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getWorkspace() {
		return $this->workspace;
	}

	/**
	 * Returns the identifier of this node
	 *
	 * @return string the node's UUID (unique within the workspace)
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Returns the parent node of this node
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The parent node or NULL if this is the root node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParent() {
		if ($this->path === '/') {
			return NULL;
		}
		$parentNodePath = substr($this->path, 0, strrpos($this->path, '/'));
		$parentNode = $this->nodeRepository->findOneByPath($parentNodePath, $this->context->getWorkspace());
		return $this->treatNodeWithContext($parentNode);
	}

	/**
	 * Moves this node before the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christian Müller <christian@kitsunet.de>
	 */
	public function moveBefore(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode) {
		if ($this->path === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1285005924);
		}

		$referenceNodePath = $referenceNode->getPath();
		if (substr($this->path, 0, strrpos($this->path, '/')) !== substr($referenceNodePath, 0, strrpos($referenceNodePath, '/'))) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('Moving to other levels is currently not supported.', 1285005926);
		}

		$moveTo = $referenceNode->getIndex();
		$moveFrom = $this->getIndex();
		if (($referenceNode === $this) || ($moveFrom === ($moveTo-1))) {
			return;
		}
		if($moveTo > $moveFrom) {
			$moveTo -= 1;
		}

		$siblingsAndSelf = $this->getParent()->getChildNodes();
		foreach ($siblingsAndSelf as $currentIndex => $currentNode) {
			if ($currentIndex >= $moveTo && $currentIndex < $moveFrom) {
				$currentNode->setIndex($currentNode->getIndex()+1);
			} elseif ($currentIndex > $moveFrom && $currentIndex <= $moveTo) {
				$currentNode->setIndex($currentNode->getIndex()-1);
			}
		}
		$this->setIndex($moveTo);
	}

	/**
	 * Moves this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christian Müller <christian@kitsunet.de>
	 */
	function moveAfter(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode) {
		if ($this->path === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1285005924);
		}

		$referenceNodePath = $referenceNode->getPath();
		if (substr($this->path, 0, strrpos($this->path, '/')) !== substr($referenceNodePath, 0, strrpos($referenceNodePath, '/'))) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('Moving to other levels is currently not supported.', 1285005926);
		}

		$moveTo = $referenceNode->getIndex();
		$moveFrom = $this->getIndex();
		if (($referenceNode === $this) || ($moveFrom === ($moveTo+1))) {
			return;
		}
		if($moveTo < $moveFrom) {
			$moveTo += 1;
		}

		$siblingsAndSelf = $this->getParent()->getChildNodes();
		foreach ($siblingsAndSelf as $currentIndex => $currentNode) {
			if ($currentIndex >= $moveTo && $currentIndex < $moveFrom) {
				$currentNode->setIndex($currentNode->getIndex()+1);
			} elseif ($currentIndex > $moveFrom && $currentIndex <= $moveTo) {
				$currentNode->setIndex($currentNode->getIndex()-1);
			}
		}
		$this->setIndex($moveTo);
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if the a content object exists but does not contain the specified property
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentObject() {
		return ($this->contentObjectProxy !== NULL ? $this->contentObjectProxy->getObject(): NULL);
	}

	/**
	 * Unsets the content object of this node.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createNode($name, $contentType = NULL, $identifier = NULL) {
		if (!is_string($name) || preg_match(self::MATCH_PATTERN_NAME, $name) !== 1) {
			throw new \InvalidArgumentException('Invalid node name: A node name must only contain characters, numbers and the "-" sign.', 1292428697);
		}

		$currentWorkspace = $this->context->getWorkspace();

		$newPath = $this->path . ($this->path !== '/' ? '/' : '') . $name;
		if ($this->getNode($newPath) !== NULL) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('Node with path "' . $newPath . '" already exists.', 1292503465);
		}
		$newIndex = $this->nodeRepository->countByParentAndContentType($this->path, NULL, $currentWorkspace) + 1;

		$newNode = new Node($newPath, $currentWorkspace, $identifier);
		$this->nodeRepository->add($newNode);

		$newNode->setIndex($newIndex);
		if ($contentType !== NULL) {
			$newNode->setContentType($contentType);
		}

		return $this->treatNodeWithContext($newNode, TRUE);
	}

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNode($path) {
		$node = $this->nodeRepository->findOneByPath($this->normalizePath($path), $this->context->getWorkspace());
		return ($node !== NULL) ? $this->treatNodeWithContext($node) : NULL;
	}

	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * content type. For now it is just the first child node.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The primary child node or NULL if no such node exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPrimaryChildNode() {
		$node = $this->nodeRepository->findFirstByParentAndContentType($this->path, NULL, $this->context->getWorkspace());
		if (!$node) {
			return NULL;
		}
		return $this->treatNodeWithContext($node);
	}

	/**
	 * Returns all direct child nodes of this node.
	 * If a content type is specified, only nodes of that type are returned.
	 *
	 * @param string $contentTypeFilter If specified, only nodes with that content type are considered
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> An array of nodes or an empty array if no child nodes matched
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodes($contentTypeFilter = NULL) {
		$nodes = $this->nodeRepository->findByParentAndContentType($this->path, $contentTypeFilter, $this->context->getWorkspace());
		return $this->treatNodesWithContext($nodes);
	}

	/**
	 * Checks if this node has any child nodes.
	 *
	 * @param string $contentTypeFilter If specified, only nodes with that content type are considered
	 * @return boolean TRUE if this node has child nodes, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasChildNodes($contentTypeFilter = NULL) {
		return $this->nodeRepository->countByParentAndContentType($this->getPath(), $contentTypeFilter, $this->context->getWorkspace()) > 0;
	}

	/**
	 * Removes this node and all its child nodes.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isRemoved() {
		return $this->removed;
	}

	/**
	 * Sets the "hidden" flag for this node.
	 *
	 * @param boolean $hidden If TRUE, this Node will be hidden
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isHidden() {
		return $this->hidden;
	}

	/**
	 * Sets the date and time when this node becomes potentially visible.
	 *
	 * @param \DateTime $hideBeforeDate Date before this node should be hidden
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHiddenBeforeDate(\DateTime $dateTime = NULL) {
		if ($this->hiddenBeforeDate != $dateTime) {
			$this->hiddenBeforeDate = $dateTime;
			$this->nodeRepository->update($this);
		}
	}

	/**
	 * Returns the date and time before which this node will be automatically hidden.
	 *
	 * @return \DateTime Date before this node will be hidden
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getHiddenBeforeDate() {
		return $this->hiddenBeforeDate;
	}

	/**
	 * Sets the date and time when this node should be automatically hidden
	 *
	 * @param \DateTime $hideAfterDate Date after which this node should be hidden
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHiddenAfterDate(\DateTime $dateTime = NULL) {
		if ($this->hiddenAfterDate != $dateTime) {
			$this->hiddenAfterDate = $dateTime;
			$this->nodeRepository->update($this);
		}
	}

	/**
	 * Returns the date and time after which this node will be automatically hidden.
	 *
	 * @return \DateTime Date after which this node will be hidden
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getHiddenAfterDate() {
		return $this->hiddenAfterDate;
	}

	/**
	 * Sets if this node should be hidden in indexes, such as a site navigation.
	 *
	 * @param boolean $hidden TRUE if it should be hidden, otherwise FALSE
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isHiddenInIndex() {
		return $this->hiddenInIndex;
	}

	/**
	 * Sets the roles which are required to access this node
	 *
	 * @param array $accessRoles
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAccessRoles() {
		return $this->accessRoles;
	}

	/**
	 * Tells if this node is "visible".
	 *
	 * For this the "hidden" flag and the "hiddenBeforeDate" and "hiddenAfterDate" dates are taken into account.
	 * The fact that a node is "visible" does not imply that it can / may be shown to the user. Further modifiers
	 * such as isAccessible() need to be evaluated.
	 *
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isVisible() {
		if ($this->hidden === TRUE) {
			return FALSE;
		}
		$currentDateTime = $this->context->getCurrentDateTime();
		if ($this->hiddenBeforeDate !== NULL && $this->hiddenBeforeDate > $currentDateTime) {
			return FALSE;
		}
		if ($this->hiddenAfterDate !== NULL && $this->hiddenAfterDate < $currentDateTime) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Tells if this node may be accessed according to the current security context.
	 *
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function isAccessible() {
		if ($this->accessRoles === array()) {
			return TRUE;
		}

		if (!$this->securityContext->isInitialized()) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException(sprintf('The security context is not yet intialized, thus the Node "%s" cannot determine if it is accessible. Some code part is calling isAccessible() early than is possible at that initialization stage.', $this->path), 1315383002);
		}

		foreach ($this->accessRoles as $roleName) {
			if ($this->securityContext->hasRole($roleName)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Sets the context from which this node was acquired.
	 *
	 * This will be set by the context or other nodes while retrieving this node.
	 * This method is only for internal use, don't mess with it.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Service\Context $context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContext(\TYPO3\TYPO3CR\Domain\Service\Context $context) {
		$this->context = $context;
	}

	/**
	 * Returns the current context this node operates in.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Normalizes the given path and returns an absolute path
	 *
	 * @param string $path The unnormalized path
	 * @return string The normalized absolute path
	 * @author Robert Lemke <robert@typo3.org>
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
	 * Treats the given nodes with the current context
	 *
	 * @param array $originalNodes The nodes to contextize
	 * @return array The same node objects, but with the context of this node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function treatNodesWithContext(array $originalNodes) {
		$proxyNodes = array();
		foreach ($originalNodes as $index => $node) {
			$treatedNode = $this->treatNodeWithContext($node);
			if ($treatedNode !== NULL) {
				$proxyNodes[$index] = $treatedNode;
			}
		}
		return $proxyNodes;
	}

	/**
	 * Treats the given node with the current context
	 *
	 * @param mixed $node The node to contextize
	 * @param boolean $disableFilters If set to TRUE, the node will only be treated with context and not filtered
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The same node, but with the context of this node
	 * @author Robert Lemke <robert@typo3.org>
	 * @fixme This method does more than the name or description claims
	 */
	protected function treatNodeWithContext(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $disableFilters = FALSE) {
		if ($node instanceof \TYPO3\TYPO3CR\Domain\Model\Node) {
			if ($node->getWorkspace() !== $this->context->getWorkspace()) {
				$node = $this->proxyNodeFactory->createFromNode($node);
			}
			$node->setContext($this->context);
		}

		if ($disableFilters === FALSE) {
			if ($node->isRemoved() && !$this->context->isRemovedContentShown()) {
				return NULL;
			}

			if (!$node->isVisible() && !$this->context->isInvisibleContentShown()) {
				return NULL;
			}

			if (!$this->isAccessible() && !$this->context->isInaccessibleContentShown()) {
				return NULL;
			}
		}
		return $node;
	}
}

?>

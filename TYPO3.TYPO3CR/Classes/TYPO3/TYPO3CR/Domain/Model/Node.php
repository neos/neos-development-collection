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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A Node inside the Content Repository. This is the main API for storing and
 * retrieving content in the system.
 *
 * Note: If this API is extended, make sure to also implement the additional
 * methods inside ProxyNode and keep NodeInterface / PersistentNodeInterface in sync!
 *
 * @Flow\Entity
 */
class Node extends AbstractNode implements PersistentNodeInterface {

	/**
	 * @ORM\Version
	 * @var integer
	 */
	protected $version;

	/**
	 * Absolute path of this node
	 *
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 */
	protected $path;

	/**
	 * Absolute path of the parent path
	 *
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "maximum"=255 })
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
	 * @var integer
	 * @Flow\Transient
	 */
	protected $depth;

	/**
	 * @var string
	 * @Flow\Transient
	 */
	protected $name;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\ContentObjectProxy
	 * @ORM\ManyToOne
	 */
	protected $contentObjectProxy;

	/**
	 * If this is a removed node. This flag can and is only used in workspaces
	 * which do have a base workspace. In a bottom level workspace nodes are
	 * really removed, in other workspaces, removal is realized by this flag.
	 *
	 * @var boolean
	 */
	protected $removed = FALSE;

	/**
	 * @var \DateTime
	 * @ORM\Column(nullable=true)
	 */
	protected $hiddenBeforeDateTime;

	/**
	 * @var \DateTime
	 * @ORM\Column(nullable=true)
	 */
	protected $hiddenAfterDateTime;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Factory\ProxyNodeFactory
	 */
	protected $proxyNodeFactory;

	/**
	 * Constructs this node
	 *
	 * @param string $path Absolute path of this node
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The workspace this node will be contained in
	 * @param string $identifier Uuid of this node. Specifying this only makes sense while creating corresponding nodes
	 */
	public function __construct($path, Workspace $workspace, $identifier = NULL) {
		$this->setPath($path, FALSE);
		$this->workspace = $workspace;
		$this->identifier = ($identifier === NULL) ? \TYPO3\Flow\Utility\Algorithms::generateUUID() : $identifier;
	}

	/**
	 * Sets the absolute path of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the path of a node manually may lead to unexpected behavior and bad breath.
	 *
	 * @param string $path
	 * @param boolean $recursive
	 * @return void
	 * @throws \InvalidArgumentException if the given node path is invalid.
	 */
	public function setPath($path, $recursive = TRUE) {
		if (!is_string($path) || preg_match(self::MATCH_PATTERN_PATH, $path) !== 1) {
			throw new \InvalidArgumentException('Invalid path "' . $path . '" (a path must be a valid string, be absolute (starting with a slash) and contain only the allowed characters).', 1284369857);
		}

		if ($path === $this->path) {
			return;
		}

		if ($recursive === TRUE) {
			/** @var $childNode PersistentNodeInterface */
			foreach ($this->getChildNodes() as $childNode) {
				$childNode->setPath($path . '/' . $childNode->getName());
			}
		}

		$this->path = $path;
		if ($path === '/') {
			$this->parentPath = '';
			$this->depth = 0;
		} elseif (substr_count($path, '/') === 1) {
				$this->parentPath = '/';
		} else {
			$this->parentPath = substr($path, 0, strrpos($path, '/'));
		}
		$this->emitNodePathChanged();
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
		if ($this->depth === NULL) {
			$this->depth = $this->path === '/' ? 0 : substr_count($this->path, '/');
		}
		return $this->depth;
	}

	/**
	 * Set the name of the node to $newName, keeping it's position as it is.
	 *
	 * @param string $newName
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if you try to set the name of the root node.
	 */
	public function setName($newName) {
		if ($this->path === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be renamed.', 1346778388);
		}

		if ($this->getName() === $newName) {
			return;
		}

		$this->setPath($this->parentPath . ($this->parentPath === '/' ? '' : '/') . $newName);
		$this->update();
		$this->nodeRepository->persistEntities();
		$this->emitNodePathChanged();
	}

	/**
	 * Returns the name of this node
	 *
	 * @return string
	 */
	public function getName() {
		if ($this->name === NULL) {
			$this->name = $this->path === '/' ? '' : substr($this->path, strrpos($this->path, '/') + 1);
		}
		return $this->name;
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
	public function setWorkspace(Workspace $workspace) {
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
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface The parent node or NULL if this is the root node
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if you try to move the root node.
	 */
	public function moveBefore(PersistentNodeInterface $referenceNode) {
		if ($referenceNode === $this) {
			return;
		}

		if ($this->path === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1285005924);
		}

		if ($referenceNode->getParentPath() !== $this->parentPath) {
			$parentPath = $referenceNode->getParentPath();
			$this->setPath($parentPath . ($parentPath === '/' ? '' : '/') . $this->getName());
			$this->emitNodePathChanged();
		}
		$this->nodeRepository->setNewIndex($this, NodeRepository::POSITION_BEFORE, $referenceNode);
	}

	/**
	 * Moves this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if you try to move the root node.
	 */
	public function moveAfter(PersistentNodeInterface $referenceNode) {
		if ($referenceNode === $this) {
			return;
		}

		if ($this->path === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1316361483);
		}

		if ($referenceNode->getParentPath() !== $this->parentPath) {
			$parentPath = $referenceNode->getParentPath();
			$this->setPath($parentPath . ($parentPath === '/' ? '' : '/') . $this->getName());
			$this->emitNodePathChanged();
		}
		$this->nodeRepository->setNewIndex($this, NodeRepository::POSITION_AFTER, $referenceNode);
	}

	/**
	 * Moves this node into the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException if you try to move the root node.
	 */
	public function moveInto(PersistentNodeInterface $referenceNode) {
		if ($referenceNode === $this || $referenceNode === $this->getParent()) {
			return;
		}

		if ($this->path === '/') {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1346769001);
		}

		$parentPath = $referenceNode->getPath();
		$this->setPath($parentPath . ($parentPath === '/' ? '' : '/') . $this->getName());
		$this->nodeRepository->setNewIndex($this, NodeRepository::POSITION_LAST);
		$this->emitNodePathChanged();
	}

	/**
	 * Copies this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function copyBefore(PersistentNodeInterface $referenceNode, $nodeName) {
		$copiedNode = $referenceNode->getParent()->createSingleNode($nodeName);
		$copiedNode->similarize($this);
		/** @var $childNode PersistentNodeInterface */
		foreach ($this->getChildNodes() as $childNode) {
			$childNode->copyInto($copiedNode, $childNode->getName());
		}
		$copiedNode->moveBefore($referenceNode);

		return $copiedNode;
	}

	/**
	 * Copies this node after the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function copyAfter(PersistentNodeInterface $referenceNode, $nodeName) {
		$copiedNode = $referenceNode->getParent()->createSingleNode($nodeName);
		$copiedNode->similarize($this);
		/** @var $childNode PersistentNodeInterface */
		foreach ($this->getChildNodes() as $childNode) {
			$childNode->copyInto($copiedNode, $childNode->getName());
		}
		$copiedNode->moveAfter($referenceNode);

		return $copiedNode;
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $referenceNode
	 * @param string $nodeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function copyInto(PersistentNodeInterface $referenceNode, $nodeName) {
		$copiedNode = $referenceNode->createSingleNode($nodeName);
		$copiedNode->similarize($this);
		/** @var $childNode PersistentNodeInterface */
		foreach ($this->getChildNodes() as $childNode) {
			$childNode->copyInto($copiedNode, $childNode->getName());
		}

		return $copiedNode;
	}


	/**
	 * Creates, adds and returns a child node of this node. Also sets default
	 * properties and creates default subnodes.
	 *
	 * @param string $name Name of the new node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType Node type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 * @throws \InvalidArgumentException if the node name is not accepted.
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException if a node with this path already exists.
	 */
	public function createNode($name, NodeType $nodeType = NULL, $identifier = NULL) {
		$newNode = $this->createSingleNode($name, $nodeType, $identifier);
		if ($nodeType !== NULL) {
			foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
				$newNode->setProperty($propertyName, $propertyValue);
			}

			foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeType) {
				$newNode->createNode($childNodeName, $childNodeType);
			}
		}
		return $newNode;
	}

	/**
	 * Creates, adds and returns a child node of this node, without setting default
	 * properties or creating subnodes. Only used internally.
	 *
	 * @param string $name Name of the new node
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType Node type of the new node (optional)
	 * @param string $identifier The identifier of the node, unique within the workspace, optional(!)
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 * @throws \InvalidArgumentException if the node name is not accepted.
	 * @throws \TYPO3\TYPO3CR\Exception\NodeExistsException if a node with this path already exists.
	 */
	public function createSingleNode($name, NodeType $nodeType = NULL, $identifier = NULL) {
		if (!is_string($name) || preg_match(self::MATCH_PATTERN_NAME, $name) !== 1) {
			throw new \InvalidArgumentException('Invalid node name "' . $name . '" (a node name must only contain characters, numbers and the "-" sign).', 1292428697);
		}

		$newPath = $this->path . ($this->path === '/' ? '' : '/') . $name;
		if ($this->getNode($newPath) !== NULL) {
			throw new \TYPO3\TYPO3CR\Exception\NodeExistsException('Node with path "' . $newPath . '" already exists.', 1292503465);
		}

		$newNode = new Node($newPath, $this->nodeRepository->getContext()->getWorkspace(), $identifier);
		$this->nodeRepository->add($newNode);
		$this->nodeRepository->setNewIndex($newNode, NodeRepository::POSITION_LAST);

		if ($nodeType !== NULL) {
			$newNode->setNodeType($nodeType);
		}

		return $this->createProxyForContextIfNeeded($newNode, TRUE);
	}

	/**
	 * Creates and persists a node from the given $nodeTemplate as child node
	 *
	 * @param NodeTemplate $nodeTemplate
	 * @param string $nodeName name of the new node. If not specified the name of the nodeTemplate will be used.
	 * @return PersistentNodeInterface the freshly generated node
	 */
	public function createNodeFromTemplate(NodeTemplate $nodeTemplate, $nodeName = NULL) {
		$newNodeName = $nodeName !== NULL ? $nodeName : $nodeTemplate->getName();
		$newNode = $this->createNode($newNodeName, $nodeTemplate->getNodeType());
		$newNode->similarize($nodeTemplate);
		return $newNode;
	}

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface The specified node or NULL if no such node exists
	 */
	public function getNode($path) {
		$node = $this->nodeRepository->findOneByPath($this->normalizePath($path), $this->getContext()->getWorkspace());
		if ($node === NULL) {
			return NULL;
		}
		$node = $this->createProxyForContextIfNeeded($node);
		return $this->filterNodeByContext($node);
	}

	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * node type. For now it is just the first child node.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface The primary child node or NULL if no such node exists
	 */
	public function getPrimaryChildNode() {
		$node = $this->nodeRepository->findFirstByParentAndNodeType($this->path, NULL, $this->getContext()->getWorkspace());
		if ($node === NULL) {
			return NULL;
		}
		$node = $this->createProxyForContextIfNeeded($node);
		return $this->filterNodeByContext($node);
	}

	/**
	 * Returns all direct child nodes of this node.
	 * If a node type is specified, only nodes of that type are returned.
	 *
	 * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface> An array of nodes or an empty array if no child nodes matched
	 */
	public function getChildNodes($nodeTypeFilter = NULL) {
		$nodes = $this->nodeRepository->findByParentAndNodeType($this->path, $nodeTypeFilter, $this->getContext()->getWorkspace());
		return $this->proxyAndFilterNodesForContext($nodes);
	}

	/**
	 * Checks if this node has any child nodes.
	 *
	 * @param string $nodeTypeFilter If specified, only nodes with that node type are considered
	 * @return boolean TRUE if this node has child nodes, otherwise FALSE
	 * @todo Needs proper implementation in NodeRepository which only counts nodes (considering workspaces, removed nodes etc.)
	 */
	public function hasChildNodes($nodeTypeFilter = NULL) {
		return ($this->getChildNodes($nodeTypeFilter) !== array());
	}

	/**
	 * Removes this node and all its child nodes.
	 *
	 * @return void
	 */
	public function remove() {
		/** @var $childNode PersistentNodeInterface */
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
	 * Enables using the remove method when only setters are available
	 *
	 * @param boolean $removed If TRUE, this node and it's child nodes will be removed. Cannot handle FALSE (yet).
	 * @return void
	 */
	public function setRemoved($removed) {
		if ((boolean)$removed === TRUE) {
			$this->remove();
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
		// TODO: if security context can not be initialized (because too early), we return TRUE.
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
	 *  - node type
	 *  - content object
	 * will be set to the same values as in the source node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $sourceNode
	 * @return void
	 */
	public function similarize(NodeInterface $sourceNode) {
		foreach ($sourceNode->getProperties() as $propertyName => $propertyValue) {
			$this->setProperty($propertyName, $propertyValue);
		}

		$propertyNames = array(
			'nodeType', 'hidden', 'hiddenAfterDateTime',
			'hiddenBeforeDateTime', 'hiddenInIndex', 'accessRoles'
		);
		if ($sourceNode instanceof PersistentNodeInterface) {
			$propertyNames[] = 'index';
		}
		foreach ($propertyNames as $propertyName) {
			\TYPO3\Flow\Reflection\ObjectAccess::setProperty($this, $propertyName, \TYPO3\Flow\Reflection\ObjectAccess::getProperty($sourceNode, $propertyName));
		}

		$contentObject = $sourceNode->getContentObject();
		if ($contentObject !== NULL) {
			$this->setContentObject($contentObject);
		}
	}

	/**
	 * Normalizes the given path and returns an absolute path
	 *
	 * @param string $path The non-normalized path
	 * @return string The normalized absolute path
	 * @throws \InvalidArgumentException if your node path contains two consecutive slashes.
	 */
	protected function normalizePath($path) {
		if ($path === '.') {
			return $this->path;
		}

		if (!is_string($path)) {
			throw new \InvalidArgumentException(sprintf('An invalid node path was specified: is of type %s but a string is expected.', gettype($path)), 1357832901);
		}

		if (strpos($path, '//') !== FALSE) {
			throw new \InvalidArgumentException('Paths must not contain two consecutive slashes.', 1291371910);
		}

		if ($path[0] === '/') {
			$absolutePath = $path;
		} else {
			$absolutePath = ($this->path === '/' ? '' : $this->path) . '/' . $path;
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
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface The same node or a proxy in current context.
	 */
	protected function createProxyForContextIfNeeded(PersistentNodeInterface $node) {
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface
	 */
	protected function filterNodeByContext(PersistentNodeInterface $node) {
		if (!$this->nodeRepository->getContext()->isRemovedContentShown() && $node->isRemoved()) {
			return NULL;
		}

		if (!$this->nodeRepository->getContext()->isInvisibleContentShown() && !$node->isVisible()) {
			return NULL;
		}

		if (!$this->nodeRepository->getContext()->isInaccessibleContentShown() && !$node->isAccessible()) {
			return NULL;
		}
		return $node;
	}

	/**
	 * Updates this node
	 *
	 * @return void
	 */
	protected function update() {
		$this->nodeRepository->update($this);
	}

	/**
	 * @param object $contentObject
	 * @return void
	 */
	protected function updateContentObject($contentObject) {
		$this->persistenceManager->update($contentObject);
	}

	/**
	 * Signals that a node has changed it's path.
	 *
	 * @Flow\Signal
	 * @return void
	 */
	protected function emitNodePathChanged() {
	}

	/**
	 * For debugging purposes, the node can be converted to a string.
	 *
	 * @return string
	 */
	public function __toString() {
		return 'Node ' . $this->getContextPath() . '[' . $this->getNodeType()->getName() . ']';
	}

}
?>
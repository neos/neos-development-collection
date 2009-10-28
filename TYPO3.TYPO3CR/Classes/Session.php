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
 * A Session
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Session implements \F3\PHPCR\SessionInterface {

	/**
	 * XML export types
	 */
	const EXPORT_SYSTEM = 0;
	const EXPORT_DOCUMENT = 1;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\PHPCR\RepositoryInterface
	 */
	protected $repository;

	/**
	 * @var \F3\PHPCR\WorkspaceInterface
	 */
	protected $workspace;

	/**
	 * @var \F3\TYPO3CR\Storage\BackendInterface
	 */
	protected $storageBackend;

	/**
	 * @var \F3\PHPCR\ValueFactoryInterface
	 */
	protected $valueFactory;

	/**
	 * @var boolean
	 */
	protected $isLive = TRUE;

	/**
	 * @var \F3\PHPCR\NodeInterface
	 */
	protected $rootNode;

	/**
	 * @var array of \F3\PHPCR\NodeInterface - stores references to all loaded nodes in a Identifier->value fashion.
	 */
	protected $currentlyLoadedNodes = array();

	/**
	 * @var array of \F3\PHPCR\NodeInterface - stores references to all new nodes (UoW)
	 */
	protected $currentlyNewNodes = array();

	/**
	 * @var array of \F3\PHPCR\NodeInterface - stores references to all dirty nodes (UoW)
	 */
	protected $currentlyDirtyNodes = array();

	/**
	 * @var array of \F3\PHPCR\NodeInterface - stores references to all removed node (UoW)
	 */
	protected $currentlyRemovedNodes = array();

	/**
	 * @var array of \F3\PHPCR\PropertyInterface - stores references to all new properties (UoW)
	 */
	protected $currentlyNewProperties = array();

	/**
	 * @var array of \F3\PHPCR\PropertyInterface - stores references to all dirty properties (UoW)
	 */
	protected $currentlyDirtyProperties = array();

	/**
	 * @var array of \F3\PHPCR\PropertyInterface - stores references to all removed properties (UoW)
	 */
	protected $currentlyRemovedProperties = array();

	/**
	 * @var array Associative array of local namespace mappings (created either explicitely or implicitely)
	 */
	protected $localNamespaceMappings = array();

	/**
	 * Constructs a Session object
	 *
	 * @param string $workspaceName
	 * @param \F3\PHPCR\RepositoryInterface $repository
	 * @param \F3\TYPO3CR\Storage\BackendInterface $storageBackend
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @throws \InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($workspaceName, \F3\PHPCR\RepositoryInterface $repository, \F3\TYPO3CR\Storage\BackendInterface $storageBackend, \F3\FLOW3\Object\FactoryInterface $objectFactory) {
		if (!is_string($workspaceName) || $workspaceName == '') throw new \InvalidArgumentException('"' . $workspaceName . '" is no valid workspace name.', 1200616245);

		$this->objectFactory = $objectFactory;
		$this->repository = $repository;
		$this->storageBackend = $storageBackend;

		$this->workspace = $this->objectFactory->create('F3\PHPCR\WorkspaceInterface', $workspaceName, $this);
		$this->valueFactory = $this->objectFactory->create('F3\PHPCR\ValueFactoryInterface', $objectFactory, $this);
	}


	// JSR-283 methods


	/**
	 * Returns the Repository object through which the Session object was aquired.
	 *
	 * @return \F3\PHPCR\RepositoryInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getRepository() {
		return $this->repository;
	}

	/**
	 * Gets the user ID associated with this Session. This method is free to return an
	 * 'anonymous user ID' or NULL.
	 *
	 * @return mixed
	 * @api
	 */
	public function getUserID() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212408404);
	}

	/**
	 * Returns the names of the attributes set in this session as a result of the
	 * Credentials that were used to acquire it. This method returns an
	 * empty array if the Credentials instance did not provide attributes.
	 *
	 * @return array
	 * @api
	 */
	public function getAttributeNames() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212408403);
	}

	/**
	 * Returns the value of the named attribute as an Object, or NULL if no
	 * attribute of the given name exists.
	 *
	 * @param string $name
	 * @return object The value of the attribute or null if no attribute of the given name exists.
	 * @api
	 */
	public function getAttribute($name) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212408402);
	}

	/**
	 * Returns the Workspace attached to this Session.
	 *
	 * @return \F3\PHPCR\WorkspaceInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getWorkspace() {
		return $this->workspace;
	}

	/**
	 * Returns the root node of the workspace, "/". This node is the main access
	 * point to the content of the workspace.
	 *
	 * @return \F3\PHPCR\NodeInterface The root node of the workspace: a Node object.
	 * @throws RepositoryException if an error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getRootNode() {
		if ($this->rootNode === NULL) {
			$this->rootNode = $this->objectFactory->create(
				'F3\PHPCR\NodeInterface',
				$this->storageBackend->getRawRootNode(),
				$this);
			$this->currentlyLoadedNodes[$this->rootNode->getIdentifier()] = $this->rootNode;
		}

		return $this->rootNode;
	}

	/**
	 * Returns a new session in accordance with the specified (new) Credentials.
	 * Allows the current user to "impersonate" another using incomplete or relaxed
	 * credentials requirements (perhaps including a user name but no password, for
	 * example), assuming that this Session gives them that permission.
	 * The new Session is tied to a new Workspace instance. In other words, Workspace
	 * instances are not re-used. However, the Workspace instance returned represents
	 * the same actual persistent workspace entity in the repository as is represented
	 * by the Workspace object tied to this Session.
	 *
	 * @param \F3\PHPCR\CredentialsInterface $credentials A Credentials object
	 * @return \F3\PHPCR\SessionInterface a Session object
	 * @throws \F3\PHPCR\LoginException if the current session does not have sufficient access to perform the operation.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @api
	 */
	public function impersonate(\F3\PHPCR\CredentialsInterface $credentials) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212408401);
	}

	/**
	 * Returns the node specified by the given identifier. Applies to both referenceable
	 * and non-referenceable nodes.
	 *
	 * @param string $id An identifier.
	 * @return \F3\PHPCR\NodeInterface A Node.
	 * @throws \F3\PHPCR\ItemNotFoundException if no node with the specified identifier exists or if this Session does not have read access to the node with the specified identifier.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getNodeByIdentifier($id) {
		if (array_key_exists($id, $this->currentlyLoadedNodes)) {
			return $this->currentlyLoadedNodes[$id];
		}

		$rawNode = $this->storageBackend->getRawNodeByIdentifier($id);
		if ($rawNode === FALSE) {
			throw new \F3\PHPCR\ItemNotFoundException('Node with identifier ' . $id . ' not found in repository.', 1181070997);
		}
		$node = $this->objectFactory->create('F3\PHPCR\NodeInterface', $rawNode, $this);
		$this->currentlyLoadedNodes[$node->getIdentifier()] = $node;

		return $node;
	}

	/**
	 * Returns the node at the specified absolute path in the workspace. If no such
	 * node exists, then it returns the property at the specified path.
	 *
	 * This method should only be used if the application does not know whether the
	 * item at the indicated path is property or node. In cases where the application
	 * has this information, either getNode(java.lang.String) or
	 * getProperty(java.lang.String) should be used, as appropriate. In many repository
	 * implementations the node and property-specific methods are likely to be more
	 * efficient than getItem.
	 *
	 * @param string $absPath An absolute path.
	 * @return \F3\PHPCR\ItemInterface
	 * @throws \F3\PHPCR\PathNotFoundException if no accessible item is found at the specified path.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getItem($absPath) {
		return \F3\TYPO3CR\PathParser::parsePath($absPath, $this->getRootNode(), \F3\TYPO3CR\PathParser::SEARCH_MODE_ITEMS);
	}

	/**
	 * Returns the node at the specified absolute path in the workspace.
	 *
	 * @param string $absPath An absolute path.
	 * @return \F3\PHPCR\NodeInterface A node
	 * @throws \F3\PHPCR\PathNotFoundException if no accessible node is found at the specified path.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getNode($absPath) {
		return $this->getRootNode()->getNode($absPath);
	}

	/**
	 * Returns the property at the specified absolute path in the workspace.
	 *
	 * @param string $absPath An absolute path.
	 * @return \F3\PHPCR\PropertyInterface A property
	 * @throws \F3\PHPCR\PathNotFoundException if no accessible property is found at the specified path.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getProperty($absPath) {
		return $this->getRootNode()->getProperty($absPath);
	}

	/**
	 * Returns true if an item exists at absPath and this Session has read
	 * access to it; otherwise returns false.
	 *
	 * @param string $absPath An absolute path.
	 * @return boolean a boolean
	 * @throws \F3\PHPCR\RepositoryException if absPath is not a well-formed absolute path.
	 * @api
	 */
	public function itemExists($absPath) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485251);
	}

	/**
	 * Returns true if a node exists at absPath and this Session has read
	 * access to it; otherwise returns false.
	 *
	 * @param string $absPath An absolute path.
	 * @return boolean a boolean
	 * @throws \F3\PHPCR\RepositoryException if absPath is not a well-formed absolute path.
	 * @api
	 */
	public function nodeExists($absPath) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485252);
	}

	/**
	 * Returns true if a property exists at absPath and this Session has read
	 * access to it; otherwise returns false.
	 *
	 * @param string $absPath An absolute path.
	 * @return boolean a boolean
	 * @throws \F3\PHPCR\RepositoryException if absPath is not a well-formed absolute path.
	 * @api
	 */
	public function propertyExists($absPath) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485253);
	}

	/**
	 * Moves the node at srcAbsPath (and its entire subgraph) to the new location
	 * at destAbsPath.
	 *
	 * This is a session-write method and therefor requires a save to dispatch
	 * the change.
	 *
	 * The identifiers of referenceable nodes must not be changed by a move. The
	 * identifiers of non-referenceable nodes may change.
	 *
	 * A ConstraintViolationException is thrown either immediately, on dispatch
	 * or on persist, if performing this operation would violate a node type or
	 * implementation-specific constraint. Implementations may differ on when
	 * this validation is performed.
	 *
	 * As well, a ConstraintViolationException will be thrown on persist if an
	 * attempt is made to separately save either the source or destination node.
	 *
	 * Note that this behaviour differs from that of Workspace.move($srcAbsPath,
	 * $destAbsPath), which is a workspace-write method and therefore
	 * immediately dispatches changes.
	 *
	 * The destAbsPath provided must not have an index on its final element. If
	 * ordering is supported by the node type of the parent node of the new location,
	 * then the newly moved node is appended to the end of the child node list.
	 *
	 * This method cannot be used to move an individual property by itself. It
	 * moves an entire node and its subgraph.
	 *
	 * @param string $srcAbsPath the root of the subgraph to be moved.
	 * @param string $destAbsPath the location to which the subgraph is to be moved.
	 * @return void
	 * @throws \F3\PHPCR\ItemExistsException if a node already exists at destAbsPath and same-name siblings are not allowed.
	 * @throws \F3\PHPCR\PathNotFoundException if either srcAbsPath or destAbsPath cannot be found and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\Version\VersionException if the parent node of destAbsPath or the parent node of srcAbsPath is versionable and checked-in, or or is non-versionable and its nearest versionable ancestor is checked-in and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\ConstraintViolationException if a node-type or other constraint violation is detected immediately and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\Lock\LockException if the move operation would violate a lock and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\RepositoryException if the last element of destAbsPath has an index or if another error occurs.
	 * @api
	 */
	public function move($srcAbsPath, $destAbsPath) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485254);
	}

	/**
	 * Removes the specified item and its subgraph.
	 *
	 * This is a session-write method and therefore requires a save in order to
	 * dispatch the change.
	 *
	 * If a node with same-name siblings is removed, this decrements by one the
	 * indices of all the siblings with indices greater than that of the removed
	 * node. In other words, a removal compacts the array of same-name siblings
	 * and causes the minimal re-numbering required to maintain the original
	 * order but leave no gaps in the numbering.
	 *
	 * @param string $absPath the absolute path of the item to be removed.
	 * @return void
	 * @throws \F3\PHPCR\Version\VersionException if the parent node of the item at absPath is read-only due to a checked-in node and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\Lock\LockException if a lock prevents the removal of the specified item and this implementation performs this validation immediately instead.
	 * @throws \F3\PHPCR\ConstraintViolationException if removing the specified item would violate a node type or implementation-specific constraint and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\PathNotFoundException if no accessible item is found at $absPath property or if the specified item or an item in its subgraph is currently the target of a REFERENCE property located in this workspace but outside the specified item's subgraph and the current Session does not have read access to that REFERENCE property.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @see Item::remove()
	 * @api
	 */
	public function removeItem($absPath) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224512858);
	}

	/**
	 * Validates all pending changes currently recorded in this Session. If
	 * validation of all pending changes succeeds, then this change information
	 * is cleared from the Session.
	 *
	 * If the save occurs outside a transaction, the changes are dispatched and
	 * persisted. Upon being persisted the changes become potentially visible to
	 * other Sessions bound to the same persitent workspace.
	 *
	 * If the save occurs within a transaction, the changes are dispatched but
	 * are not persisted until the transaction is committed.
	 *
	 * If validation fails, then no pending changes are dispatched and they
	 * remain recorded on the Session. There is no best-effort or partial save.
	 *
	 * @return void
	 * @throws \F3\PHPCR\AccessDeniedException if any of the changes to be persisted would violate the access privileges of the this Session. Also thrown if any of the changes to be persisted would cause the removal of a node that is currently referenced by a REFERENCE property that this Session does not have read access to.
	 * @throws \F3\PHPCR\ItemExistsException if any of the changes to be persisted would be prevented by the presence of an already existing item in the workspace.
	 * @throws \F3\PHPCR\ConstraintViolationException if any of the changes to be persisted would violate a node type or restriction. Additionally, a repository may use this exception to enforce implementation- or configuration-dependent restrictions.
	 * @throws \F3\PHPCR\InvalidItemStateException if any of the changes to be persisted conflicts with a change already persisted through another session and the implementation is such that this conflict can only be detected at save-time and therefore was not detected earlier, at change-time.
	 * @throws \F3\PHPCR\ReferentialIntegrityException if any of the changes to be persisted would cause the removal of a node that is currently referenced by a REFERENCE property that this Session has read access to.
	 * @throws \F3\PHPCR\Version\VersionException if the save would make a result in a change to persistent storage that would violate the read-only status of a checked-in node.
	 * @throws \F3\PHPCR\Lock\LockException if the save would result in a change to persistent storage that would violate a lock.
	 * @throws \F3\PHPCR\NodeType\NoSuchNodeTypeException if the save would result in the addition of a node with an unrecognized node type.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function save() {
		$this->validatePendingChanges();

		foreach ($this->currentlyNewNodes as $node) {
			$this->storageBackend->addNode($node);
			$this->addPropertiesForNode($node);
			unset($this->currentlyNewNodes[$node->getIdentifier()]);
		}

		foreach ($this->currentlyDirtyNodes as $node) {
			$this->storageBackend->updateNode($node);
			$this->addPropertiesForNode($node);
			$this->updatePropertiesForNode($node);
			$this->removePropertiesForNode($node);
			unset($this->currentlyDirtyNodes[$node->getIdentifier()]);
		}

		foreach ($this->currentlyRemovedNodes as $node) {
			$this->storageBackend->removeNode($node);
			$this->removePropertiesForNode($node);
			unset($this->currentlyRemovedNodes[$node->getIdentifier()]);
		}
	}

	/**
	 * If keepChanges is false, this method discards all pending changes currently
	 * recorded in this Session and returns all items to reflect the current saved
	 * state. Outside a transaction this state is simply the current state of
	 * persistent storage. Within a transaction, this state will reflect persistent
	 * storage as modified by changes that have been saved but not yet committed.
	 * If keepChanges is true then pending change are not discarded but items that
	 * do not have changes pending have their state refreshed to reflect the current
	 * saved state, thus revealing changes made by other sessions.
	 *
	 * @param boolean $keepChanges a boolean
	 * @return void
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @api
	 */
	public function refresh($keepChanges) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485255);
	}

	/**
	 * Returns true if this session holds pending (that is, unsaved) changes;
	 * otherwise returns false.
	 *
	 * @return boolean a boolean
	 * @throws \F3\PHPCR\RepositoryException if an error occurs
	 * @api
	 */
	public function hasPendingChanges() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485256);
	}

	/**
	 * This method returns a ValueFactory that is used to create Value objects for
	 * use when setting repository properties.
	 *
	 * @return \F3\PHPCR\ValueFactoryInterface
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getValueFactory() {
		return $this->valueFactory;
	}

	/**
	 * Returns true if this Session has permission to perform the specified
	 * actions at the specified absPath and false otherwise.
	 * The actions parameter is a comma separated list of action strings.
	 * The following action strings are defined:
	 *
	 * add_node: If hasPermission(path, "add_node") returns true, then this
	 * Session has permission to add a node at path.
	 * set_property: If hasPermission(path, "set_property") returns true, then
	 * this Session has permission to set (add or change) a property at path.
	 * remove: If hasPermission(path, "remove") returns true, then this Session
	 * has permission to remove an item at path.
	 * read: If hasPermission(path, "read") returns true, then this Session has
	 * permission to retrieve (and read the value of, in the case of a property)
	 * an item at path.
	 *
	 * When more than one action is specified in the actions parameter, this method
	 * will only return true if this Session has permission to perform all of the
	 * listed actions at the specified path.
	 *
	 * The information returned through this method will only reflect the access
	 * control status (both JCR defined and implementation-specific) and not
	 * other restrictions that may exist, such as node type constraints. For
	 * example, even though hasPermission may indicate that a particular Session
	 * may add a property at /A/B/C, the node type of the node at /A/B may
	 * prevent the addition of a property called C.
	 *
	 * @param string $absPath an absolute path.
	 * @param string $actions a comma separated list of action strings.
	 * @return boolean true if this Session has permission to perform the specified actions at the specified absPath.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @api
	 */
	public function hasPermission($absPath, $actions) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485257);
	}

	/**
	 * Determines whether this Session has permission to perform the specified actions
	 * at the specified absPath. This method quietly returns if the access request is
	 * permitted, or throws a suitable java.security.AccessControlException otherwise.
	 * The actions parameter is a comma separated list of action strings. The following
	 * action strings are defined:
	 *
	 * add_node: If checkPermission(path, "add_node") returns quietly, then this Session
	 * has permission to add a node at path, otherwise permission is denied.
	 * set_property: If checkPermission(path, "set_property") returns quietly, then this
	 * Session has permission to set (add or change) a property at path, otherwise
	 * permission is denied.
	 * remove: If checkPermission(path, "remove") returns quietly, then this Session
	 * has permission to remove an item at path, otherwise permission is denied.
	 * read: If checkPermission(path, "read") returns quietly, then this Session has
	 * permission to retrieve (and read the value of, in the case of a property) an
	 * item at path, otherwise permission is denied.
	 *
	 * When more than one action is specified in the actions parameter, this method
	 * will only return quietly if this Session has permission to perform all of the
	 * listed actions at the specified path.
	 *
	 * The information returned through this method will only reflect the access
	 * control status (both JCR defined and implementation-specific) and not
	 * other restrictions that may exist, such as node type constraints. For
	 * example, even though hasPermission may indicate that a particular Session
	 * may add a property at /A/B/C, the node type of the node at /A/B may
	 * prevent the addition of a property called C.
	 *
	 * @param string $absPath an absolute path.
	 * @param string $actions a comma separated list of action strings.
	 * @return void
	 * @throws java.security.AccessControlException If permission is denied.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @api
	 */
	public function checkPermission($absPath, $actions) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485258);
	}

	/**
	 * Checks whether an operation can be performed given as much context as can
	 * be determined by the repository, including:
	 *
	 * * Permissions granted to the current user, including access control privileges.
	 * * Current state of the target object (reflecting locks, checkin/checkout
	 *   status, retention and hold status etc.).
	 * * Repository capabilities.
	 * * Node type-enforced restrictions.
	 * * Repository configuration-specific restrictions.
	 *
	 * The implementation of this method is best effort: returning false guarantees
	 * that the operation cannot be performed, but returning true does not guarantee
	 * the opposite.
	 *
	 * The methodName parameter identifies the method in question by its name
	 * as defined in the Javadoc.
	 *
	 * The target parameter identifies the object on which the specified method
	 * is called.
	 *
	 * The arguments parameter contains a Map object consisting of
	 * name/value pairs where the name is a String holding the parameter name of
	 * the method as defined in the Javadoc and the value is an Object holding
	 * the value to be passed. In cases where the value is a Java primitive type
	 * it must be converted to its corresponding Java object form before being
	 * passed.
	 *
	 * For example, given a Session S and Node N then
	 *
	 * Map p = new HashMap();
	 * p.put("relPath", "foo");
	 * boolean b = S.hasCapability("addNode", N, p);
	 *
	 * will result in b == false if a child node called foo cannot be added to
	 * the node N within the session S.
	 *
	 * @param string $methodName the nakme of the method.
	 * @param object $target the target object of the operation.
	 * @param array $arguments the arguments of the operation.
	 * @return boolean FALSE if the operation cannot be performed, TRUE if the operation can be performed or if the repository cannot determine whether the operation can be performed.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs
	 * @api
	 */
	public function hasCapability($methodName, $target, array $arguments) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224508902);
	}

	/**
	 * Returns a \F3\PHPCR\ContentHandlerInterface which is used to push SAX events
	 * to the repository. If the incoming XML (in the form of SAX events)
	 * does not appear to be a JCR system view XML document then it is interpreted
	 * as a JCR document view XML document.
	 * The incoming XML is deserialized into a subgraph of items immediately below
	 * the node at parentAbsPath.
	 *
	 * This method simply returns the ContentHandler without altering the state of
	 * the session; the actual deserialization to the session transient space is
	 * done through the methods of the ContentHandler. Invalid XML data will
	 * cause the ContentHandler to throw a SAXException.
	 *
	 * As SAX events are fed into the ContentHandler, the tree of new items is built
	 * in the transient storage of the session. In order to dispatch the new
	 * content, save must be called. See Workspace.getImportContentHandler() for
	 * a workspace-write version of this method.
	 *
	 * The flag uuidBehavior governs how the identifiers of incoming nodes are
	 * handled. There are four options:
	 *
	 * ImportUUIDBehavior.IMPORT_UUID_CREATE_NEW: Incoming identifiers nodes are added
	 * in the same way that new node is added with Node.addNode. That is, they are either
	 * assigned newly created identifiers upon addition or upon save (depending on the
	 * implementation). In either case, identifier collisions will not occur.
	 *
	 * ImportUUIDBehavior.IMPORT_UUID_COLLISION_REMOVE_EXISTING: If an incoming node has
	 * the same identifier as a node already existing in the workspace then the already
	 * existing node (and its subgraph) is removed from wherever it may be in the workspace
	 * before the incoming node is added. Note that this can result in nodes "disappearing"
	 * from locations in the workspace that are remote from the location to which the
	 * incoming subgraph is being written. Both the removal and the new addition will be
	 * persisted on save.
	 *
	 * ImportUUIDBehavior.IMPORT_UUID_COLLISION_REPLACE_EXISTING: If an incoming node has
	 * the same identifier as a node already existing in the workspace, then the
	 * already-existing node is replaced by the incoming node in the same position as the
	 * existing node. Note that this may result in the incoming subgraph being disaggregated
	 * and "spread around" to different locations in the workspace. In the most extreme
	 * case this behavior may result in no node at all being added as child of parentAbsPath.
	 * This will occur if the topmost element of the incoming XML has the same identifier as
	 * an existing node elsewhere in the workspace. The change will be persisted on save.
	 *
	 * ImportUUIDBehavior.IMPORT_UUID_COLLISION_THROW: If an incoming node has the same
	 * identifier as a node already existing in the workspace then a SAXException is thrown
	 * by the ContentHandler during deserialization.
	 *
	 * Unlike Workspace.getImportContentHandler, this method does not necessarily enforce
	 * all node type constraints during deserialization. Those that would be immediately
	 * enforced in a session-write method (Node.addNode, Node.setProperty etc.) of this
	 * implementation cause the returned ContentHandler to throw an immediate SAXException
	 * during deserialization. All other constraints are checked on save, just as they are
	 * in normal write operations. However, which node type constraints are enforced depends
	 * upon whether node type information in the imported data is respected, and this is an
	 * implementation-specific issue.
	 *
	 * A SAXException will also be thrown by the returned ContentHandler during deserialization
	 * if uuidBehavior is set to IMPORT_UUID_COLLISION_REMOVE_EXISTING and an incoming node has
	 * the same identifier as the node at parentAbsPath or one of its ancestors.
	 *
	 * @param string $parentAbsPath the absolute path of a node under which (as child) the imported subgraph will be built.
	 * @param integer $uuidBehavior a four-value flag that governs how incoming identifiers are handled.
	 * @return \F3\PHPCR\ContentHandlerInterface whose methods may be called to feed SAX events into the deserializer.
	 * @throws \F3\PHPCR\PathNotFoundException if no node exists at parentAbsPath and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\ConstraintViolationException if the new subgraph cannot be added to the node at parentAbsPath due to node-type or other implementation-specific constraints, and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\Version\VersionException if the node at $parentAbsPath is read-only due to a checked-in node and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\Lock\LockException if a lock prevents the addition of the subgraph and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @api
	 */
	public function getImportContentHandler($parentAbsPath, $uuidBehavior) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485259);
	}

	/**
	 * Deserializes an XML document and adds the resulting item subgraph as a
	 * child of the node at $parentAbsPath.
	 * If the incoming XML does not appear to be a JCR system view XML document
	 * then it is interpreted as a document view XML document.
	 *
	 * The tree of new items is built in the transient storage of the Session. In order
	 * to persist the new content, save must be called. The advantage of this
	 * through-the-session method is that (depending on what constraint checks the
	 * implementation leaves until save) structures that violate node type constraints
	 * can be imported, fixed and then saved. The disadvantage is that a large import
	 * will result in a large cache of pending nodes in the session. See
	 * Workspace.importXML(string, string, integer) for a version
	 * of this method that does not go through the Session.
	 *
	 * The flag $uuidBehavior governs how the identifiers of incoming nodes are
	 * handled. There are four options:
	 *
	 * ImportUUIDBehavior.IMPORT_UUID_CREATE_NEW: Incoming nodes are added in the same
	 * way that new node is added with Node.addNode. That is, they are either assigned
	 * newly created identifiers upon addition or upon save (depending on the implementation,
	 * see 4.9.1.1 When Identifiers are Assigned in the specification). In either case,
	 * identifier collisions will not occur.
	 * ImportUUIDBehavior.IMPORT_UUID_COLLISION_REMOVE_EXISTING: If an incoming node has
	 * the same identifier as a node already existing in the workspace then the already
	 * existing node (and its subgraph) is removed from wherever it may be in the workspace
	 * before the incoming node is added. Note that this can result in nodes "disappearing"
	 * from locations in the workspace that are remote from the location to which the
	 * incoming subgraph is being written. Both the removal and the new addition will be
	 * dispatched on save.
	 * ImportUUIDBehavior.IMPORT_UUID_COLLISION_REPLACE_EXISTING: If an incoming node
	 * has the same identifier as a node already existing in the workspace, then the
	 * already-existing node is replaced by the incoming node in the same position as
	 * the existing node. Note that this may result in the incoming subgraph being
	 * disaggregated and "spread around" to different locations in the workspace. In the
	 * most extreme case this behavior may result in no node at all being added as child
	 * of parentAbsPath. This will occur if the topmost element of the incoming XML has
	 * the same identifier as an existing node elsewhere in the workspace. The change
	 * will be dispatched on save.
	 * ImportUUIDBehavior.IMPORT_UUID_COLLISION_THROW: If an incoming node has the same
	 * identifier as a node already existing in the workspace then an
	 * ItemExistsException is thrown.
	 * Unlike Workspace.importXML(string, string, integer), this method does not
	 * necessarily enforce all node type constraints during deserialization.
	 * Those that would be immediately enforced in a normal write method (Node.addNode,
	 * Node.setProperty etc.) of this implementation cause an immediate
	 * ConstraintViolationException during deserialization. All other constraints are
	 * checked on save, just as they are in normal write operations. However, which node
	 * type constraints are enforced depends upon whether node type information in the
	 * imported data is respected, and this is an implementation-specific issue.
	 *
	 * @param string $parentAbsPath the absolute path of the node below which the deserialized subgraph is added.
	 * @param string $in An URI from which the XML to be deserialized is read.
	 * @param integer $uuidBehavior a four-value flag that governs how incoming identifiers are handled.
	 * @return void
	 * @throws \RuntimeException if an error during an I/O operation occurs.
	 * @throws \F3\PHPCR\PathNotFoundException if no node exists at parentAbsPath and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\ItemExistsException if deserialization would overwrite an existing item and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\ConstraintViolationException if a node type or other implementation-specific constraint is violated that would be checked on a session write method or if uuidBehavior is set to IMPORT_UUID_COLLISION_REMOVE_EXISTING and an incoming node has the same UUID as the node at parentAbsPath or one of its ancestors.
	 * @throws \F3\PHPCR\Version\VersionException if the node at $parentAbsPath is read-only due to a checked-in node and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\InvalidSerializedDataException if incoming stream is not a valid XML document.
	 * @throws \F3\PHPCR\Lock\LockException if a lock prevents the addition of the subgraph and this implementation performs this validation immediately.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @api
	 */
	public function importXML($parentAbsPath, $in, $uuidBehavior) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485260);
	}

	/**
	 * Serializes the node (and if noRecurse is false, the whole subgraph) at $absPath
	 * as an XML stream and outputs it to the supplied XMLWriter.
	 *
	 * The resulting XML is in the system view form. Note that $absPath must be the path
	 * of a node, not a property.
	 * If skipBinary is true then any properties of PropertyType.BINARY will be serialized
	 * as if they are empty. That is, the existence of the property will be serialized,
	 * but its content will not appear in the serialized output (the <sv:value> element
	 * will have no content). Note that in the case of multi-value BINARY properties,
	 * the number of values in the property will be reflected in the serialized output,
	 * though they will all be empty. If skipBinary is false then the actual value(s)
	 * of each BINARY property is recorded using Base64 encoding.
	 *
	 * If noRecurse is true then only the node at absPath and its properties, but not
	 * its child nodes, are serialized. If noRecurse is false then the entire subgraph
	 * rooted at absPath is serialized.
	 *
	 * If the user lacks read access to some subsection of the specified tree, that
	 * section simply does not get serialized, since, from the user's point of view,
	 * it is not there.
	 *
	 * The serialized output will reflect the state of the current workspace as
	 * modified by the state of this Session. This means that pending changes
	 * (regardless of whether they are valid according to node type constraints)
	 * and all namespace mappings in the namespace registry, as modified by the
	 * current session-mappings, are reflected in the output.
	 *
	 * The output XML will be encoded in UTF-8.
	 *
	 * @param string $absPath The path of the root of the subgraph to be serialized. This must be the path to a node, not a property
	 * @param \XMLWriter $out The XMLWriter to which the XML serialization of the subgraph will be output.
	 * @param boolean $skipBinary A boolean governing whether binary properties are to be serialized.
	 * @param boolean $noRecurse A boolean governing whether the subgraph at absPath is to be recursed.
	 * @return void
	 * @throws \F3\PHPCR\PathNotFoundException if no node exists at absPath.
	 * @throws \RuntimeException if an error during an I/O operation occurs.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function exportSystemView($absPath, \XMLWriter $out, $skipBinary, $noRecurse) {
		$this->exportToXML($this->getNode($absPath), $out, !$skipBinary, !$noRecurse, self::EXPORT_SYSTEM, TRUE);
	}

	/**
	 * Serializes the node (and if noRecurse is false, the whole subgraph) at $absPath as an XML
	 * stream and outputs it to the supplied XMLWriter. The resulting XML is in the document
	 * view form. Note that $absPath must be the path of a node, not a property.
	 *
	 * If skipBinary is true then any properties of PropertyType.BINARY will be serialized as if
	 * they are empty. That is, the existence of the property will be serialized, but its content
	 * will not appear in the serialized output (the value of the attribute will be empty). If
	 * skipBinary is false then the actual value(s) of each BINARY property is recorded using
	 * Base64 encoding.
	 *
	 * If noRecurse is true then only the node at absPath and its properties, but not its
	 * child nodes, are serialized. If noRecurse is false then the entire subgraph rooted at
	 * absPath is serialized.
	 *
	 * If the user lacks read access to some subsection of the specified tree, that section
	 * simply does not get serialized, since, from the user's point of view, it is not there.
	 *
	 * The serialized output will reflect the state of the current workspace as modified by
	 * the state of this Session. This means that pending changes (regardless of whether they
	 * are valid according to node type constraints) and all namespace mappings in the
	 * namespace registry, as modified by the current session-mappings, are reflected in
	 * the output.
	 *
	 * The output XML will be encoded in UTF-8.
	 *
	 * @param string $absPath The path of the root of the subgraph to be serialized. This must be the path to a node, not a property
	 * @param \XMLWriter $out The XMLWriter to which the XML serialization of the subgraph will be output.
	 * @param boolean $skipBinary A boolean governing whether binary properties are to be serialized.
	 * @param boolean $noRecurse A boolean governing whether the subgraph at absPath is to be recursed.
	 * @return void
	 * @throws \F3\PHPCR\PathNotFoundException if no node exists at absPath.
	 * @throws \RuntimeException if an error during an I/O operation occurs.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function exportDocumentView($absPath, \XMLWriter $out, $skipBinary, $noRecurse) {
		$this->exportToXML($this->getNode($absPath), $out, !$skipBinary, !$noRecurse, self::EXPORT_DOCUMENT, TRUE);
	}

	/**
	 * Within the scope of this Session, this method maps uri to prefix. The
	 * remapping only affects operations done through this Session. To clear
	 * all remappings, the client must acquire a new Session.
	 * All local mappings already present in the Session that include either
	 * the specified prefix or the specified uri are removed and the new mapping
	 * is added.
	 *
	 * @param string $prefix a string
	 * @param string $uri a string
	 * @return void
	 * @throws \F3\PHPCR\NamespaceException if an attempt is made to map a namespace URI to a prefix beginning with the characters "xml" (in any combination of case) or if an attempt is made to map either the empty prefix or the empty namespace (i.e., if either $prefix or $uri are the empty string).
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setNamespacePrefix($prefix, $uri) {
		if (strtolower(substr($prefix, 0, 3)) == 'xml') {
			throw new \F3\PHPCR\NamespaceException('Attempt to register a prefix which starts with "XML" (in any combination of case)', 1190282877);
		}

		if (empty($prefix) || empty($uri)) {
			throw new \F3\PHPCR\NamespaceException('Attempt to map the empty prefix or the empty namespace.', 1190282972);
		}

		if (in_array($uri, $this->localNamespaceMappings)) {
			$prefixToUnset = array_search ($uri, $this->localNamespaceMappings);
			unset($this->localNamespaceMappings[$prefixToUnset]);
		}

		$this->localNamespaceMappings[$prefix] = $uri;
	}

	/**
	 * Returns all prefixes currently mapped to URIs in this Session.
	 *
	 * @return array a string array
	 * @throws \F3\PHPCR\RepositoryException if an error occurs
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getNamespacePrefixes() {
		$globalPrefixes = $this->workspace->getNamespaceRegistry()->getPrefixes();
		foreach ($globalPrefixes as $globalPrefix) {
			$this->loadNamespaceFromPrefix($globalPrefix);
		}

		return array_keys($this->localNamespaceMappings);
	}

	/**
	 * Returns the URI to which the given prefix is mapped as currently set in
	 * this Session.
	 *
	 * @param string $prefix a string
	 * @return string a string
	 * @throws \F3\PHPCR\NamespaceException if the specified prefix is unknown.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getNamespaceURI($prefix) {
		$this->loadNamespaceFromPrefix($prefix);
		return $this->localNamespaceMappings[$prefix];
	}

	/**
	 * Returns the prefix to which the given uri is mapped as currently set in
	 * this Session.
	 *
	 * @param string $uri a string
	 * @return string a string
	 * @throws \F3\PHPCR\NamespaceException if the specified uri is unknown.
	 * @throws \F3\PHPCR\RepositoryException - if another error occurs
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getNamespacePrefix($uri) {
		$this->loadNamespaceFromURI($uri);
		return array_search($uri, $this->localNamespaceMappings);
	}

	/**
	 * Releases all resources associated with this Session. This method should
	 * be called when a Session is no longer needed.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function logout() {
		$this->storageBackend->disconnect();
		$this->isLive = FALSE;
		$this->currentlyLoadedNodes = array();
	}

	/**
	 * Returns TRUE if this Session object is usable by the client. A usable
	 * Session object is one that is neither logged-out, timed-out nor in any
	 * other way disconnected from the repository.
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function isLive() {
		return $this->isLive;
	}

	/**
	 * Returns the access control manager for this Session.
	 *
	 * @return \F3\PHPCR\Security\AccessControlManager the access control manager for this Session
	 * @throws \F3\PHPCR\UnsupportedRepositoryOperationException if access control is not supported.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @api
	 */
	public function getAccessControlManager() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212485267);
	}

	/**
	 * Returns the retention and hold manager for this Session.
	 *
	 * @return \F3\PHPCR\Retention\RetentionManagerInterface the retention manager for this Session.
	 * @throws \F3\PHPCR\UnsupportedRepositoryOperationException if retention and hold are not supported.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @api
	 */
	public function getRetentionManager() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213801951);
	}


	// non-JSR-283 methods


	/**
	 * Returns the \F3\TYPO3CR\Storage\BackendInterface instance of the session
	 *
	 * @return \F3\TYPO3CR\Storage\BackendInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getStorageBackend() {
		return $this->storageBackend;
	}

	/**
	 * Helper method which loads the specified $prefix from
	 * the persistent namespace registry if it is not set locally.
	 *
	 * @param string $prefix
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @throws \F3\PHPCR\NamespaceException if prefix is unknown
	 */
	protected function loadNamespaceFromPrefix($prefix) {
		if (!isset($this->localNamespaceMappings[$prefix])) {
			$this->localNamespaceMappings[$prefix] = $this->workspace->getNamespaceRegistry()->getURI($prefix);
		}
	}

	/**
	 * Helper method which loads the specified $uri from
	 * the persistent namespace registry if it is not set locally.
	 *
	 * @param string $uri
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @throws \F3\PHPCR\NamespaceException if prefix is unknown
	 */
	protected function loadNamespaceFromURI($uri) {
		if (!in_array($uri, $this->localNamespaceMappings)) {
			$prefix = $this->workspace->getNamespaceRegistry()->getPrefix($uri);
			$this->localNamespaceMappings[$prefix] = $uri;
		}
	}

	/**
	 * Adds properties for node
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function addPropertiesForNode(\F3\PHPCR\NodeInterface $node) {
		$nodeIdentifier = $node->getIdentifier();
		if (isset($this->currentlyNewProperties[$nodeIdentifier])) {
			foreach ($this->currentlyNewProperties[$nodeIdentifier] as $property) {
				$this->storageBackend->addProperty($property);
				unset($this->currentlyNewProperties[$nodeIdentifier][$property->getName()]);
			}
		}
	}

	/**
	 * Updates properties for node
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function updatePropertiesForNode(\F3\PHPCR\NodeInterface $node) {
		$nodeIdentifier = $node->getIdentifier();
		if (isset($this->currentlyDirtyProperties[$nodeIdentifier])) {
			foreach ($this->currentlyDirtyProperties[$nodeIdentifier] as $property) {
				$this->storageBackend->updateProperty($property);
				unset($this->currentlyDirtyProperties[$nodeIdentifier][$property->getName()]);
			}
		}
	}

	/**
	 * Removes properties for node
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removePropertiesForNode(\F3\PHPCR\NodeInterface $node) {
		$nodeIdentifier = $node->getIdentifier();
		if (isset($this->currentlyRemovedProperties[$nodeIdentifier])) {
			foreach ($this->currentlyRemovedProperties[$nodeIdentifier] as $property) {
				$this->storageBackend->removeProperty($property);
				unset($this->currentlyRemovedProperties[$nodeIdentifier][$property->getName()]);
			}
		}
	}

	/**
	 * Returns TRUE if the $identifier is in use in the session or the underlying
	 * storage.
	 *
	 * @param string $identifier
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasIdentifier($identifier) {
		return isset($this->currentlyLoadedNodes[$identifier]) || $this->storageBackend->hasIdentifier($identifier);
	}

	/**
	 * Returns TRUE if the node has REFERENCE properties pointing at it.
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function nodeIsReferenceTarget(\F3\PHPCR\NodeInterface $node) {
		return $this->storageBackend->isReferenceTarget($node->getIdentifier());
	}

	/**
	 * Checks whether the current state can be saved. If not, exceptions are
	 * thrown according to the errors found.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function validatePendingChanges() {
		foreach ($this->currentlyRemovedNodes as $node) {
			if ($this->nodeIsReferenceTarget($node)) {
				foreach ($node->getReferences() as $reference) {
					if (!array_key_exists($reference->getParent()->getIdentifier(), $this->currentlyRemovedNodes)) {
						throw new \F3\PHPCR\ReferentialIntegrityException('The node with identifier ' . $node->getIdentifier() . ' is still a reference target.', 1227025834);
					}
				}
			}
		}
	}


	// Export methods


	/**
	 * Serializes the node (and if noRecurse is false, the whole subtree) at $absPath
	 * as an XML stream and outputs it to the supplied URI.
	 *
	 * If skipBinary is true then any properties of PropertyType.BINARY will be serialized
	 * as if they are empty. That is, the existence of the property will be serialized,
	 * but its content will not appear in the serialized output (the <sv:value> element
	 * will have no content). Note that in the case of multi-value BINARY properties,
	 * the number of values in the property will be reflected in the serialized output,
	 * though they will all be empty. If skipBinary is false then the actual value(s)
	 * of each BINARY property is recorded using Base64 encoding.
	 *
	 * If noRecurse is true then only the node at absPath and its properties, but not
	 * its child nodes, are serialized. If noRecurse is false then the entire subtree
	 * rooted at absPath is serialized.
	 *
	 * The resulting XML will represent system or document view depending on $exportType.
	 * If $createFullDocument is TRUE the output will be wrapped by the xml declaration
	 * and namespace declarations will be included in the top-level node tag.
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @param \XMLWriter $xmlWriter The XMLWriter to which the XML serialization of the subtree will be output.
	 * @param boolean $includeBinary A boolean governing whether binary properties are to be serialized.
	 * @param boolean $recurse A boolean governing whether the subtree at $absPath is to be recursed.
	 * @param integer $exportType One of self::EXPORT_SYSTEM or self::EXPORT_DOCUMENT
	 * @return void
	 * @throws \F3\PHPCR\PathNotFoundException if no node exists at absPath.
	 * @throws \RuntimeException if an error during an I/O operation occurs.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo escape names and values in document view
	 */
	protected function exportToXML(\F3\PHPCR\NodeInterface $node, \XMLWriter $xmlWriter, $includeBinary, $recurse, $exportType, $createFullDocument = FALSE) {
		if ($createFullDocument === TRUE) {
			$xmlWriter->startDocument('1.0', 'UTF-8');
		}

		if ($exportType === self::EXPORT_SYSTEM) {
			$xmlWriter->startElement('sv:node');
			$xmlWriter->writeAttribute('xmlns:sv', 'http://www.jcp.org/jcr/sv/1.0');
			if ($node->getDepth() === 0) {
				$xmlWriter->writeAttribute('sv:name', 'jcr:root');
			} else {
				$xmlWriter->writeAttribute('sv:name', $node->getName());
			}
			if ($createFullDocument === TRUE) {
				$this->writeNamespaceAttributes($xmlWriter);
			}
			$this->exportPropertiesForSystemView($node, $xmlWriter, $includeBinary);
		} else {
			if ($node->getName() === 'jcr:xmltext' && $node->hasNodes() === FALSE && count($node->getProperties()) === 1 && $node->hasProperty('jcr:xmlcharacters')) {
				$xmlWriter->text($node->getProperty('jcr:xmlcharacters')->getString());
			} else {
				if ($node->getDepth() === 0) {
					$xmlWriter->startElement('jcr:root');
				} else {
					$xmlWriter->startElement($node->getName());
				}
				if ($createFullDocument === TRUE) {
					$this->writeNamespaceAttributes($xmlWriter);
				}
				$this->exportPropertiesForDocumentView($node, $xmlWriter, $includeBinary);
			}
		}

		if ($recurse === TRUE) {
			foreach ($node->getNodes() as $node) {
				$this->exportToXML($node, $xmlWriter, $includeBinary, $recurse, $exportType);
			}
		}

		if ($exportType === self::EXPORT_SYSTEM || $node->getName() !== 'jcr:xmltext') {
			$xmlWriter->endElement();
		}

		if ($createFullDocument === TRUE) {
			$xmlWriter->endDocument();
		}
	}

	/**
	 * Writes out namespace attributes for all namespaces in the namespace registry.
	 *
	 * @param \XMLWriter $xmlWriter
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function writeNamespaceAttributes(\XMLWriter $xmlWriter) {
		foreach ($this->getNamespacePrefixes() as $prefix) {
			if ($prefix !== '') {
				$xmlWriter->writeAttribute('xmlns:' . $prefix, $this->getNamespaceURI($prefix));
			}
		}
	}

	/**
	 * Writes out the properties of the $node as XML to the given $xmlWriter.
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @param \XMLWriter $xmlWriter
	 * @param boolean $includeBinary
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo add mixinTypes as soon as getMixinNodeTypes() is implemented
	 */
	protected function exportPropertiesForSystemView(\F3\PHPCR\NodeInterface $node, \XMLWriter $xmlWriter, $includeBinary) {
		$xmlWriter->startElement('sv:property');
		$xmlWriter->writeAttribute('sv:name', 'jcr:primaryType');
		$xmlWriter->writeAttribute('sv:type', \F3\PHPCR\PropertyType::TYPENAME_STRING);
		$xmlWriter->writeElement('sv:value', $node->getPrimaryNodeType()->getName());
		$xmlWriter->endElement();

		$xmlWriter->startElement('sv:property');
		$xmlWriter->writeAttribute('sv:name', 'jcr:mixinTypes');
		$xmlWriter->writeAttribute('sv:type', \F3\PHPCR\PropertyType::TYPENAME_STRING);
		$mixinNodeTypes = array(); // $node->getMixinNodeTypes();
		foreach ($mixinNodeTypes as $mixinNodeType) {
			$xmlWriter->writeElement('sv:value', $mixinNodeType->getName());
		}
		$xmlWriter->endElement();

		$xmlWriter->startElement('sv:property');
		$xmlWriter->writeAttribute('sv:name', 'jcr:uuid');
		$xmlWriter->writeAttribute('sv:type', \F3\PHPCR\PropertyType::TYPENAME_STRING);
		$xmlWriter->writeElement('sv:value', $node->getIdentifier());
		$xmlWriter->endElement();

		$skip = array('jcr:uuid', 'jcr:primaryType', 'jcr:mixinTypes');
		foreach ($node->getProperties() as $property) {
			if (in_array($property->getName(), $skip)) continue;

			$xmlWriter->startElement('sv:property');
			$xmlWriter->writeAttribute('sv:name', $property->getName());
			$xmlWriter->writeAttribute('sv:type', \F3\PHPCR\PropertyType::nameFromValue($property->getType()));
			if ($property->isMultiple()) {
				$properties = $property->getValues();
				$xmlWriter->writeAttribute('sv:multiple', 'true');
				foreach ($properties as $value) {
					$xmlWriter->startElement('sv:value');
					if ($property->getType() === \F3\PHPCR\PropertyType::BINARY) {
						if ($includeBinary === TRUE) {
							$xmlWriter->text(base64_encode($value->getString()));
						}
					} else {
						$xmlWriter->text($value->getString());
					}
					$xmlWriter->endElement();
				}
			} else {
				$xmlWriter->startElement('sv:value');
				if ($property->getType() === \F3\PHPCR\PropertyType::BINARY) {
					if ($includeBinary === TRUE) {
						$xmlWriter->text(base64_encode($property->getValue()->getString()));
					}
				} else {
					$xmlWriter->text($property->getValue()->getString());
				}
				$xmlWriter->endElement();
			}
			$xmlWriter->endElement();
		}
	}

	/**
	 * Writes out the properties of the $node as XML to the given $xmlWriter.
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @param \XMLWriter $xmlWriter
	 * @param boolean $includeBinary
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo add mixinTypes as soon as getMixinNodeTypes() is implemented
	 * @todo export of multivalued properties, see http://forge.typo3.org/issues/show/3671
	 */
	protected function exportPropertiesForDocumentView(\F3\PHPCR\NodeInterface $node, \XMLWriter $xmlWriter, $includeBinary) {
		foreach ($node->getProperties() as $property) {
			if ($property->isMultiple()) {
				// handle multi-valued properties
			} else {
				$xmlWriter->startAttribute($property->getName());
				$value = $property->getValue();
				if ($property->getType() === \F3\PHPCR\PropertyType::BINARY) {
					if ($includeBinary === TRUE) {
						$xmlWriter->text(base64_encode($value->getString()));
					}
				} else {
					$xmlWriter->text($value->getString());
				}
				$xmlWriter->endAttribute();
			}
		}
	}


	// UoW methods


	/**
	 * Registers a node as new within this session
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerNodeAsNew(\F3\PHPCR\NodeInterface $node) {
		$this->currentlyLoadedNodes[$node->getIdentifier()] = $node;
		$this->currentlyNewNodes[$node->getIdentifier()] = $node;
	}

	/**
	 * Checks if the given node is registered as new
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isRegisteredAsNewNode(\F3\PHPCR\NodeInterface $node) {
		return isset($this->currentlyNewNodes[$node->getIdentifier()]);
	}

	/**
	 * Registers a node as dirty within this session
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerNodeAsDirty(\F3\PHPCR\NodeInterface $node) {
		if (!$this->isRegisteredAsNewNode($node)) {
			$this->currentlyDirtyNodes[$node->getIdentifier()] = $node;
		}
	}

	/**
	 * Checks if the given node is registered as dirty
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isRegisteredAsDirtyNode(\F3\PHPCR\NodeInterface $node) {
		return isset($this->currentlyDirtyNodes[$node->getIdentifier()]);
	}

	/**
	 * Registers a node as removed within this session
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerNodeAsRemoved(\F3\PHPCR\NodeInterface $node) {
		if ($this->isRegisteredAsNewNode($node)) {
			unset($this->currentlyNewNodes[$node->getIdentifier()]);
		}
		if ($this->isRegisteredAsDirtyNode($node)) {
			unset($this->currentlyDirtyNodes[$node->getIdentifier()]);
		}
		$this->currentlyRemovedNodes[$node->getIdentifier()] = $node;
		unset($this->currentlyLoadedNodes[$node->getIdentifier()]);
	}

	/**
	 * Registers a property as new within this session
	 *
	 * @param \F3\PHPCR\PropertyInterface $property
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerPropertyAsNew(\F3\PHPCR\PropertyInterface $property) {
		$this->currentlyNewProperties[$property->getParent()->getIdentifier()][$property->getName()] = $property;
	}

	/**
	 * Checks if the given property is registered as new
	 *
	 * @param \F3\PHPCR\PropertyInterface $property
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isRegisteredAsNewProperty(\F3\PHPCR\PropertyInterface $property) {
		return isset($this->currentlyNewProperties[$property->getParent()->getIdentifier()][$property->getName()]);
	}

	/**
	 * Registers a property item as dirty within this session
	 *
	 * @param \F3\PHPCR\PropertyInterface $property
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerPropertyAsDirty(\F3\PHPCR\PropertyInterface $property) {
		if (!$this->isRegisteredAsNewProperty($property)) {
			$this->currentlyDirtyProperties[$property->getParent()->getIdentifier()][$property->getName()] = $property;
		}
	}

	/**
	 * Checks if the given property is registered as new
	 *
	 * @param \F3\PHPCR\PropertyInterface $property
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isRegisteredAsDirtyProperty(\F3\PHPCR\PropertyInterface $property) {
		return isset($this->currentlyDirtyProperties[$property->getParent()->getIdentifier()][$property->getName()]);
	}

	/**
	 * Registers a property as removed within this session
	 *
	 * @param \F3\PHPCR\PropertyInterface $property
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerPropertyAsRemoved(\F3\PHPCR\PropertyInterface $property) {
		$this->currentlyRemovedProperties[$property->getParent()->getIdentifier()][$property->getName()] = $property;
	}

}
?>
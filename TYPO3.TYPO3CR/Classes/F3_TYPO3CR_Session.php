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
 * A Session
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Session implements F3_PHPCR_SessionInterface {

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var F3_TYPO3CR_Repository
	 */
	protected $repository;

	/**
	 * @var F3_TYPO3CR_Workspace
	 */
	protected $workspace;

	/**
	 * @var F3_TYPO3CR_StorageAccess_StorageAccessInterface
	 */
	protected $storageAccess;

	/**
	 * @var boolean
	 */
	protected $isLive = TRUE;

	/**
	 * @var F3_TYPO3CR_Node
	 */
	protected $rootNode;

	/**
	 * @var array of F3_TYPO3CR_Node - stores references to all loaded nodes in a Identifier->value fashion.
	 */
	protected $currentlyLoadedNodes = array();

	/**
	 * @var array Associative array of local namespace mappings (created either explicitely or implicitely)
	 */
	protected $localNamespaceMappings = array();

	/**
	 * Constructs a Session object
	 *
	 * @param  F3_PHPCR_WorkspaceInterface $workspace
	 * @param  F3_PHPCR_RepositoryInterface $repository
	 * @param  F3_TYPO3CR_StorageAccess_StorageAccessInterface $storageAccess
	 * @param  F3_FLOW3_Component_ManagerInterface $componentManager
	 * @throws InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($workspaceName, F3_PHPCR_RepositoryInterface $repository, F3_TYPO3CR_StorageAccess_StorageAccessInterface $storageAccess, F3_FLOW3_Component_ManagerInterface $componentManager) {
		if (!is_string($workspaceName) || $workspaceName == '') throw new InvalidArgumentException('"' . $workspaceName . '" is no valid workspace name.', 1200616245);

		$this->componentManager = $componentManager;
		$this->repository = $repository;
		$this->storageAccess = $storageAccess;
		$this->storageAccess->setWorkspaceName($workspaceName);

		$this->workspace = $this->componentManager->getComponent('F3_PHPCR_WorkspaceInterface', $workspaceName, $this, $storageAccess, $this->componentManager);
	}

	/**
	 * Returns the Repository object through which the Session object was aquired.
	 *
	 * @return F3_PHPCR_Repository
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRepository() {
		return $this->repository;
	}

	/**
	 * Gets the user ID associated with this Session. This method is free to return an
	 * 'anonymous user ID' or NULL.
	 *
	 * Currently always returns NULL!
	 *
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Implement after making the repository actually honour the credentials given to login().
	 */
	public function getUserID() {
		return NULL;
	}

	/**
	 * Returns the names of the attributes set in this session as a result of the
	 * Credentials that were used to acquire it. This method returns an
	 * empty array if the Credentials instance did not provide attributes.
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Implement after actually making use of the login credentials.
	 */
	public function getAttributeNames() {
		return array();
	}

	/**
	 * Returns the value of the named attribute as an Object, or NULL if no
	 * attribute of the given name exists.
	 *
	 * @param String $name
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Implement after actually making use of the login credentials.
	 */
	public function getAttribute($name) {
		return NULL;
	}

	/**
	 * Returns the Workspace attached to this Session.
	 *
	 * @return F3_TYPO3CR_Workspace
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getWorkspace() {
		return $this->workspace;
	}

	/**
	 * Returns the root node of the workspace, /. This node is the main access
	 * point to the content of the workspace.
	 *
	 * @return F3_TYPO3CR_Node
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRootNode() {
		if ($this->rootNode === NULL) {
			$this->rootNode = $this->componentManager->getComponent('F3_PHPCR_NodeInterface', $this, $this->storageAccess);
			$this->rootNode->initializeFromArray($this->storageAccess->getRawRootNode());
			$this->currentlyLoadedNodes[$this->rootNode->getIdentifier()] = $this->rootNode;
		}

		return $this->rootNode;
	}

	/**
	 * Get a node by its identifier
	 *
	 * @param string $id
	 * @return F3_TYPO3CR_Node
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getNodeByIdentifier($id) {
		if (array_key_exists($id, $this->currentlyLoadedNodes)) {
			return $this->currentlyLoadedNodes[$id];
		}

		$rawNode = $this->storageAccess->getRawNodeByIdentifier($id);
		if ($rawNode === FALSE) {
			throw new F3_PHPCR_ItemNotFoundException('Node with identifier ' . $id . ' not found in repository.', 1181070997);
		}
		$node = $this->componentManager->getComponent('F3_PHPCR_NodeInterface', $this, $this->storageAccess);
		$node->initializeFromArray($rawNode);
		$this->currentlyLoadedNodes[$node->getIdentifier()] = $node;

		return $node;
	}

	/**
	 * Releases all resources associated with this Session. This method should
	 * be called when a Session is no longer needed.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function logout() {
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
	 */
	public function isLive() {
		return $this->isLive;
	}

	/**
	 * Save all pending changes in the session
	 *
	 * @return void
	 */
	public function save() {
		throw new F3_PHPCR_RepositoryException('Session::save() not implemented yet', 1203091439);
	}

	/**
	 * Returns the node at the specified absolute path in the workspace.
	 * if no such node exists, then it returns the property at the specified path.
	 * If no such property exists, a F3_PHPCR_PathNotFoundException is thrown.
	 *
	 * @param string $absPath absolute path
	 * @return F3_PHPCR_Item
	 * @throws F3_PHPCR_PathNotFoundException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getItem($absPath) {
		$pathParser = $this->componentManager->getComponent('F3_TYPO3CR_PathParserInterface');
		return $pathParser->parsePath($absPath, $this->getRootNode(), F3_TYPO3CR_PathParserInterface::SEARCH_MODE_ITEMS);
	}

	/**
	 * Returns the node at the specified absolute path in the workspace.
	 * If no such node exists, a F3_PHPCR_PathNotFoundException is thrown.
	 *
	 * @param string $absPath absolute path
	 * @return F3_PHPCR_Item
	 * @throws F3_PHPCR_PathNotFoundException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getNode($absPath) {
		return $this->getRootNode()->getNode($absPath);
	}

	/**
	 * Returns the property at the specified absolute path in the workspace.
	 * If no such property exists, a F3_PHPCR_PathNotFoundException is thrown.
	 *
	 * @param string $absPath absolute path
	 * @return F3_PHPCR_Item
	 * @throws F3_PHPCR_PathNotFoundException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getProperty($absPath) {
		return $this->getRootNode()->getProperty($absPath);
	}

	/**
	 * This method returns a ValueFactory that is used to create Value objects for
	 * use when setting repository properties.
	 *
	 * @return F3_PHPCR_ValueFactoryInterface
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function getValueFactory() {
		return $this->componentManager->getComponent('F3_PHPCR_ValueFactoryInterface');
	}

	/**
	 * Maps $uri to $prefix within the scope of this session.
	 * Overrides previous mappings with the same URI/Prefix
	 *
	 * @param string $prefix: XML prefix
	 * @param string $uri: Namespace URI
	 * @return void
	 * @throws F3_PHPCR_NamespaceException if the prefix begins with "xml" or prefix/uri are empty
	 * @throws F3_PHPCR_RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function setNamespacePrefix($prefix, $uri) {
		if (F3_PHP6_Functions::strtolower(F3_PHP6_Functions::substr($prefix, 0, 3)) == 'xml') {
			throw new F3_PHPCR_NamespaceException('Attempt to register a prefix which starts with "XML" (in any combination of case)', 1190282877);
		}

		if (empty($prefix) || empty($uri)) {
			throw new F3_PHPCR_NamespaceException('Attempt to map the empty prefix or the empty namespace.', 1190282972);
		}

		if (in_array($uri, $this->localNamespaceMappings)) {
			$prefixToUnset = array_search ($uri, $this->localNamespaceMappings);
			unset($this->localNamespaceMappings[$prefixToUnset]);
		}

		$this->localNamespaceMappings[$prefix] = $uri;
	}

	/**
	 * Returns all prefixes currently available for this Session.
	 * This also includes all implicit and explicit nameaspace mappings,
	 * and the persistently registered namespaces.
	 *
	 * @return array All namespace prefixes available through this session
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getNamespacePrefixes() {
		$globalPrefixes = $this->workspace->getNamespaceRegistry()->getPrefixes();
		foreach ($globalPrefixes as $globalPrefix) {
			$this->loadNamespaceFromPrefix($globalPrefix);
		}

		return array_keys($this->localNamespaceMappings);
	}

	/**
	 * Returns the URI to which the given prefix is mapped in the current session.
	 *
	 * @param string $prefix
	 * @return string URI
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @throws F3_PHPCR_NamespaceException if prefix is unknown
	 */
	public function getNamespaceURI($prefix) {
		$this->loadNamespaceFromPrefix($prefix);
		return $this->localNamespaceMappings[$prefix];
	}

	/**
	 * Returns the prefix to which the given URI is mapped in the current session
	 *
	 * @param string $uri
	 * @return string prefix
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @throws F3_PHPCR_NamespaceException if uri is unknown
	 */
	public function getNamespacePrefix($uri) {
		$this->loadNamespaceFromURI($uri);
		return array_search($uri, $this->localNamespaceMappings);
	}

	/**
	 * Helper method which loads the specified $prefix from
	 * the persistent namespace registry if it is not set locally.
	 *
	 * @param string $prefix
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @throws F3_PHPCR_NamespaceException if prefix is unknown
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
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @throws F3_PHPCR_NamespaceException if prefix is unknown
	 */
	protected function loadNamespaceFromURI($uri) {
		if (!in_array($uri, $this->localNamespaceMappings)) {
			$prefix = $this->workspace->getNamespaceRegistry()->getPrefix($uri);
			$this->localNamespaceMappings[$prefix] = $uri;
		}
	}
}
?>
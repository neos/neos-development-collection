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
 * A NamespaceRegistry
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_NamespaceRegistry implements F3_PHPCR_NamespaceRegistryInterface {

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var array
	 */
	protected $builtInNamespaces = array(
		'jcr' => 'http://www.jcp.org/jcr/1.0',
		'nt' => 'http://www.jcp.org/jcr/nt/1.0',
		'mix' => 'http://www.jcp.org/jcr/mix/1.0',
		'xml' => 'http://www.w3.org/XML/1998/namespace',
		'' => ''
	);

	/**
	 * @var array
	 */
	protected $customNamespaces = array();

	/**
	 * Constructs a NamespaceRegistry object
	 *
	 * @param F3_TYPO3CR_StorageAccessInterface $storageAccess
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_TYPO3CR_StorageAccessInterface $storageAccess, F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->storageAccess = $storageAccess;
		$this->componentManager = $componentManager;

		$this->initializeCustomNamespaces();
	}

	/**
	 * Loads the custom namespaces from the persistent storage
	 *
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	protected function initializeCustomNamespaces() {
		$rawNamespaces = $this->storageAccess->getRawNamespaces();
		if (!count($rawNamespaces))	return;
		foreach ($rawNamespaces as $rawNamespace) {
			$this->customNamespaces[$rawNamespace['prefix']] = $rawNamespace['uri'];
		}
	}

	/**
	 * Returns an array holding all currently registered prefixes.
	 * Warning: All methods that use namespace mappings have to use
	 * Session::getNamespacePrefixes instead to ensure that "Session namespace
	 * mappings" work.
	 *
	 * @return array
	 * @throws F3_PHPCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPrefixes() {
		return array_merge(
			array_keys($this->builtInNamespaces),
			array_keys($this->customNamespaces)
		);
	}

	/**
	 * 	Returns an array holding all currently registered URIs.
	 *
	 * @return array
	 * @throws F3_PHPCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getURIs() {
		return array_merge(
			array_values($this->builtInNamespaces),
			array_values($this->customNamespaces)
		);
	}

	/**
	 * Returns the prefix which is mapped to the given uri. If a mapping with
	 * the specified uri does not exist, a NamespaceException is thrown.
	 * Warning: All methods that use namespace mappings have to use
	 * Session::getNamespacePrefix instead to ensure that "Session namespace
	 * mappings" work.
	 *
	 * @param string $uri
	 * @return string
	 * @throws F3_PHPCR_NamespaceException
	 * @throws F3_PHPCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getPrefix($uri) {
		$prefix = array_search($uri, $this->builtInNamespaces);
		if ($prefix === FALSE) {
			$prefix = array_search($uri, $this->customNamespaces);
			if ($prefix === FALSE) {
				throw new F3_PHPCR_NamespaceException('URI ' . $uri . ' not registered in NamespaceRegistry', 1184478139);
			}
		}
		return $prefix;
	}

	/**
	 * Returns the URI to which the given prefix is mapped. If a mapping
	 * with the specified prefix does not exist, a NamespaceException is
	 * thrown.
	 * Warning: All methods that use namespace mappings have to use
	 * Session::getNamespaceURI instead to ensure that "Session namespace
	 * mappings" work.
	 * @param string $prefix
	 * @return string
	 * @throws F3_PHPCR_NamespaceException
	 * @throws F3_PHPCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getURI($prefix) {
		if (array_key_exists($prefix, $this->builtInNamespaces)) {
			return $this->builtInNamespaces[$prefix];
		} elseif (array_key_exists($prefix, $this->customNamespaces)) {
			return $this->customNamespaces[$prefix];
		} else {
			throw new F3_PHPCR_NamespaceException('Prefix ' . $prefix . ' not registered in NamespaceRegistry', 1184478140);
		}
	}

	/**
	 * Sets a one-to-one mapping between prefix and uri in the global
	 * namespace registry of this repository.
	 * Assigning a new prefix to a URI that already exists in the namespace
	 * registry erases the old prefix.
	 * On the other hand, taking a prefix that is already assigned to a URI and
	 * re-assigning it to a new URI in effect unregisters that URI.
	 *
	 * @param string $prefix
	 * @param string $uri
	 * @return void
	 * @throws NamespaceException
	 * @throws UnsupportedRepositoryOperationException
	 * @throws AccessDeniedException
	 * @throws RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerNamespace($prefix, $uri) {
		if (!$this->isAllowedToModifyNamespace($prefix)) {
			throw new F3_PHPCR_NamespaceException('Attempt to register a protected namespace!', 1184478152);
		}

			// Update a namespace
		if (in_array($uri, $this->customNamespaces)) {
			$this->storageAccess->updateNamespacePrefix($prefix, $uri);
			$prefixToRemove = array_search($uri, $this->customNamespaces);
			unset($this->customNamespaces[$prefixToRemove]);
			$this->customNamespaces[$prefix] = $uri;
		} elseif (in_array($prefix, array_keys($this->customNamespaces))) {
			$this->storageAccess->updateNamespaceURI($prefix, $uri);
			$this->customNamespaces[$prefix] = $uri;
		} else {
			$this->storageAccess->addNamespace($prefix, $uri);
			$this->customNamespaces[$prefix] = $uri;
		}
	}

	/**
	 * Removes a namespace mapping from the registry.
	 *
	 * @param string $prefix
	 * @return void
	 * @throws NamespaceException
	 * @throws UnsupportedRepositoryOperationException
	 * @throws AccessDeniedException
	 * @throws RepositoryException
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function unregisterNamespace($prefix) {
		if (!$this->isAllowedToModifyNamespace($prefix)) {
			throw new F3_PHPCR_NamespaceException('Attempt to unregister a protected namespace!', 1184478149);
		}

		if (!array_key_exists($prefix, $this->customNamespaces)) {
			throw new F3_PHPCR_NamespaceException("Attempt to unregister a not registered namespace!", 1184479159);
		}
		$this->storageAccess->deleteNamespace($prefix);
		unset ($this->customNamespaces[$prefix]);
	}

	/**
	 * Are we allowed to register/unregister a workspace?
	 *
	 * @param string $prefix Prefix of namespace
	 * @return boolean TRUE if we are allowed to modify the namespace
	 * @throws F3_PHPCR_NamespaceException
	 * @todo Throws an AccessDeniedException if this Session does not have permission to add/remove a Namespace
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	protected function isAllowedToModifyNamespace($prefix) {
		if (array_key_exists($prefix, $this->builtInNamespaces)) {
			return FALSE;
		}

		if (F3_PHP6_Functions::strtolower(F3_PHP6_Functions::substr($prefix, 0,3)) == 'xml') {
			return FALSE;
		}

		return TRUE;
	}

}

?>
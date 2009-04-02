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
 * @package TYPO3CR
 * @version $Id$
 */

/**
 * A NamespaceRegistry
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class NamespaceRegistry implements \F3\PHPCR\NamespaceRegistryInterface {

	/**
	 * @var array
	 */
	protected $builtInNamespaces = array(
		self::PREFIX_JCR => self::NAMESPACE_JCR,
		self::PREFIX_NT => self::NAMESPACE_NT,
		self::PREFIX_MIX => self::NAMESPACE_MIX,
		self::PREFIX_XML => self::NAMESPACE_XML,
		self::PREFIX_EMPTY => self::NAMESPACE_EMPTY
	);

	/**
	 * @var array
	 */
	protected $customNamespaces = array();

	/**
	 * Constructs a NamespaceRegistry object
	 *
	 * @param \F3\TYPO3CR\Storage\BackendInterface $storageBackend
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\TYPO3CR\Storage\BackendInterface $storageBackend) {
		$this->storageBackend = $storageBackend;
	}

	/**
	 * Loads the custom namespaces from the persistent storage
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function initializeObject() {
		$rawNamespaces = $this->storageBackend->getRawNamespaces();
		if (!count($rawNamespaces))	return;
		foreach ($rawNamespaces as $rawNamespace) {
			if (!array_key_exists($rawNamespace['prefix'], $this->builtInNamespaces)) {
				$this->customNamespaces[$rawNamespace['prefix']] = $rawNamespace['uri'];
			}
		}
	}

	/**
	 * Returns an array holding all currently registered prefixes.
	 * Warning: All methods that use namespace mappings have to use
	 * Session::getNamespacePrefixes instead to ensure that "Session namespace
	 * mappings" work.
	 *
	 * @return array
	 * @throws \F3\PHPCR\RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPrefixes() {
		return array_merge(
			array_keys($this->builtInNamespaces),
			array_keys($this->customNamespaces)
		);
	}

	/**
	 * Returns an array holding all currently registered URIs.
	 *
	 * @return array
	 * @throws \F3\PHPCR\RepositoryException
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
	 * @throws \F3\PHPCR\NamespaceException
	 * @throws \F3\PHPCR\RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPrefix($uri) {
		$prefix = array_search($uri, $this->builtInNamespaces);
		if ($prefix === FALSE) {
			$prefix = array_search($uri, $this->customNamespaces);
			if ($prefix === FALSE) {
				throw new \F3\PHPCR\NamespaceException('URI ' . $uri . ' not registered in NamespaceRegistry', 1184478139);
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
	 *
	 * @param string $prefix
	 * @return string
	 * @throws \F3\PHPCR\NamespaceException
	 * @throws \F3\PHPCR\RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getURI($prefix) {
		if (isset($this->builtInNamespaces[$prefix])) {
			return $this->builtInNamespaces[$prefix];
		} elseif (isset($this->customNamespaces[$prefix])) {
			return $this->customNamespaces[$prefix];
		} else {
			throw new \F3\PHPCR\NamespaceException('Prefix ' . $prefix . ' not registered in NamespaceRegistry', 1184478140);
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
	 * @throws \F3\PHPCR\NamespaceException
	 * @throws \F3\PHPCR\UnsupportedRepositoryOperationException
	 * @throws \F3\PHPCR\AccessDeniedException
	 * @throws \F3\PHPCR\RepositoryException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerNamespace($prefix, $uri) {
		if (!$this->isAllowedToModifyNamespace($prefix)) {
			throw new \F3\PHPCR\NamespaceException('Attempt to register a protected namespace!', 1184478152);
		}

			// Update a namespace
		if (in_array($uri, $this->customNamespaces)) {
			$this->storageBackend->updateNamespacePrefix($prefix, $uri);
			$prefixToRemove = array_search($uri, $this->customNamespaces);
			unset($this->customNamespaces[$prefixToRemove]);
			$this->customNamespaces[$prefix] = $uri;
		} elseif (in_array($prefix, array_keys($this->customNamespaces))) {
			$this->storageBackend->updateNamespaceURI($prefix, $uri);
			$this->customNamespaces[$prefix] = $uri;
		} else {
			$this->storageBackend->addNamespace($prefix, $uri);
			$this->customNamespaces[$prefix] = $uri;
		}
	}

	/**
	 * Removes a namespace mapping from the registry.
	 *
	 * @param string $prefix
	 * @return void
	 * @throws \F3\PHPCR\NamespaceException
	 * @throws \F3\PHPCR\UnsupportedRepositoryOperationException
	 * @throws \F3\PHPCR\AccessDeniedException
	 * @throws \F3\PHPCR\RepositoryException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function unregisterNamespace($prefix) {
		if (!$this->isAllowedToModifyNamespace($prefix)) {
			throw new \F3\PHPCR\NamespaceException('Attempt to unregister a protected namespace!', 1184478149);
		}

		if (!array_key_exists($prefix, $this->customNamespaces)) {
			throw new \F3\PHPCR\NamespaceException("Attempt to unregister a not registered namespace!", 1184479159);
		}
		$this->storageBackend->deleteNamespace($prefix);
		unset ($this->customNamespaces[$prefix]);
	}

	/**
	 * Are we allowed to register/unregister a workspace?
	 *
	 * @param string $prefix Prefix of namespace
	 * @return boolean TRUE if we are allowed to modify the namespace
	 * @throws \F3\PHPCR\NamespaceException
	 * @todo Throws an AccessDeniedException if this Session does not have permission to add/remove a Namespace
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function isAllowedToModifyNamespace($prefix) {
		if (array_key_exists($prefix, $this->builtInNamespaces)) {
			return FALSE;
		}

		if (strtolower(substr($prefix, 0,3)) == 'xml') {
			return FALSE;
		}

		return TRUE;
	}

}

?>
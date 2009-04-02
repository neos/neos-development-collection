<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Storage;

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
 * @subpackage Storage
 * @version $Id$
 */

/**
 * An abstract storage backend
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
abstract class AbstractBackend implements \F3\TYPO3CR\Storage\BackendInterface {

	/**
	 * @var string Name of the current workspace
	 */
	protected $workspaceName = 'default';

	/**
	 * @var \F3\TYPO3CR\Storage\SearchInterface
	 */
	protected $searchBackend;

	/**
	 * @var \F3\TYPO3CR\NamespaceRegistryInterface
	 */
	protected $namespaceRegistry;

	/**
	 * Constructs this backend
	 *
	 * @param mixed $options Configuration options - depends on the actual backend
	 */
	public function __construct($options = array()) {
		foreach ($options as $optionKey => $optionValue) {
			$methodName = 'set' . ucfirst($optionKey);
			if (method_exists($this, $methodName)) {
				$this->$methodName($optionValue);
			}
		}
	}

	/**
	 * Sets the name of the current workspace
	 *
	 * @param  string $workspaceName Name of the workspace which should be used for all storage operations
	 * @return void
	 * @throws \InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setWorkspaceName($workspaceName) {
		if ($workspaceName === '' || !is_string($workspaceName)) throw new \InvalidArgumentException('"' . $workspaceName . '" is not a valid workspace name.', 1200614989);
		$this->workspaceName = $workspaceName;
		$this->searchBackend->setWorkspaceName($workspaceName);
	}

	/**
	 * Sets the search backend used by the storage backend.
	 *
	 * @param \F3\TYPO3CR\Storage\SearchInterface $searchBackend
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setSearchBackend(\F3\TYPO3CR\Storage\SearchInterface $searchBackend) {
		$this->searchBackend = $searchBackend;
		$this->searchBackend->connect();
	}

	/**
	 * Returns the search backend used by the storage backend.
	 *
	 * @return \F3\TYPO3CR\Storage\SearchInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSearchBackend() {
		return $this->searchBackend;
	}

	/**
	 * Sets the namespace registry used by the storage backend
	 *
	 * @param \F3\PHPCR\NamespaceRegistryInterface $namespaceRegistry
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function setNamespaceRegistry(\F3\PHPCR\NamespaceRegistryInterface $namespaceRegistry) {
		$this->namespaceRegistry = $namespaceRegistry;
	}


}
?>
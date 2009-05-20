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
 * An abstract indexing/search backend
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
abstract class AbstractSearch implements \F3\TYPO3CR\Storage\SearchInterface {

	/**
	 * @var string Name of the current workspace
	 */
	protected $workspaceName = 'default';

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
		if (is_array($options) || $options instanceof ArrayAccess) {
			foreach ($options as $optionKey => $optionValue) {
				$methodName = 'set' . ucfirst($optionKey);
				if (method_exists($this, $methodName)) {
					$this->$methodName($optionValue);
				}
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
		if ($workspaceName == '' || !is_string($workspaceName)) throw new \InvalidArgumentException('"' . $workspaceName . '" is not a valid workspace name.', 1218961735);
		$this->workspaceName = $workspaceName;
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

	/**
	 * Performs any needed initialization before the search backend can be used
	 *
	 * @return void
	 */
	public function connect() {}

	/**
	 * Performs any needed cleanup before the search backend can be discarded
	 *
	 * @return void
	 */
	public function disconnect() {}

	/**
	 * Takes the given array of a namespace URI (key 'namespaceURI' in the array) and name (key 'name') and converts it to a prefixed name
	 *
	 * @param array $namespacedName key 'namespaceURI' for the namespace, 'name' for the local name
	 * @return string
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function prefixName($namespacedName) {
		if (! $namespacedName['namespaceURI']) {
			return $namespacedName['name'];
		}

		if ($this->namespaceRegistry) {
			return $this->namespaceRegistry->getPrefix($namespacedName['namespaceURI']) . ':' . $namespacedName['name'];
		} else {
				// Fall back to namespaces table when no namespace registry is available
			$statementHandle = $this->databaseHandle->prepare('SELECT "prefix" FROM "namespaces" WHERE "uri"=?');
			$statementHandle->execute(array($namespacedName['namespaceURI']));
			$namespaces = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);

			if (count($namespaces) != 1) {
					// TODO: throw exception instead of returning once namespace table is properly filled
				return $namespacedName['name'];
			}

			foreach ($namespaces as $namespace) {
				return $namespace['prefix'] . ':' . $namespacedName['name'];
			}
		}
	}

	/**
	 * Splits the given name string into a namespace URI (using the namespaces table) and a name
	 *
	 * @param string $prefixedName the name in prefixed notation (':' between prefix if one exists and name, no ':' in string if there is no prefix)
	 * @return array (key "namespaceURI" for the namespace, "name" for the name)
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function splitName($prefixedName) {
		$split = explode(':', $prefixedName, 2);

		if (count($split) != 2) {
			return array('namespaceURI' => '', 'name' => $prefixedName);
		}

		$namespacePrefix = $split[0];
		$name = $split[1];

		return array('namespaceURI' => $this->namespaceRegistry->getURI($namespacePrefix), 'name' => $name);
	}

}
?>
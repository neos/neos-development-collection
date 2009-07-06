<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\FLOW3\Persistence;

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
 * @subpackage FLOW3
 * @version $Id$
 */

/**
 * An identity mapper to map nodes to objects
 *
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see \F3\TYPO3CR\FLOW3\Persistence\DataMapper, \F3\TYPO3CR\FLOW3\Persistence\Backend
 */
class IdentityMap {

	/**
	 * @var \SplObjectStorage
	 */
	protected $objectMap;

	/**
	 * @var array
	 */
	protected $uuidMap = array();

	/**
	 * Constructs a new Identity Map
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct() {
		$this->objectMap = new \SplObjectStorage();
	}

	/**
	 * Checks whether the given object is known to the identity map
	 *
	 * @param object $object
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasObject($object) {
		return $this->objectMap->contains($object);
	}

	/**
	 * Checks whether the given UUID is known to the identity map
	 *
	 * @param string $uuid
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasUUID($uuid) {
		return array_key_exists($uuid, $this->uuidMap);
	}

	/**
	 * Returns the object for the given UUID
	 *
	 * @param string $uuid
	 * @return object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectByUUID($uuid) {
		return $this->uuidMap[$uuid];
	}

	/**
	 * Returns the node identifier for the given object
	 *
	 * @param object $object
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getUUIDByObject($object) {
		if (!is_object($object)) throw new \InvalidArgumentException('Object expected, ' . gettype($object) . ' given.', 1246892972);
		if (!isset($this->objectMap[$object])) {
			throw new \F3\TYPO3CR\FLOW3\Persistence\Exception\UnknownObjectException('The given object (class: ' . get_class($object) . ') is not registered in this Identity Map.', 1246892970);
		}
		return $this->objectMap[$object];
	}

	/**
	 * Register a node identifier for an object
	 *
	 * @param object $object
	 * @param string $uuid
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerObject($object, $uuid) {
		$this->objectMap[$object] = $uuid;
		$this->uuidMap[$uuid] = $object;
	}

	/**
	 * Unregister an object
	 *
	 * @param string $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterObject($object) {
		unset($this->uuidMap[$this->objectMap[$object]]);
		$this->objectMap->detach($object);
	}

}

?>
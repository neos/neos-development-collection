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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @see \F3\TYPO3CR\FLOW3\Persistence\DataMapper, \F3\TYPO3CR\FLOW3\Persistence\Backend
 */
class IdentityMap {

	/**
	 * @var \SplObjectStorage
	 */
	protected $identityMap;

	/**
	 * Constructs a new Identity Map
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct() {
		$this->identityMap = new \SplObjectStorage();
	}

	/**
	 * Checks whether the given object is known to the identity map
	 *
	 * @param object $object
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasObject($object) {
		return $this->identityMap->contains($object);
	}

	/**
	 * Returns the (node) identifier for the given object
	 *
	 * @param object $object
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifier($object) {
		return $this->identityMap[$object];
	}

	/**
	 * Register a (node) identifier for an object
	 *
	 * @param object $object
	 * @param string $identifier
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerObject($object, $identifier) {
		$this->identityMap[$object] = $identifier;
	}

	/**
	 * Unregister an object
	 *
	 * @param string $object
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterObject($object) {
		$this->identityMap->detach($object);
	}

}

?>
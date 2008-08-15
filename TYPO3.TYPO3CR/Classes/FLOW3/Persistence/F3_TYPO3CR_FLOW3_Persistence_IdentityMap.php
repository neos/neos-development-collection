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
 * @subpackage FLOW3
 * @version $Id$
 */

/**
 * An identity mapper to map nodes to objects
 *
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @see F3_TYPO3CR_FLOW3_Persistence_DataMapper, F3_TYPO3CR_FLOW3_Persistence_Backend
 */
class F3_TYPO3CR_FLOW3_Persistence_IdentityMap {

	/**
	 * @var array
	 */
	protected $identityMap = array();

	/**
	 * Checks whether the given (object) has is known to the identity map
	 *
	 * @param string $hash
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasObject($hash) {
		return key_exists($hash, $this->identityMap);
	}

	/**
	 * Returns the (node) identifier for the given (object) hash
	 *
	 * @param string $hash
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifier($hash) {
		return $this->identityMap[$hash];
	}

	/**
	 * Register a (node) identifier for an (object) hash
	 *
	 * @param string $identifier
	 * @param string $hash
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function registerObject($hash, $identifier) {
		$this->identityMap[$hash] = $identifier;
	}

}

?>
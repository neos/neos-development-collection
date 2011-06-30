<?php
namespace TYPO3\TYPO3\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\Party\Domain\Model\Person;

/**
 * Domain Model of a User
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @entity
 * @scope prototype
 */
class User extends Person  {

	/**
	 * Preferences of this user
	 *
	 * @var \TYPO3\TYPO3\Domain\Model\UserPreferences
	 * @OneToOne(cascade={"all"})
	 */
	protected $preferences;

	/**
	 * Constructs this User object
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		parent::__construct();
		$this->preferences = new UserPreferences();
	}

	/**
	 * @return \TYPO3\TYPO3\Domain\Model\UserPreferences
	 */
	public function getPreferences() {
		return $this->preferences;
	}

}
?>
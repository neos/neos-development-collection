<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript;

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

/**
 * A TypoScript Menu object
 *
 * @version $Id: Text.php 4448 2010-06-07 13:24:31Z robert $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Menu extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/TypoScript/Templates/Menu.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('items');

	/**
	 * The first navigation level which should be rendered.
	 *
	 * 0 = top level of the site
	 * 
	 * @var integer
	 */
	protected $firstLevel = 0;

	/**
	 * The last navigation level which should be rendered.
	 *
	 * 0 = top level of the site
	 * 1 = first sub level (2nd level)
	 * 2 = second sub level (3rd level)
	 * ...
	 *
	 * -1 = last level
	 * -2 = level above the last level
	 * ...
	 *
	 * @var integer
	 */
	protected $lastLevel = 2;

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * Sets the first level
	 *
	 * @param integer $firstLevel
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFirstLevel($firstLevel) {
		$this->firstLevel = $firstLevel;
	}

	/**
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFirstLevel() {
		return $this->firstLevel;
	}

	/**
	 * Sets the last level
	 *
	 * @param integer $lastLevel
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setLastLevel($lastLevel) {
		$this->lastLevel = $lastLevel;
	}

	/**
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLastLevel($lastLevel) {
		return $this->lastLevel;
	}

	/**
	 * Returns the menu items according to the defined settings.
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getItems() {
		if ($this->items === NULL) {
			$this->items = $this->buildItems();
		}
     return $this->items;
   }

	/**
	 * Builds the array of menu items containing those items which match the
	 * configuration set for this Menu object.
	 *
	 * @return array An array of menu items and further information
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildItems() {
		return array(
			''
		);
	}
}
?>
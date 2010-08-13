<?php
declare(ENCODING = 'utf-8');
namespace F3\TypoScript\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript"                  *
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
 * A TypoScript object fixture
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class ObjectWithArrayProperty extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @var array
	 */
	protected $theArray = array();


	/**
	 * @return array
	 */
	public function getTheArray() {
      return $this->theArray;
   }


	/**
	 * @param array $theArray
	 * @return void
	 */
  public function setTheArray(array $theArray) {
      $this->theArray = $theArray;
   }
}

?>
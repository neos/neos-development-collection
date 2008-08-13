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
 * @version $Id:$
 */

/**
 * A set of helpers for the TYPO3CR
 *
 * @package TYPO3CR
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Utility {

	/**
	 * Removes all NULL values from an array.
	 *
	 * @param array $value
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public static function removeNullFromArray(array $array) {
		foreach ($array as $key => $element) {
			if (is_array($element)) {
				$array[$key] = self::removeNullFromArray($element);
			}
			if ($element === NULL) {
				unset($array[$key]);
			}
		}
		return $array;
	}
}

?>
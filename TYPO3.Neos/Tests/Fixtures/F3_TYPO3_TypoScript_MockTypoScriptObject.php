<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::TypoScript;

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
 * @package
 * @subpackage
 * @version $Id:$
 */
/**
 *
 *
 * @package
 * @subpackage
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class MockTypoScriptObject {

	protected $value;

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public function __toString() {
		return (string)$this->value;
	}
}
?>
<?php
namespace TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A mock for a TypoScript object
 *
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
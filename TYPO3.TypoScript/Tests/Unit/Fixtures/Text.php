<?php
namespace TYPO3\TypoScript\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript"                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A TypoScript Text object fixture
 *
 * @scope prototype
 */
class Text extends \TYPO3\TypoScript\AbstractContentObject {

	protected $value;

	/**
	 * @param $value
	 * @return void
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return mixed
	 */
	public function render() {
		return $this->value;
	}
}

?>
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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A TypoScript Page Object fixture
 *
 * @FLOW3\Scope("prototype")
 */
class Page extends \TYPO3\TypoScript\AbstractContentArrayObject {

	/**
	 * @return mixed
	 */
	public function render() {
		return $this->renderArray();
	}
}

?>
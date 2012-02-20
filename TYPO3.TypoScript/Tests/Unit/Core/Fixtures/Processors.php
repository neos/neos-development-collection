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
 * Processors for use in the Parser tests
 *
 * @FLOW3\Scope("prototype")
 */
class Processors implements \TYPO3\TypoScript\ProcessorInterface {
	public function process($subject) {
		return 'foo';
	}
}

?>
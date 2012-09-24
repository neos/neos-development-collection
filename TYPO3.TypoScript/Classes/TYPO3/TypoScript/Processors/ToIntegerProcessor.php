<?php
namespace TYPO3\TypoScript\Processors;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TypoScript".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Processor that onverts the subject into an integer.
 *
 */
class ToIntegerProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * Converts the subject into an integer.
	 *
	 * @param string $subject The string to be processed
	 * @return integer The casted integer value
	 */
	public function process($subject) {
		return intval((string)$subject);
	}
}
?>
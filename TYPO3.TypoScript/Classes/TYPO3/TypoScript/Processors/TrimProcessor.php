<?php
namespace TYPO3\TypoScript\Processors;

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
 * Processor that trims the current subject (Removes whitespaces around the value).
 *
 */
class TrimProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * Trims the current subject (Removes whitespaces around the value).
	 *
	 * @param string $subject The string to be processed
	 * @return string The processed string
	 */
	public function process($subject) {
		return trim((string)$subject);
	}
}
?>
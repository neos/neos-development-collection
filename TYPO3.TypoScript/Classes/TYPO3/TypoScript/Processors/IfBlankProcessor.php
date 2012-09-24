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
 * Processor that overrides the current subject with the given value, if the subject (not trimmed) is empty.
 *
 */
class IfBlankProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * The value that overrides the subject
	 * @var string
	 */
	protected $replacement = '';

	/**
	 * @param string $replacement The value that overrides the subject
	 * @return void
	 */
	public function setReplacement($replacement) {
		$this->replacement = $replacement;
	}

	/**
	 * @return string The value that overrides the subject
	 */
	public function getReplacement() {
		return $this->replacement;
	}

	/**
	 * Overrides the current subject with the given value, if the subject (not trimmed) is empty.
	 *
	 * @param string $subject The string to be processed
	 * @return string The processed string
	 */
	public function process($subject) {
		return (!\TYPO3\FLOW3\Utility\Unicode\Functions::strlen((string)$subject)) ? $this->replacement : $subject;
	}
}
?>
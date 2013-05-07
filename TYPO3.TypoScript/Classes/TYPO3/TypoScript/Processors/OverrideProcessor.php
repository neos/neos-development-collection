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
 * Overrides the subject with the given value, if the value is not empty.
 *
 */
class OverrideProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * @var string
	 */
	protected $replacement = '';

	/**
	 * The value that overrides the subject.
	 *
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
	 * Overrides the subject with the given replacement, if the replacement is not empty.
	 *
	 * @param string $subject The string to be processed
	 * @return string The processed string
	 */
	public function process($subject) {
		$trimmedReplacement = trim((string)$this->replacement);
		$replacementIsEmpty = $trimmedReplacement === '' || $trimmedReplacement === '0';
		return $replacementIsEmpty ? $subject : $this->replacement;
	}
}
?>
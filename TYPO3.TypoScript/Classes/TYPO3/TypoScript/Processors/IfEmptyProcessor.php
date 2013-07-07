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

use TYPO3\Flow\Annotations as Flow;

/**
 * Overrides the subject with the given value, if the subject (trimmed) is empty.
 *
 */
class IfEmptyProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * @var string
	 */
	protected $replacement = '';

	/**
	 * The replacement to override the subject.
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
	 * Overrides the subject with the given value, if the subject (trimmed) is empty.
	 *
	 * @param string $subject The string to be processed
	 * @return string The processed string
	 */
	public function process($subject) {
		$subjectIsEmpty = (trim((string)$subject) === '' || trim((string)$subject) === '0');
		return $subjectIsEmpty ? $this->replacement : $subject;
	}
}
?>
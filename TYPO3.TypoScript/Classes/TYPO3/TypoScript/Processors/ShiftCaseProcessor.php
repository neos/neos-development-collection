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
 * Processor that shifts the case of a string into the specified direction.
 *
 */
class ShiftCaseProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	const SHIFT_CASE_TO_UPPER = 'upper';
	const SHIFT_CASE_TO_LOWER = 'lower';
	const SHIFT_CASE_TO_TITLE = 'title';

	/**
	 * One of the SHIFT_CASE_* constants
	 * @var string
	 */
	protected $direction;

	/**
	 * @param string $direction Direction to shift case in, one of SHIFT_CASE_TO_*
	 * @return void
	 */
	public function setDirection($direction) {
		$this->direction = $direction;
	}

	/**
	 * @return string Direction to shift case in, one of SHIFT_CASE_TO_*
	 */
	public function getDirection() {
		return $this->direction;
	}

	/**
	 * Shifts the case of a string into the specified direction.
	 *
	 * @param string $subject the string to be processed
	 * @return string The processed string
	 * @throws \TYPO3\TypoScript\Exception
	 */
	public function process($subject) {
		switch ($this->direction) {
			case self::SHIFT_CASE_TO_LOWER :
				$processedSubject = \TYPO3\Flow\Utility\Unicode\Functions::strtolower($subject);
				break;
			case self::SHIFT_CASE_TO_UPPER :
				$processedSubject = \TYPO3\Flow\Utility\Unicode\Functions::strtoupper($subject);
				break;
			case self::SHIFT_CASE_TO_TITLE :
				$processedSubject = \TYPO3\Flow\Utility\Unicode\Functions::strtotitle($subject);
				break;
			default:
				throw new \TYPO3\TypoScript\Exception('Invalid direction specified for case shift. Please use one of the SHIFT_CASE_* constants.', 1179399480);
		}
		return $processedSubject;
	}
}
?>
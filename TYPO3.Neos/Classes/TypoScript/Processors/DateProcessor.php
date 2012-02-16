<?php
namespace TYPO3\TYPO3\TypoScript\Processors;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Processor that transforms an UNIX timestamp according to the given format.
 * For the possible format values, look at the php date() function.
 *
 */
class DateProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * A format string, according to the rules of the php date() function
	 * @var string
	 */
	protected $format;

	/**
	 * @param string $format format string, according to the rules of the php date() function
	 * @return void
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	/**
	 * @return string $format format string, according to the rules of the php date() function
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Transforms an UNIX timestamp according to the given format.
	 * For the possible format values, look at the php date() function.
	 *
	 * @param string $subject The UNIX timestamp to transform
	 * @return string The processed string
	 */
	public function process($subject) {
		if ($subject === '') {
			return '';
		}

		$timestamp = is_object($subject) ? (string)$subject : $subject;
		$format = (string)$this->format;
		if ($timestamp <= 0) throw new \TYPO3\TypoScript\Exception('The given timestamp value was zero or negative, sorry this is not allowed.', 1185282371);

		return date($format, $timestamp);
	}
}
?>

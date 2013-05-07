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
 * Rounds the subject if it is a float value. If an integer is given, nothing happens.
 *
 */
class RoundProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * @var integer
	 */
	protected $precision = NULL;

	/**
	 * The number of digits after the decimal point. Negative values are also supported (-1 rounds to full 10ths).
	 *
	 * @param integer $precision
	 * @return void
	 */
	public function setPrecision($precision) {
		$this->precision = $precision;
	}

	/**
	 * The number of digits after the decimal point. Negative values are also supported (-1 rounds to full 10ths).
	 *
	 * @return integer
	 */
	public function getPrecision() {
		return $this->precision;
	}

	/**
	 * Rounds a given float value. If an integer is given, nothing happens.
	 *
	 * @param float/string $subject The subject to round.
	 * @return float Rounded value
	 * @throws \TYPO3\TypoScript\Exception
	 */
	public function process($subject) {
		if (!is_numeric($subject)) {
			throw new \TYPO3\TypoScript\Exception('Expected an integer or float passed, ' . gettype($subject) . ' given.', 1224053300);
		}
		$subject = floatval($subject);
		if ($this->precision != NULL && !is_int($this->precision)) {
			throw new \TYPO3\TypoScript\Exception('Precision must be an integer.');
		}
		return round($subject, $this->precision);
	}
}
?>
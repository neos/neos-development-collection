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
 * Processor that rounds a given float value.
 * If integer given, nothing happens.
 *
 */
class RoundProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * Number of digits after the decimal point. Negative values are also supported. (-1 rounds to full 10ths)
	 * @var integer
	 */
	protected $precision = NULL;

	/**
	 * @param integer $precision the number of digits after the decimal point. Negative values are also supported. (-1 rounds to full 10ths)
	 * @return void
	 */
	public function setPrecision($precision) {
		$this->precision = $precision;
	}

	/**
	 * @return integer the number of digits after the decimal point. Negative values are also supported. (-1 rounds to full 10ths)
	 */
	public function getPrecision() {
		return $this->precision;
	}

	/**
	 * Rounds a given float value. If integer given, nothing happens.
	 *
	 * @param float/string $subject The subject to round.
	 * @return float Rounded value
	 * @throws \TYPO3\TypoScript\Exception
	 */
	public function process($subject) {
		if (!is_numeric($subject)) throw new \TYPO3\TypoScript\Exception('Expected an integer or float passed, ' . gettype($subject) . ' given.', 1224053300);
		$subject = floatval($subject);
		if ($this->precision != NULL && !is_int($this->precision)) throw new \TYPO3\TypoScript\Exception('Precision must be an integer.');
		return round($subject, $this->precision);
	}
}
?>
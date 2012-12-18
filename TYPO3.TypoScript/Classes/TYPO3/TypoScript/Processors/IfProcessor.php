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
 * Processor that Returns the trueValue when the condition evaluates to TRUE,
 * otherwise the falseValue is returned.
 *
 */
class IfProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * The condition for the if clause, or simply TRUE/FALSE
	 * @var boolean
	 */
	protected $condition;

	/**
	 * This is returned if $this->condition is TRUE
	 * @var string
	 */
	protected $trueValue = '';

	/**
	 * This is returned if $this->condition is FALSE
	 * @var string
	 */
	protected $falseValue = '';

	/**
	 * @param boolean $condition the condition for the if clause, or simply TRUE/FALSE
	 * @return void
	 */
	public function setCondition($condition) {
		$this->condition = $condition;
	}

	/**
	 * @return boolean the condition for the if clause, or simply TRUE/FALSE
	 */
	public function getCondition() {
		return $this->condition;
	}

	/**
	 * @param string $trueValue the string that is returned if $this->condition is TRUE
	 * @return void
	 */
	public function setTrueValue($trueValue) {
		$this->trueValue = $trueValue;
	}

	/**
	 * @return string the string that is returned if $this->condition is TRUE
	 */
	public function getTrueValue() {
		return $this->trueValue;
	}

	/**
	 * @param string $falseValue the string that is returned if $this->condition is FALSE
	 * @return void
	 */
	public function setFalseValue($falseValue) {
		$this->falseValue = $falseValue;
	}

	/**
	 * @return string the string that is returned if $this->condition is FALSE
	 */
	public function getFalseValue() {
		return $this->falseValue;
	}

	/**
	 * Returns the trueValue when the condition evaluates to TRUE, otherwise
	 * the falseValue is returned.
	 *
	 * If the condition is a TypoScript object, it is handled as a string.
	 *
	 * The following conditions are considered TRUE:
	 *
	 *   - boolean TRUE
	 *   - number > 0
	 *   - non-empty string
	 *
	 * While these conditions evaluate to FALSE:
	 *
	 *   - boolean FALSE
	 *   - number <= 0
	 *   - empty string
	 *
	 * @param string $subject Not used in this processor
	 * @return string The calculated return value. Either $this->trueValue or $this->falseValue
	 * @throws \TYPO3\TypoScript\Exception
	 */
	public function process($subject) {
		$condition = $this->condition;
		if (!is_bool($condition)) {
			if (is_object($condition)) {
				$condition = (string)$condition;
			}
			if ((is_numeric($condition) && $condition <= 0) || $condition === '') {
				$condition = FALSE;
			}
			if ($condition === 1 || (is_string($condition) && \TYPO3\Flow\Utility\Unicode\Functions::strlen($condition) > 0)) {
				$condition = TRUE;
			}
		}
		if (!is_bool($condition)) {
			throw new \TYPO3\TypoScript\Exception('The condition in the if processor could not be converted to boolean. Got: (' . gettype($condition) . ')' . (string)$condition, 1185355020);
		}
		return $condition ? $this->trueValue : $this->falseValue;
	}
}
?>
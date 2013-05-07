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
 * Wraps the specified string into a prefix and a suffix string.
 *
 */
class WrapProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * The string to prepend
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * The string to append
	 * @var string
	 */
	protected $suffix = '';

	/**
	 * The string to prepend.
	 *
	 * @param string $prefix a string to be prepended
	 * @return void
	 */
	public function setPrefix($prefix) {
		$this->prefix = $prefix;
	}

	/**
	 * @return string the string which is to be prepended to the subject
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * The string to append.
	 *
	 * @param string $suffix a string to be appended
	 * @return void
	 */
	public function setSuffix($suffix) {
		$this->suffix = $suffix;
	}

	/**
	 * @return string the string which is to be appended to the subject
	 */
	public function getSuffix() {
		return $this->suffix;
	}

	/**
	 * Wraps the specified string into a prefix- and a suffix string.
	 *
	 * @param string $subject the string to be wrapped
	 * @return string The processed string
	 */
	public function process($subject) {
		return $this->prefix . $subject . $this->suffix;
	}
}
?>
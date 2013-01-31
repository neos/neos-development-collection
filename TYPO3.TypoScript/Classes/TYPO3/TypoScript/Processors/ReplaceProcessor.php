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
 * Processor that replaces a part of the given subject with something else
 *
 */
class ReplaceProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * The string to search for
	 *
	 * @var string
	 */
	protected $search = '';

	/**
	 * The string to replace with
	 *
	 * @var string
	 */
	protected $replace = '';

	/**
	 * @param string $search
	 * @return void
	 */
	public function setSearch($search) {
		$this->search = $search;
	}

	/**
	 * @return string
	 */
	public function getSearch() {
		return $this->search;
	}

	/**
	 * @param string $replace
	 * @return void
	 */
	public function setReplace($replace) {
		$this->replace = $replace;
	}

	/**
	 * @return string
	 */
	public function getReplace() {
		return $this->replace;
	}

	/**
	 * Replaces occurrences of $this->search with $this->replace in the given subject.
	 *
	 * @param string $subject The string to be processed
	 * @return string The processed string
	 */
	public function process($subject) {
		return str_replace($this->search, $this->replace, $subject);
	}
}

?>
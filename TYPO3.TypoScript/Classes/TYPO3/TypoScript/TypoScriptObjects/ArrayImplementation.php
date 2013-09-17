<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

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
use TYPO3\Flow\Utility\ArraySorter;

/**
 * The old "COA" object
 */
class ArrayImplementation extends AbstractTypoScriptObject implements \ArrayAccess {

	/**
	 * @Flow\Inject
	 * @var ArraySorter
	 */
	protected $arraySorter;

	/**
	 * Sub-typoscript elements of this object
	 *
	 * @var array
	 */
	protected $subElements = array();

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function evaluate() {
		$sortedSubElements = $this->arraySorter->sortArray($this->subElements, '__meta.position');

		if (count($sortedSubElements) === 0) {
			return NULL;
		}

		$output = '';
		foreach (array_keys($sortedSubElements) as $key) {
			$output .= $this->tsRuntime->render($this->path . '/' . $key);
		}

		return $output;
	}

	/**
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->subElements[$offset] = $value;
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
	}
}
?>
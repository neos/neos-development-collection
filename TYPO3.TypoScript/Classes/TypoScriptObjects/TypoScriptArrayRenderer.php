<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The old "COA" object
 */
class TypoScriptArrayRenderer extends AbstractTsObject implements \ArrayAccess {

	/**
	 * Sub-typoscript element keys.
	 *
	 * We only need the keys here, because the rendering is done by using the
	 * tsRuntime.
	 *
	 * @var array
	 */
	protected $subKeys = array();

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function evaluate() {
		sort($this->subKeys, SORT_NUMERIC);

		$output = '';
		foreach ($this->subKeys as $key) {
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
		$this->subKeys[$offset] = $offset;
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
	}
}
?>
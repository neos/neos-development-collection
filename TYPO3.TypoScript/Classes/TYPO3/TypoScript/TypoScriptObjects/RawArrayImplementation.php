<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Evaluate sub objects to an array (instead of a string as ArrayImplementation does)
 */
class RawArrayImplementation extends ArrayImplementation {

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public function evaluate() {
		$sortedChildTypoScriptKeys = $this->sortNestedTypoScriptKeys();

		if (count($sortedChildTypoScriptKeys) === 0) {
			return array();
		}

		$output = array();
		foreach ($sortedChildTypoScriptKeys as $key) {
			$output[$key] = $this->tsValue($key);
		}

		return $output;
	}

}

<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects\Fixtures;

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

/**
 * Renderer which wraps the nested TS object found at "value" with "prepend" and "append".
 *
 * Needed for more complex prototype inheritance chain testing.
 */
class WrappedNestedObjectRenderer extends \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject {

	/**
	 * @var string
	 */
	protected $prepend;

	/**
	 * @var string
	 */
	protected $append;

	/**
	 * @param string $prepend
	 */
	public function setPrepend($prepend) {
		$this->prepend = $prepend;
	}

	/**
	 * @param string $append
	 */
	public function setAppend($append) {
		$this->append = $append;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function evaluate() {
		return $this->tsValue('prepend') . $this->tsRuntime->evaluate($this->path . '/value') . $this->tsValue('append');
	}
}
?>
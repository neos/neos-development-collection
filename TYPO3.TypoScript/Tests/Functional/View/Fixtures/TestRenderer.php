<?php
namespace TYPO3\TypoScript\Tests\Functional\View\Fixtures;

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
 * Test renderer
 */
class TestRenderer extends \TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject {

	protected $test;

	public function setTest($test) {
		$this->test = $test;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function evaluate() {
		return 'X' . $this->tsValue('test');
	}
}

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
 * Testcase for the TypoScript ReplaceProcessor
 *
 */
class ReplaceProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\ReplaceProcessor
	 */
	protected $replaceProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->replaceProcessor = new \TYPO3\TypoScript\Processors\ReplaceProcessor();
	}

	/**
	 * Checks if the replace() processor works
	 *
	 * @test
	 */
	public function replaceRelacesStrings() {
		$subject = 'I am unchanged';
		$expectedResult = 'I am changed now';

		$this->replaceProcessor->setSearch('unchanged');
		$this->replaceProcessor->setReplace('changed now');
		$result = $this->replaceProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "replace" did not return the expected result.');
	}

}
?>

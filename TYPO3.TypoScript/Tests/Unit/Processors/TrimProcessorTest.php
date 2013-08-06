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
 * Testcase for the TypoScript TrimProcessor
 *
 */
class TrimProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\TrimProcessor
	 */
	protected $trimProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->trimProcessor = new \TYPO3\TypoScript\Processors\TrimProcessor();
	}

	/**
	 * Checks if the trim() processor basically works
	 *
	 * @test
	 */
	public function trimBasicallyWorks() {
		$subject = '  I am not trimmed     ';
		$expectedResult = 'I am not trimmed';
		$result = $this->trimProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "trim" did not return the expected result.');
	}

	/**
	 * Checks if the trim() processor works with integers
	 *
	 * @test
	 */
	public function trimWorksWithIntegers() {
		$subject = 123;
		$expectedResult = '123';
		$result = $this->trimProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "trim" did not return the expected result.');
	}
}
?>

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
 * Testcase for the TypoScript WrapProcessor
 *
 */
class WrapProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\WrapProcessor
	 */
	protected $wrapProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->wrapProcessor = new \TYPO3\TypoScript\Processors\WrapProcessor();
	}

	/**
	 * Checks if the wrap() processor basically works
	 *
	 * @test
	 */
	public function wrapBasicallyWorks() {
		$subject = 'Kasper Skårhøj';
		$this->wrapProcessor->setPrefix('<strong>');
		$this->wrapProcessor->setSuffix('</strong>');
		$result = $this->wrapProcessor->process($subject);
		$expectedResult = '<strong>Kasper Skårhøj</strong>';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "wrap" did not return the expected result.');
	}

	/**
	 * Checks if the wrap() processor wraps the subject with empty strings if
	 * prefixString and suffixString are not set
	 *
	 * @test
	 */
	public function prefixAndSuffixAreEmptyByDefault() {
		$subject = 'Kasper Skårhøj';
		$result = $this->wrapProcessor->process($subject);
		$expectedResult = 'Kasper Skårhøj';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "wrap" did not return the expected result.');
	}
}
?>

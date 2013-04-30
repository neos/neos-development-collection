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
 * Testcase for the TypoScript SubstringProcessor
 *
 */
class SubstringProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\SubstringProcessor
	 */
	protected $substringProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->substringProcessor = new \TYPO3\TypoScript\Processors\SubstringProcessor();
	}

	/**
	 * @test
	 */
	public function substringWorksIfStartAndLengthHaveBeenSpecified() {
		$subject = 'Kasper Skårhøj\'s name is good to test String functions';
		$this->substringProcessor->setStart(14);
		$this->substringProcessor->setLength(10);
		$expectedResult = '\'s name is';

		$actualResult = $this->substringProcessor->process($subject);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function subjectIsNotModifiedIfNoOptionsHaveBeenSet() {
		$subject = 'Kasper Skårhøj\'s name is good to test String functions';
		$expectedResult = 'Kasper Skårhøj\'s name is good to test String functions';

		$actualResult = $this->substringProcessor->process($subject);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function substringWorksIfLengthHasNotBeenSpecified() {
		$subject = 'Kasper Skårhøj\'s name is good to test String functions';
		$this->substringProcessor->setStart(5);
		$expectedResult = 'r Skårhøj\'s name is good to test String functions';

		$actualResult = $this->substringProcessor->process($subject);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function substringWorksIfStartHasNotBeenSpecified() {
		$subject = 'Kasper Skårhøj\'s name is good to test String functions';
		$this->substringProcessor->setLength(10);
		$expectedResult = 'Kasper Skå';

		$actualResult = $this->substringProcessor->process($subject);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function passingAStringInsteadOfTheStartPositionIntoSubstringThrowsAnException() {
		$this->substringProcessor->setStart('a string');
		$this->substringProcessor->process('the subject');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function passingAStringInsteadOfTheLenghtIntoSubstringThrowsAnException() {
		$this->substringProcessor->setLength('a string');
		$this->substringProcessor->process('the subject');
	}
}
?>

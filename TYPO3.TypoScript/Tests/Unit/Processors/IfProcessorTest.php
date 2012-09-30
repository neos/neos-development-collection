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
 * Testcase for the TypoScript IfProcessor
 *
 */
class IfProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\IfProcessor
	 */
	protected $ifProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->ifProcessor = new \TYPO3\TypoScript\Processors\IfProcessor();
	}

	/**
	 * Checks if the if() processor basically works for satisfied conditions
	 *
	 * @test
	 */
	public function ifBasicallyWorksForSatisfiedConditions() {
		$subject = 'not needed here';
		$this->ifProcessor->setCondition(TRUE);
		$this->ifProcessor->setTrueValue('I am really true!');
		$this->ifProcessor->setFalseValue('I am more than just false!');

		$expectedResult = 'I am really true!';
		$result = $this->ifProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "if" did not return the expected result. (condition: TRUE)');
	}

	/**
	 * Checks if the if() processor basically works for unsatisfied conditions
	 *
	 * @test
	 */
	public function ifBasicallyWorksForUnatisfiedConditions() {
		$subject = 'not needed here';
		$this->ifProcessor->setCondition(FALSE);
		$this->ifProcessor->setTrueValue('I am really true!');
		$this->ifProcessor->setFalseValue('I am more than just false!');

		$expectedResult = 'I am more than just false!';
		$result = $this->ifProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "if" did not return the expected result. (condition: FALSE)');
	}

	/**
	 * Checks if the if() processor throws an exception on an invalid condition
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function ifThrowsExceptionOnInvalidCondition() {
		$subject = 'not needed here';
		$this->ifProcessor->setCondition(NULL);
		$this->ifProcessor->setTrueValue('I am really true!');
		$this->ifProcessor->setFalseValue('I am more than just false!');

		$this->ifProcessor->process($subject);
	}

	/**
	 * Checks if the if() processor returns an empty string by default for satisfied conditions
	 *
	 * @test
	 */
	public function trueValueIsEmptyByDefault() {
		$subject = 'not needed here';
		$this->ifProcessor->setCondition(TRUE);

		$expectedResult = '';
		$result = $this->ifProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "if" did not return an empty string although trueValue has not been specified. (condition: TRUE)');
	}

	/**
	 * Checks if the if() processor returns an empty string by default for unsatisfied conditions
	 *
	 * @test
	 */
	public function falseValueIsEmptyByDefault() {
		$subject = 'not needed here';
		$this->ifProcessor->setCondition(FALSE);

		$expectedResult = '';
		$result = $this->ifProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "if" did not return an empty string although falseValue has not been specified. (condition: FALSE)');
	}
}
?>

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
 * Testcase for the TypoScript ShiftCaseProcessor
 *
 */
class ShiftCaseProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\ShiftCaseProcessor
	 */
	protected $shiftCaseProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->shiftCaseProcessor = new \TYPO3\TypoScript\Processors\ShiftCaseProcessor();
	}

	/**
	 * Checks if the shiftCase() processor works with direction "to upper"
	 *
	 * @test
	 */
	public function shiftCaseToUpperWorks() {
		$subject = 'Kasper Skårhøj';
		$this->shiftCaseProcessor->setDirection('upper');
		$result = $this->shiftCaseProcessor->process($subject);
		$expectedResult = 'KASPER SKÅRHØJ';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "shiftCase" did not return the expected result while converting to upper case.');

		$subject = 'Fußball ist nicht mein Lieblingssport';
		$this->shiftCaseProcessor->setDirection('upper');
		$result = $this->shiftCaseProcessor->process($subject);
		$expectedResult = 'FUSSBALL IST NICHT MEIN LIEBLINGSSPORT';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "shiftCase" did not return the expected result while converting to upper case - the Fußball test.');
	}

	/**
	 * Checks if the shiftCase() processor works with direction "to lower"
	 *
	 * @test
	 */
	public function shiftCaseToLowerWorks() {
		$subject = 'Kasper SKÅRHØJ';
		$this->shiftCaseProcessor->setDirection('lower');
		$result = $this->shiftCaseProcessor->process($subject);
		$expectedResult = 'kasper skårhøj';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "shiftCase" did not return the expected result while converting to lower case.');
	}

	/**
	 * Checks if the shiftCase() processor works with direction "to title"
	 *
	 * @test
	 */
	public function shiftCaseToTitleWorks() {
		$subject = 'kasper skårhøj';
		$this->shiftCaseProcessor->setDirection('title');
		$result = $this->shiftCaseProcessor->process($subject);
		$expectedResult = 'Kasper Skårhøj';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "shiftCase" did not return the expected result while converting to title case.');
	}

	/**
	 * Checks if the shiftCase() processor throws an exception on an invalid direction
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function shiftCaseThrowsExceptionOnInvalidDirection() {
		$subject = 'Kasper Skårhøj';
		$this->shiftCaseProcessor->setDirection(-123456);
		$this->shiftCaseProcessor->process($subject);
	}

	/**
	 * Checks if the shiftCase() processor throws an exception if direction has not been specified
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function shiftCaseThrowsExceptionIfDirectionHasNotBeenSpecified() {
		$subject = 'Kasper Skårhøj';
		$this->shiftCaseProcessor->process($subject);
	}
}
?>

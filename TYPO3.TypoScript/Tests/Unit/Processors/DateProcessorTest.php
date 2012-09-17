<?php
namespace TYPO3\TypoScript\Processors;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TypoScript".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript DateProcessor
 *
 */
class DateProcessorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\DateProcessor
	 */
	protected $dateProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->dateProcessor = new \TYPO3\TypoScript\Processors\DateProcessor();
	}

	/**
	 * Checks if the date() processor basically works.
	 *
	 * @test
	 */
	public function dateBasicallyWorksImplyingUtcTimezone() {
		$subject = 1185279917;
		$this->dateProcessor->setFormat('F j, Y, g:i a e');
		$result = $this->dateProcessor->process($subject);
		$expectedResult = 'July 24, 2007, 12:25 pm +00:00';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return the expected result while converting a UNIX timestamp. Expected "' . $expectedResult . '" but got "' . $result . '"');
	}

	/**
	 * Checks if the date() processor works when setting the timezone to "Japan"
	 *
	 * @test
	 */
	public function dateWorksAdjustingTimezoneToJapan() {
		$subject = 1185279917;
		$this->dateProcessor->setFormat('F j, Y, g:i a e');
		$this->dateProcessor->setTimezone('Japan');
		$result = $this->dateProcessor->process($subject);
		$expectedResult = 'July 24, 2007, 9:25 pm Japan';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return the expected result while converting a UNIX timestamp to Japanese timezone. Expected "' . $expectedResult . '" but got "' . $result . '"');
	}

	/**
	 * Checks if the date() processor throws an \TYPO3\TypoScript\Exception on an invalid timestamp
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function dateThrowsExceptionOnInvalidTimestamp() {
		$subject = 'This is no valid timestamp';
		$this->dateProcessor->setFormat('F j, Y, g:i a');
		$this->dateProcessor->process($subject);
	}

	/**
	 * Checks if the date() processor throws an \TYPO3\TypoScript\Exception on a negative timestamp value
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function dateThrowsExceptionOnNegativeTimestamp() {
		$subject = -1254324643;
		$this->dateProcessor->setFormat('F j, Y, g:i a');
		$this->dateProcessor->process($subject);
	}

	/**
	 * Checks if the date() processor throws an exception on an invalid timezone setting
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function dateThrowsExceptionOnGivingInvalidTimezone() {
		$subject = 1185279917;
		$this->dateProcessor->setFormat('F j, Y, g:i a e');
		$this->dateProcessor->setTimezone('Galactic Sector QQ7 Active J Gamma');
		$this->dateProcessor->process($subject);
	}

	/**
	 * Checks if the date() processor returns an empty string on an empty format string
	 *
	 * @test
	 */
	public function dateReturnsEmptyStringOnEmptyFormat() {
		$subject = 1254324643;
		$this->dateProcessor->setFormat('');
		$expectedResult = '';
		$result = $this->dateProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return an empty string on an empty format string.');
	}

	/**
	 * Checks if the date() processor returns an empty string if date format is not specified
	 *
	 * @test
	 */
	public function dateReturnsEmptyStringIfFormatIsNotSpecified() {
		$subject = 1254324643;
		$expectedResult = '';
		$result = $this->dateProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return an empty string although no date format has been specified.');
	}
}
?>

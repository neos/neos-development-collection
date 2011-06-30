<?php
namespace TYPO3\TYPO3\TypoScript\Processors;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript DateProcessor
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DateProcessorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3\TypoScript\Processors\DateProcessor
	 */
	protected $dateProcessor;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->dateProcessor = new \TYPO3\TYPO3\TypoScript\Processors\DateProcessor();
	}

	/**
	 * Checks if the date() processor basically works.
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dateBasicallyWorks() {
		$subject = 1185279917;
		$this->dateProcessor->setFormat('F j, Y, g:i a');
		$result = $this->dateProcessor->process($subject);
		$expectedResult = 'July 24, 2007, 2:25 pm';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return the expected result while converting a UNIX timestamp. Expected "' . $expectedResult . '" but got "' . $result . '"');
	}

	/**
	 * Checks if the date() processor throws an \TYPO3\TypoScript\Exception on an invalid timestamp
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dateThrowsExceptionOnNegativeTimestamp() {
		$subject = -1254324643;
		$this->dateProcessor->setFormat('F j, Y, g:i a');
		$this->dateProcessor->process($subject);
	}

	/**
	 * Checks if the date() processor returns an empty string on an empty format string
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dateReturnsEmptyStringIfFormatIsNotSpecified() {
		$subject = 1254324643;
		$expectedResult = '';
		$result = $this->dateProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return an empty string although no date format has been specified.');
	}
}
?>

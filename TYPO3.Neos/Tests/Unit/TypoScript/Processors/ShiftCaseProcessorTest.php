<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript\Processors;

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
 * Testcase for the TypoScript ShiftCaseProcessor
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ShiftCaseProcessorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3\TypoScript\Processors\ShiftCaseProcessor
	 */
	protected $shiftCaseProcessor;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->shiftCaseProcessor = new \F3\TYPO3\TypoScript\Processors\ShiftCaseProcessor();
	}

	/**
	 * Checks if the shiftCase() processor works with direction "to upper"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @expectedException \F3\TypoScript\Exception
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @expectedException \F3\TypoScript\Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function shiftCaseThrowsExceptionIfDirectionHasNotBeenSpecified() {
		$subject = 'Kasper Skårhøj';
		$this->shiftCaseProcessor->process($subject);
	}
}
?>

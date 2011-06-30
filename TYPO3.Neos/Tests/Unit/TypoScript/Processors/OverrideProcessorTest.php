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
 * Testcase for the TypoScript OverrideProcessor
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class OverrideProcessorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3\TypoScript\Processors\OverrideProcessor
	 */
	protected $overrideProcessor;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->overrideProcessor = new \TYPO3\TYPO3\TypoScript\Processors\OverrideProcessor();
	}

	/**
	 * Checks if the override() processor basically works
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function overrideBasicallyWorks() {
		$subject = 'To be killed!';
		$this->overrideProcessor->setReplacement('I shot the subject!');
		$expectedResult = 'I shot the subject!';
		$result = $this->overrideProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did not override the subject with the given value.');
	}

	/**
	 * Checks if the override() processor returns the original subject on an empty override value
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function overrideReturnsSubjectOnEmptyOverrideValue() {
		$subject = 'Not to be killed!';
		$this->overrideProcessor->setReplacement('');
		$expectedResult = 'Not to be killed!';
		$result = $this->overrideProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did override the subject with an empty override value.');
	}

	/**
	 * Checks if the override() processor returns the original subject on a 0 value
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function overrideReturnsSubjectOnZeroOverrideValue() {
		$subject = 'Not to be killed!';
		$this->overrideProcessor->setReplacement(0);
		$expectedResult = 'Not to be killed!';
		$result = $this->overrideProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did override the subject with a zero override value.');
	}

	/**
	 * Checks if the override() processor returns the original subject on a not trimmed 0 value
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function overrideReturnsSubjectOnNotTrimmedZeroOverrideValue() {
		$subject = 'Not to be killed!';
		$this->overrideProcessor->setReplacement('  0  ');
		$expectedResult = 'Not to be killed!';
		$result = $this->overrideProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did override the subject with a not trimmed zero override value.');
	}

	/**
	 * Checks if the override() processor returns the original subject if replacement has not been specified
	 *
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function overrideReturnsSubjectIfReplacementHasNotBeenSpecified() {
		$subject = 'Not to be killed!';
		$expectedResult = 'Not to be killed!';
		$result = $this->overrideProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did override the subject although no replacement has been specified.');
	}
}
?>

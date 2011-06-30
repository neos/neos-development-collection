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
 * Testcase for the TypoScript SubstringProcessor
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SubstringProcessorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3\TypoScript\Processors\SubstringProcessor
	 */
	protected $substringProcessor;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->substringProcessor = new \TYPO3\TYPO3\TypoScript\Processors\SubstringProcessor();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function subjectIsNotModifiedIfNoOptionsHaveBeenSet() {
		$subject = 'Kasper Skårhøj\'s name is good to test String functions';
		$expectedResult = 'Kasper Skårhøj\'s name is good to test String functions';

		$actualResult = $this->substringProcessor->process($subject);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function passingAStringInsteadOfTheStartPositionIntoSubstringThrowsAnException() {
		$this->substringProcessor->setStart('a string');
		$this->substringProcessor->process('the subject');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function passingAStringInsteadOfTheLenghtIntoSubstringThrowsAnException() {
		$this->substringProcessor->setLength('a string');
		$this->substringProcessor->process('the subject');
	}
}
?>

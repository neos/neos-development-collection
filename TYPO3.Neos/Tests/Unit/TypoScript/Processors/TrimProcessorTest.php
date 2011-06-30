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
 * Testcase for the TypoScript TrimProcessor
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TrimProcessorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3\TypoScript\Processors\TrimProcessor
	 */
	protected $trimProcessor;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->trimProcessor = new \TYPO3\TYPO3\TypoScript\Processors\TrimProcessor();
	}

	/**
	 * Checks if the trim() processor basically works
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function trimWorksWithIntegers() {
		$subject = 123;
		$expectedResult = '123';
		$result = $this->trimProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "trim" did not return the expected result.');
	}
}
?>

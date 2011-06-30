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
 * Testcase for the TypoScript WrapProcessor
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class WrapProcessorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TYPO3\TypoScript\Processors\WrapProcessor
	 */
	protected $wrapProcessor;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->wrapProcessor = new \TYPO3\TYPO3\TypoScript\Processors\WrapProcessor();
	}

	/**
	 * Checks if the wrap() processor basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function prefixAndSuffixAreEmptyByDefault() {
		$subject = 'Kasper Skårhøj';
		$result = $this->wrapProcessor->process($subject);
		$expectedResult = 'Kasper Skårhøj';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "wrap" did not return the expected result.');
	}
}
?>

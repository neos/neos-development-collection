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
 * Testcase for the TypoScript MultiplyProcessor
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MultiplyProcessorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3\TypoScript\Processors\MultiplyProcessor
	 */
	protected $multiplyProcessor;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->multiplyProcessor = new \F3\TYPO3\TypoScript\Processors\MultiplyProcessor();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function multiplyReturnsTheCorrectNumbers() {
		$subject = '1';
		$this->multiplyProcessor->setFactor(1.5);
		$result = $this->multiplyProcessor->process($subject);
		$this->assertEquals($result, 1.5, 'Multiply does not output the right result.');

		$subject = '1.5';
		$this->multiplyProcessor->setFactor(2);
		$result = $this->multiplyProcessor->process($subject);
		$this->assertEquals($result, 3, 'Multiply does not output the right result (2).');
	}

	/**
	 * @test
	 * @expectedException \F3\TypoScript\Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function multiplyThrowsExceptionIfNonNumericStringPassedAsSubject() {
		$this->multiplyProcessor->setFactor(1);
		$this->multiplyProcessor->process(' ');
	}

	/**
	 * @test
	 * @expectedException \F3\TypoScript\Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function multiplyThrowsExceptionIfStringPassedAsFactor() {
		$this->multiplyProcessor->setFactor('some String');
		$this->multiplyProcessor->process('1.43');
	}

	/**
	 * @test
	 * @expectedException \F3\TypoScript\Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function notSettingTheMultiplyFactorThrowsException() {
		$this->multiplyProcessor->process(123);
	}
}
?>

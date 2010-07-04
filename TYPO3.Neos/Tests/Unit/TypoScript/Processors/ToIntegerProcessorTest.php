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

require_once (__DIR__ . '/../../Fixtures/MockTypoScriptObject.php');

/**
 * Testcase for the TypoScript ToIntegerProcessor
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ToIntegerProcessorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3\TypoScript\Processors\ToIntegerProcessor
	 */
	protected $toIntegerProcessor;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->toIntegerProcessor = new \F3\TYPO3\TypoScript\Processors\ToIntegerProcessor();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toIntegerConvertsTheNumberFivePassedAsAStringIntoAnInteger() {
		$result = $this->toIntegerProcessor->process('5');
		$this->assertSame(5, $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toIntegerConvertsTheNumberFourtyThreePassedAsAStringIntoAnInteger() {
		$result = $this->toIntegerProcessor->process('43');
		$this->assertSame(43, $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toIntegerConvertsAnObjectToStringBeforeConvertingItToAnInteger() {
		$mockObject = new \F3\TYPO3\TypoScript\MockTypoScriptObject();
		$mockObject->setValue('25');

		$result = $this->toIntegerProcessor->process($mockObject);
		$this->assertSame(25, $result);
	}
}
?>

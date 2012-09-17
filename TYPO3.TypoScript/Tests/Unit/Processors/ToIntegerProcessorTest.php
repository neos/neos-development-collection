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

require_once (__DIR__ . '/../Fixtures/MockTypoScriptObject.php');

/**
 * Testcase for the TypoScript ToIntegerProcessor
 *
 */
class ToIntegerProcessorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\ToIntegerProcessor
	 */
	protected $toIntegerProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->toIntegerProcessor = new \TYPO3\TypoScript\Processors\ToIntegerProcessor();
	}

	/**
	 * @test
	 */
	public function toIntegerConvertsTheNumberFivePassedAsAStringIntoAnInteger() {
		$result = $this->toIntegerProcessor->process('5');
		$this->assertSame(5, $result);
	}

	/**
	 * @test
	 */
	public function toIntegerConvertsTheNumberFourtyThreePassedAsAStringIntoAnInteger() {
		$result = $this->toIntegerProcessor->process('43');
		$this->assertSame(43, $result);
	}

	/**
	 * @test
	 */
	public function toIntegerConvertsAnObjectToStringBeforeConvertingItToAnInteger() {
		$mockObject = new \TYPO3\TypoScript\MockTypoScriptObject();
		$mockObject->setValue('25');

		$result = $this->toIntegerProcessor->process($mockObject);
		$this->assertSame(25, $result);
	}
}
?>

<?php
namespace TYPO3\TypoScript\Processors;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript RoundProcessor
 *
 */
class RoundProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\RoundProcessor
	 */
	protected $roundProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->roundProcessor = new \TYPO3\TypoScript\Processors\RoundProcessor();
	}

	/**
	 * Checks if the round function works as expected when having regular input
	 *
	 * @test
	 */
	public function roundWorksForFloatParameters() {
		$subject = 5.3;
		$expected = 5;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.7;
		$expected = 42;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.50001;
		$expected = 42;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.499999;
		$expected = 41;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.5;
		$expected = 42;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 42;
		$expected = 42;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');
	}

	/**
	 * Checks if the round function works as expected when having an additional precision parameter
	 *
	 * @test
	 */
	public function roundWorksWithPrecisionParameter() {
		$subject = 5.31;
		$this->roundProcessor->setPrecision(1);
		$expected = 5.3;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.7;
		$this->roundProcessor->setPrecision(-1);
		$expected = 40;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.50001;
		$this->roundProcessor->setPrecision(1);
		$expected = 41.5;
		$result = $this->roundProcessor->process($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');
	}

	/**
	 * Checks if the round function fails if passed a string
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function roundThrowsExceptionOnInvalidParameters() {
		$subject = 'Transition days rock.';
		$this->roundProcessor->process($subject);
	}

	/**
	 * Checks if the round function fails if precision is a string
	 *
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function roundThrowsExceptionOnInvalidPrecisionParameters() {
		$subject = 'Transition days rock.';
		$this->roundProcessor->setPrecision('invalidValue');
		$this->roundProcessor->process($subject);
	}
}
?>

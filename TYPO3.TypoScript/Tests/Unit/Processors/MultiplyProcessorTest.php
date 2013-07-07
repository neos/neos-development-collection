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
 * Testcase for the TypoScript MultiplyProcessor
 *
 */
class MultiplyProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\MultiplyProcessor
	 */
	protected $multiplyProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->multiplyProcessor = new \TYPO3\TypoScript\Processors\MultiplyProcessor();
	}

	/**
	 * @test
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
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function multiplyThrowsExceptionIfNonNumericStringPassedAsSubject() {
		$this->multiplyProcessor->setFactor(1);
		$this->multiplyProcessor->process(' ');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function multiplyThrowsExceptionIfStringPassedAsFactor() {
		$this->multiplyProcessor->setFactor('some String');
		$this->multiplyProcessor->process('1.43');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 */
	public function notSettingTheMultiplyFactorThrowsException() {
		$this->multiplyProcessor->process(123);
	}
}
?>

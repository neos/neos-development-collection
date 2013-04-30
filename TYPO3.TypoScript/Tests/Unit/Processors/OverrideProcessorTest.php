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
 * Testcase for the TypoScript OverrideProcessor
 *
 */
class OverrideProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\OverrideProcessor
	 */
	protected $overrideProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->overrideProcessor = new \TYPO3\TypoScript\Processors\OverrideProcessor();
	}

	/**
	 * Checks if the override() processor basically works
	 *
	 * @test
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
	 */
	public function overrideReturnsSubjectIfReplacementHasNotBeenSpecified() {
		$subject = 'Not to be killed!';
		$expectedResult = 'Not to be killed!';
		$result = $this->overrideProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did override the subject although no replacement has been specified.');
	}
}
?>

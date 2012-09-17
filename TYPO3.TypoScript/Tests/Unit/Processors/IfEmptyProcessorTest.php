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

/**
 * Testcase for the TypoScript IfEmptyProcessor
 *
 */
class IfEmptyProcessorTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\IfEmptyProcessor
	 */
	protected $ifEmptyProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->ifEmptyProcessor = new \TYPO3\TypoScript\Processors\IfEmptyProcessor();
	}

	/**
	 * Checks if the ifEmpty() processor basically works
	 *
	 * @test
	 */
	public function ifEmptyBasicallyWorks() {
		$subject = '';
		$this->ifEmptyProcessor->setReplacement('I am not empty, like the subject is!');
		$expectedResult = 'I am not empty, like the subject is!';
		$result = $this->ifEmptyProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifEmpty" did not override an empty subject with the given value.');
	}

	/**
	 * Checks if the ifEmpty() processor returns the original subject if the subject is not empty
	 *
	 * @test
	 */
	public function ifEmptyReturnsSubjectItSubjectIsNotEmpty() {
		$subject = 'Not to be killed!';
		$this->ifEmptyProcessor->setReplacement('Give it a try.');
		$expectedResult = 'Not to be killed!';
		$result = $this->ifEmptyProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifEmpty" did override the subject even it was not empty.');
	}

	/**
	 * Checks if the ifEmpty() processor returns the override value for an 0 value of the subject
	 *
	 * @test
	 */
	public function ifEmptyReturnsSubjectOnZeroOverrideValue() {
		$subject = 0;
		$this->ifEmptyProcessor->setReplacement('I will prevail!');
		$expectedResult = 'I will prevail!';
		$result = $this->ifEmptyProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifEmpty" did not override the subject wich has a zero value.');
	}

	/**
	 * Checks if the ifEmpty() processor returns the override value for an not trimmed 0 value of the subject
	 *
	 * @test
	 */
	public function ifEmptyReturnsSubjectOnNotTrimmedZeroOverrideValue() {
		$subject = '   0   ';
		$this->ifEmptyProcessor->setReplacement('I will prevail!');
		$expectedResult = 'I will prevail!';
		$result = $this->ifEmptyProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifEmpty" did override the subject which has a not trimmed zero value.');
	}

	/**
	 * Checks if the ifEmpty() processor returns an empty string if replacement has not been set
	 *
	 * @test
	 */
	public function replacementStringIsEmptyByDefault() {
		$subject = '';
		$expectedResult = '';
		$result = $this->ifEmptyProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The default replacement of the TypoScript processor "ifEmpty" is not an empty string');
	}
}
?>

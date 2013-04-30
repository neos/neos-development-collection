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
 * Testcase for the TypoScript IfBlankProcessor
 *
 */
class IfBlankProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\IfBlankProcessor
	 */
	protected $ifBlankProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->ifBlankProcessor = new \TYPO3\TypoScript\Processors\IfBlankProcessor();
	}

	/**
	 * Checks if the ifBlank() processor basically works
	 *
	 * @test
	 */
	public function ifBlankBasicallyWorks() {
		$subject = '';
		$this->ifBlankProcessor->setReplacement('I am not empty, like the subject is!');
		$expectedResult = 'I am not empty, like the subject is!';
		$result = $this->ifBlankProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did not override an empty subject with the given value.');
	}

	/**
	 * Checks if the ifBlank() processor returns the original subject if the subject is not empty
	 *
	 * @test
	 */
	public function ifBlankReturnsSubjectIfSubjectIsNotEmpty() {
		$subject = 'Not to be killed!';
		$this->ifBlankProcessor->setReplacement('Give it a try.');
		$expectedResult = 'Not to be killed!';
		$result = $this->ifBlankProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did override the subject even it was not empty.');
	}

	/**
	 * Checks if the ifBlank() processor returns the subject for an 0 value of the subject
	 *
	 * @test
	 */
	public function ifBlankReturnsSubjectOnZeroOverrideValue() {
		$subject = 0;
		$this->ifBlankProcessor->setReplacement('I will try to prevail!');
		$expectedResult = '0';
		$result = $this->ifBlankProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did not return the subject wich has a zero value.');
	}

	/**
	 * Checks if the ifBlank() processor returns the subject for an not trimmed 0 value of the subject
	 *
	 * @test
	 */
	public function ifBlankReturnsSubjectOnNotTrimmedZeroOverrideValue() {
		$subject = '   0   ';
		$this->ifBlankProcessor->setReplacement('I will try to prevail!');
		$expectedResult = '   0   ';
		$result = $this->ifBlankProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did override the subject which has a not trimmed zero value.');
	}

	/**
	 * Checks if the ifBlank() processor returns the subject for a subject with one space character
	 *
	 * @test
	 */
	public function ifBlankReturnsSubjectForSubjectOfOneSpaceCharacter() {
		$subject = ' ';
		$this->ifBlankProcessor->setReplacement('I will try to prevail!');
		$expectedResult = ' ';
		$result = $this->ifBlankProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did override the subject which is one space character.');
	}

	/**
	 * Checks if the ifBlank() processor returns an empty string if replacement has not been set
	 *
	 * @test
	 */
	public function replacementStringIsEmptyByDefault() {
		$subject = '';
		$expectedResult = '';
		$result = $this->ifBlankProcessor->process($subject);
		$this->assertEquals($expectedResult, $result, 'The default replacement of the TypoScript processor "ifBlank" is not an empty string');
	}
}
?>

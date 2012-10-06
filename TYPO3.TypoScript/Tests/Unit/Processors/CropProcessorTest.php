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
 * Testcase for the TypoScript CropProcessor
 *
 */
class CropProcessorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\Processors\CropProcessor
	 */
	protected $cropProcessor;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		$this->cropProcessor = new \TYPO3\TypoScript\Processors\CropProcessor();
	}

	/**
	 * Checks if the crop() processor works with standard options
	 *
	 * @test
	 */
	public function cropWorksWithStandardOptions() {
		$this->cropProcessor->setMaximumCharacters(18);
		$this->cropProcessor->setPreOrSuffixString('...');
		$result = $this->cropProcessor->process('Kasper Skårhøj implemented the original version of the crop function.');
		$expectedResult = 'Kasper Skårhøj imp...';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking its basic function.');
	}

	/**
	 * Checks if the crop() processor works with option "crop at word"
	 *
	 * @test
	 */
	public function cropWorksWithCropAtWordOption() {
		$this->cropProcessor->setMaximumCharacters(18);
		$this->cropProcessor->setPreOrSuffixString('...');
		$this->cropProcessor->setOptions(\TYPO3\TypoScript\Processors\CropProcessor::CROP_AT_WORD);
		$result = $this->cropProcessor->process('Kasper Skårhøj implemented the original version of the crop function.');
		$expectedResult = 'Kasper Skårhøj ...';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "crop at word" option.');
	}

	/**
	 * Checks if the crop() processor works with option "from beginning"
	 *
	 * @test
	 */
	public function cropWorksWithCropFromBeginningOption() {
		$this->cropProcessor->setMaximumCharacters(14);
		$this->cropProcessor->setPreOrSuffixString('...');
		$this->cropProcessor->setOptions(\TYPO3\TypoScript\Processors\CropProcessor::CROP_FROM_BEGINNING);
		$result = $this->cropProcessor->process('Kasper Skårhøj implemented the original version of the crop function.');
		$expectedResult = '... implemented the original version of the crop function.';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "from beginning" option.');
	}

	/**
	 * Checks if the crop() processor works with option "from beginning" and "crop at space"
	 *
	 * @test
	 */
	public function cropWorksWithCropFromBeginningAtWordOptions() {
		$this->cropProcessor->setMaximumCharacters(10);
		$this->cropProcessor->setPreOrSuffixString('...');
		$this->cropProcessor->setOptions(\TYPO3\TypoScript\Processors\CropProcessor::CROP_FROM_BEGINNING | \TYPO3\TypoScript\Processors\CropProcessor::CROP_AT_WORD);
		$result = $this->cropProcessor->process('Kasper Skårhøj implemented the original version of the crop function.');
		$expectedResult = '... implemented the original version of the crop function.';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "from beginning" and the "at word" option.');
	}

	/**
	 * Checks if the crop() processor works with option "crop at sentence"
	 *
	 * @test
	 */
	public function cropWorksWithCropAtSentenceOption() {
		$this->cropProcessor->setMaximumCharacters(80);
		$this->cropProcessor->setPreOrSuffixString('...');
		$this->cropProcessor->setOptions(\TYPO3\TypoScript\Processors\CropProcessor::CROP_AT_SENTENCE);
		$result = $this->cropProcessor->process('Kasper Skårhøj implemented the original version of the crop function. But now we are using a TextIterator. Not too bad either.');
		$expectedResult = 'Kasper Skårhøj implemented the original version of the crop function. ...';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "crop at sentence" option.');
	}

	/**
	 * Checks if the crop() processor allows to change the prefix/suffix
	 *
	 * @test
	 */
	public function preOrSuffixStringCanBeChanged() {
		$this->cropProcessor->setMaximumCharacters(15);
		$this->cropProcessor->setPreOrSuffixString('!');
		$result = $this->cropProcessor->process('Kasper Skårhøj implemented the original version of the crop function.');
		$expectedResult = 'Kasper Skårhøj !';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "preOrSuffixString" setting.');
	}

	/**
	 * Checks if the crop() processor does not modify the subject string if no options have been specified
	 *
	 * @test
	 */
	public function cropDoesNotModifySubjectIfNoOptionsAreSpecified() {
		$result = $this->cropProcessor->process('Kasper Skårhøj implemented the original version of the crop function.');
		$expectedResult = 'Kasper Skårhøj implemented the original version of the crop function.';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" does not return the expected result if no options are specified');
	}
}
?>

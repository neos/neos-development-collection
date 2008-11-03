<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::TypoScript;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage TypoScript
 * @version $Id:F3::FLOW3::Component::ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 */

require_once (__DIR__ . '/../Fixtures/F3_TYPO3_TypoScript_MockTypoScriptObject.php');

/**
 * Testcase for the TypoScript standard processors
 *
 * @package TYPO3
 * @subpackage TypoScript
 * @version $Id:F3::FLOW3::Component::ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ProcessorsTest extends F3::Testing::BaseTestCase {

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->processors = new F3::TYPO3::TypoScript::Processors;
	}

	/**
	 * Checks if the crop() processor works with standard options
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cropWorksWithStandardOptions() {
		$subject = 'Kasper Skårhøj implemented the original version of the crop function.';
		$result = $this->processors->processor_crop($subject, 15, '...');
		$expectedResult = 'Kasper Skårhøj ...';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking its basic function.');
	}

	/**
	 * Checks if the crop() processor works with option "crop at word"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cropWorksWithCropAtWordOption() {
		$subject = 'Kasper Skårhøj implemented the original version of the crop function.';
		$result = $this->processors->processor_crop($subject, 18, '...', F3::TYPO3::TypoScript::Processors::CROP_AT_WORD);
		$expectedResult = 'Kasper Skårhøj ...';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "crop at word" option.');
	}

	/**
	 * Checks if the crop() processor works with option "from beginning"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cropWorksWithCropFromBeginningOption() {
		$subject = 'Kasper Skårhøj implemented the original version of the crop function.';
		$result = $this->processors->processor_crop($subject, 14, '...', F3::TYPO3::TypoScript::Processors::CROP_FROM_BEGINNING);
		$expectedResult = '... implemented the original version of the crop function.';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "from beginning" option.');
	}

	/**
	 * Checks if the crop() processor works with option "from beginning" and "crop at space"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cropWorksWithCropFromBeginningAtWordOptions() {
		$subject = 'Kasper Skårhøj implemented the original version of the crop function.';
		$result = $this->processors->processor_crop($subject, 10, '...', F3::TYPO3::TypoScript::Processors::CROP_FROM_BEGINNING | F3::TYPO3::TypoScript::Processors::CROP_AT_WORD);
		$expectedResult = '... implemented the original version of the crop function.';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "from beginning" and the "at word" option.');
	}

	/**
	 * Checks if the crop() processor works with option "crop at sentence"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cropWorksWithCropAtSentenceOption() {
		$subject = 'Kasper Skårhøj implemented the original version of the crop function. But now we are using a TextIterator. Not too bad either.';
		$result = $this->processors->processor_crop($subject, 80, '...', F3::TYPO3::TypoScript::Processors::CROP_AT_SENTENCE);
		$expectedResult = 'Kasper Skårhøj implemented the original version of the crop function. ...';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "crop at sentence" option.');
	}

	/**
	 * Checks if the crop() processor can handle objects as parameters
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function cropCanHandleObjectsAsParameters() {
		$testText = 'Kasper Skårhøj implemented the original version of the crop function. But now we are using a TextIterator. Not too bad either.';
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testText);

		$expectedResult = 'Kasper Skårhøj implemented the original version of the crop function. ...';
		$result = $this->processors->processor_crop($subject, 80, '...', F3::TYPO3::TypoScript::Processors::CROP_AT_SENTENCE);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "crop" did not return the expected result while checking the "crop at sentence" option. (We called it with an text object)');
	}

	/**
	 * Checks if the wrap() processor basically works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function wrapBasicallyWorks() {
		$subject = 'Kasper Skårhøj';
		$result = $this->processors->processor_wrap($subject, '<strong>', '</strong>');
		$expectedResult = '<strong>Kasper Skårhøj</strong>';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "wrap" did not return the expected result.');
	}

	/**
	 * Checks if the wrap() processor can handle objects as parameters
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function wrapCanHandleObjectsAsParameters() {
		$testText = 'Kasper Skårhøj';
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testText);

		$openStrongTag = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$openStrongTag->setValue('<strong>');
		$closeStrongTag = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$closeStrongTag->setValue('</strong>');

		$result = $this->processors->processor_wrap($subject, $openStrongTag, $closeStrongTag);
		$expectedResult = '<strong>Kasper Skårhøj</strong>';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "wrap" did not return the expected result. (We called it with text objects)');
	}

	/**
	 * Checks if the shiftCase() processor works with direction "to upper"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shiftCaseToUpperWorks() {
		$subject = 'Kasper Skårhøj';
		$result = $this->processors->processor_shiftCase($subject, F3::TYPO3::TypoScript::Processors::SHIFT_CASE_TO_UPPER);
		$expectedResult = 'KASPER SKÅRHØJ';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "shiftCase" did not return the expected result while converting to upper case.');

		$subject = 'Fußball ist nicht mein Lieblingssport';
		$result = $this->processors->processor_shiftCase($subject, F3::TYPO3::TypoScript::Processors::SHIFT_CASE_TO_UPPER);
		$expectedResult = 'FUSSBALL IST NICHT MEIN LIEBLINGSSPORT';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "shiftCase" did not return the expected result while converting to upper case - the Fußball test.');
	}

	/**
	 * Checks if the shiftCase() processor works with direction "to lower"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shiftCaseToLowerWorks() {
		$subject = 'Kasper SKÅRHØJ';
		$result = $this->processors->processor_shiftCase($subject, F3::TYPO3::TypoScript::Processors::SHIFT_CASE_TO_LOWER);
		$expectedResult = 'kasper skårhøj';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "shiftCase" did not return the expected result while converting to lower case.');
	}

	/**
	 * Checks if the shiftCase() processor works with direction "to title"
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shiftCaseToTitleWorks() {
		$subject = 'kasper skårhøj';
		$result = $this->processors->processor_shiftCase($subject, F3::TYPO3::TypoScript::Processors::SHIFT_CASE_TO_TITLE);
		$expectedResult = 'Kasper Skårhøj';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "shiftCase" did not return the expected result while converting to title case.');
	}

	/**
	 * Checks if the shiftCase() processor throws an exception on an invalid direction
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shiftCaseThrowsExceptionOnInvalidDirection() {
		$subject = 'Kasper Skårhøj';
		try {
			$result = $this->processors->processor_shiftCase($subject, -123456);
			$this->fail('The TypoScript processor "shiftCase" did not throw an exception on specifying an invalid direction.');
		} catch (::Exception $exception) {

		}
	}

	/**
	 * Checks if the shiftCase() processor can handle objects as parameters
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function shiftCaseCanHandleObjectsAsParameters() {
		$testText = 'Kasper Skårhøj';
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testText);

		$result = $this->processors->processor_shiftCase($subject, F3::TYPO3::TypoScript::Processors::SHIFT_CASE_TO_UPPER);
		$expectedResult = 'KASPER SKÅRHØJ';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "shiftCase" did not return the expected result while converting to upper case. (We called it with an text object)');
	}

	/**
	 * Checks if the date() processor basically works.
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dateBasicallyWorks() {
		$subject = 1185279917;
		$format = 'F j, Y, g:i a';
		$result = $this->processors->processor_date($subject, $format);
		$expectedResult = 'July 24, 2007, 2:25 pm';
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return the expected result while converting a UNIX timestamp. Expected "' . $expectedResult . '" but got "' . $result . '"');
	}

	/**
	 * Checks if the date() processor throws an F3::TypoScript::Exception on an invalid timestamp
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dateThrowsExceptionOnInvalidTimestamp() {
		$subject = 'This is no valid timestamp';
		$format = 'F j, Y, g:i a';
		try {
			$this->processors->processor_date($subject, $format);
			$this->fail('The TypoScript processor "date" did not throw an F3::TypoScript::Exception on transforming an invalid timestamp.');
		}
		catch (F3::TypoScript::Exception $exception) {

		}
	}

	/**
	 * Checks if the date() processor throws an F3::TypoScript::Exception on a negative timestamp value
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dateThrowsExceptionOnNegativeTimestamp() {
		$subject = -1254324643;
		$format = 'F j, Y, g:i a';
		try {
			$this->processors->processor_date($subject, $format);
			$this->fail('The TypoScript processor "date" did not throw an F3::TypoScript::Exception on transforming a negative timestamp value.');
		}
		catch (F3::TypoScript::Exception $exception) {

		}
	}

	/**
	 * Checks if the date() processor returns an empty value on an empty format string
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dateReturnsEmptyValueOnEmptyFormat() {
		$subject = 1254324643;
		$format = '';
		$expectedResult = '';
		$result = $this->processors->processor_date($subject, $format);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return an empty value on an empty format string.');
	}

	/**
	 * Checks if the date() processor can be called with objects as parameters
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dateCanHandleObjectsAsParameters() {
		$testTimestamp = '1185279917';
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testTimestamp);

		$testFormat = 'F j, Y, g:i a';
		$format = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$format->setValue($testFormat);

		$expectedResult = 'July 24, 2007, 2:25 pm';
		$result = $this->processors->processor_date($subject, $format);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return the expected result while converting a UNIX timestamp. Expected "' . $expectedResult . '" but got "' . $result . '". (We called it with objects that return strings as parameters)');


		$testTimestamp = 1254324643;
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testTimestamp);

		$testFormat = 246896744;
		$format = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$format->setValue($testFormat);

		$expectedResult = '246896744';
		$result = $this->processors->processor_date($subject, $format);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "date" did not return the expected result while converting a UNIX timestamp. Expected "' . $expectedResult . '" but got "' . $result . '". (We called it with objects that return integers as parameters)');
	}

	/**
	 * Checks if the override() processor basically works
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function overrideBasicallyWorks() {
		$subject = 'To be killed!';
		$overrideValue = 'I shot the subject!';
		$expectedResult = 'I shot the subject!';
		$result = $this->processors->processor_override($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did not override the subject with the given value.');
	}

	/**
	 * Checks if the override() processor returns the original subject on an empty override value
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function overrideReturnsSubjectOnEmptyOverrideValue() {
		$subject = 'Not to be killed!';
		$overrideValue = '';
		$expectedResult = 'Not to be killed!';
		$result = $this->processors->processor_override($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did override the subject with an empty override value.');
	}

	/**
	 * Checks if the override() processor returns the original subject on a 0 value
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function overrideReturnsSubjectOnZeroOverrideValue() {
		$subject = 'Not to be killed!';
		$overrideValue = 0;
		$expectedResult = 'Not to be killed!';
		$result = $this->processors->processor_override($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did override the subject with a zero override value.');
	}

	/**
	 * Checks if the override() processor returns the original subject on a not trimmed 0 value
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function overrideReturnsSubjectOnNotTrimmedZeroOverrideValue() {
		$subject = 'Not to be killed!';
		$overrideValue = '  0  ';
		$expectedResult = 'Not to be killed!';
		$result = $this->processors->processor_override($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "override" did override the subject with a not trimmed zero override value.');
	}

	/**
	 * Checks if the override() processor can be called with objects as parameters
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function overrideCanHandleObjectsAsParameters() {
		$testString = 'To be killed!';
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testString);

		$testOverrideString = 'I shot the subject!';
		$overrideValue = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$overrideValue->setValue($testOverrideString);

		$expectedResult = 'I shot the subject!';
		$result = $this->processors->processor_override($subject, $overrideValue);
		$this->assertEquals($expectedResult, (string)$result, 'The TypoScript processor "override" did not override the subject with the override value. (We called it with objects that return strings as parameters)');


		$testString = 1132435454;
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testString);

		$testOverrideString = 0;
		$overrideValue = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$overrideValue->setValue($testOverrideString);

		$expectedResult = 1132435454;
		$result = $this->processors->processor_override($subject, $overrideValue);
		$this->assertEquals($expectedResult, (string)$result, 'The TypoScript processor "override" did override the subject with a zero override value. (We called it with objects that return integers as parameters)');
	}

	/**
	 * Checks if the ifEmpty() processor basically works
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifEmptyBasicallyWorks() {
		$subject = '';
		$overrideValue = 'I am not empty, like the subject is!';
		$expectedResult = 'I am not empty, like the subject is!';
		$result = $this->processors->processor_ifEmpty($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifEmpty" did not override an empty subject with the given value.');
	}

	/**
	 * Checks if the ifEmpty() processor returns the original subject if the subject is not empty
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifEmptyReturnsSubjectItSubjectIsNotEmpty() {
		$subject = 'Not to be killed!';
		$overrideValue = 'Give it a try.';
		$expectedResult = 'Not to be killed!';
		$result = $this->processors->processor_ifEmpty($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifEmpty" did override the subject even it was not empty.');
	}

	/**
	 * Checks if the ifEmpty() processor returns the override value for an 0 value of the subject
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifEmptyReturnsSubjectOnZeroOverrideValue() {
		$subject = 0;
		$overrideValue = 'I will prevail!';
		$expectedResult = 'I will prevail!';
		$result = $this->processors->processor_ifEmpty($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifEmpty" did not override the subject wich has a zero value.');
	}

	/**
	 * Checks if the ifEmpty() processor returns the override value for an not trimmed 0 value of the subject
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifEmptyReturnsSubjectOnNotTrimmedZeroOverrideValue() {
		$subject = '   0   ';
		$overrideValue = 'I will prevail!';
		$expectedResult = 'I will prevail!';
		$result = $this->processors->processor_ifEmpty($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifEmpty" did override the subject which has a not trimmed zero value.');
	}

	/**
	 * Checks if the ifEmpty() processor can be called with objects as parameters
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifEmptyCanHandleObjectsAsParameters() {
		$testString = '';
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testString);

		$testOverrideString = 'I shot the subject!';
		$overrideValue = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$overrideValue->setValue($testOverrideString);

		$expectedResult = 'I shot the subject!';
		$result = $this->processors->processor_ifEmpty($subject, $overrideValue);
		$this->assertEquals($expectedResult, (string)$result, 'The TypoScript processor "ifEmpty" did not override the subject with the override value. (We called it with objects that return strings as parameters)');


		$testString = 0;
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testString);

		$testOverrideString = 1132435454;
		$overrideValue = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$overrideValue->setValue($testOverrideString);

		$expectedResult = 1132435454;
		$result = $this->processors->processor_ifEmpty($subject, $overrideValue);
		$this->assertEquals($expectedResult, (string)$result, 'The TypoScript processor "ifEmpty" did not override the subject wich has a zero value. (We called it with objects that return integers as parameters)');
	}

	/**
	 * Checks if the ifBlank() processor basically works
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifBlankBasicallyWorks() {
		$subject = '';
		$overrideValue = 'I am not empty, like the subject is!';
		$expectedResult = 'I am not empty, like the subject is!';
		$result = $this->processors->processor_ifBlank($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did not override an empty subject with the given value.');
	}

	/**
	 * Checks if the ifBlank() processor returns the original subject if the subject is not empty
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifBlankReturnsSubjectIfSubjectIsNotEmpty() {
		$subject = 'Not to be killed!';
		$overrideValue = 'Give it a try.';
		$expectedResult = 'Not to be killed!';
		$result = $this->processors->processor_ifBlank($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did override the subject even it was not empty.');
	}

	/**
	 * Checks if the ifBlank() processor returns the subject for an 0 value of the subject
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifBlankReturnsSubjectOnZeroOverrideValue() {
		$subject = 0;
		$overrideValue = 'I will try to prevail!';
		$expectedResult = '0';
		$result = $this->processors->processor_ifBlank($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did not return the subject wich has a zero value.');
	}

	/**
	 * Checks if the ifBlank() processor returns the subject for an not trimmed 0 value of the subject
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifBlankReturnsSubjectOnNotTrimmedZeroOverrideValue() {
		$subject = '   0   ';
		$overrideValue = 'I will try to prevail!';
		$expectedResult = '   0   ';
		$result = $this->processors->processor_ifBlank($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did override the subject which has a not trimmed zero value.');
	}

	/**
	 * Checks if the ifBlank() processor returns the subject for a subject with one space character
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifBlankReturnsSubjectForSubjectOfOneSpaceCharacter() {
		$subject = ' ';
		$overrideValue = 'I will try to prevail!';
		$expectedResult = ' ';
		$result = $this->processors->processor_ifBlank($subject, $overrideValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "ifBlank" did override the subject which is one space character.');
	}

	/**
	 * Checks if the ifBlank() processor can be called with objects as parameters
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifBlankCanHandleObjectsAsParameters() {
		$testString = '';
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testString);

		$testOverrideString = 'I shot the subject!';
		$overrideValue = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$overrideValue->setValue($testOverrideString);

		$expectedResult = 'I shot the subject!';
		$result = $this->processors->processor_ifBlank($subject, $overrideValue);
		$this->assertEquals($expectedResult, (string)$result, 'The TypoScript processor "ifEmpty" did not override the subject with the override value. (We called it with objects that return strings as parameters)');


		$testString = 0;
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testString);

		$testOverrideString = 1132435454;
		$overrideValue = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$overrideValue->setValue($testOverrideString);

		$expectedResult = 0;
		$result = $this->processors->processor_ifBlank($subject, $overrideValue);
		$this->assertEquals($expectedResult, (string)$result, 'The TypoScript processor "ifEmpty" did override the subject wich has a zero value. (We called it with objects that return integers as parameters)');
	}

	/**
	 * Checks if the trim() processor basically works
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function trimBasicallyWorks() {
		$subject = '  I am not trimmed     ';
		$expectedResult = 'I am not trimmed';
		$result = $this->processors->processor_trim($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "trim" did not return the expected result.');
	}

	/**
	 * Checks if the trim() processor can handle objects
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function trimCanHandleObjects() {
		$testString = '  I am not trimmed     ';
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testString);

		$expectedResult = 'I am not trimmed';
		$result = $this->processors->processor_trim($subject);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "trim" did not return the expected result. (We called it with a text object)');
	}

	/**
	 * Checks if the if() processor basically works
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifBasicallyWorks() {
		$subject = 'not needed here';
		$condition = TRUE;
		$trueValue = 'I am really true!';
		$falseValue = 'I am more than just false!';

		$expectedResult = 'I am really true!';
		$result = $this->processors->processor_if($subject, $condition, $trueValue, $falseValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "if" did not return the expected result. (condition: TRUE)');

		$condition = FALSE;
		$expectedResult = 'I am more than just false!';
		$result = $this->processors->processor_if($subject, $condition, $trueValue, $falseValue);
		$this->assertEquals($expectedResult, $result, 'The TypoScript processor "if" did not return the expected result. (condition: FALSE)');
	}

	/**
	 * Checks if the if() processor can handle objects
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifCanHandleObjects() {
		$subject = 'not needed here';
		$condition = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$condition->setValue(TRUE);
		$trueValue = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$trueValue->setValue('I am really true!');
		$falseValue = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$falseValue->setValue('I am more than just false!');

		$expectedResult = 'I am really true!';
		$result = $this->processors->processor_if($subject, $condition, $trueValue, $falseValue);
		$this->assertEquals($expectedResult, (string)$result, 'The TypoScript processor "if" did not return the expected result. (condition: TRUE) We called it with text objects. Gave: ' . (string)$condition . ' Got: ' . $result);

		$condition->setValue('FALSE');
		$expectedResult = 'I am really true!';
		$result = $this->processors->processor_if($subject, $condition, $trueValue, $falseValue);
		$this->assertEquals($expectedResult, (string)$result, 'The TypoScript processor "if" did not return the expected result. (condition: "FALSE") We called it with text objects. Gave: ' . (string)$condition . ' Got: ' . $result);

		$condition->setValue('');
		$expectedResult = 'I am more than just false!';
		$result = $this->processors->processor_if($subject, $condition, $trueValue, $falseValue);
		$this->assertEquals($expectedResult, (string)$result, 'The TypoScript processor "if" did not return the expected result. (condition: "FALSE") We called it with text objects. Gave: ' . (string)$condition . ' Got: ' . $result);
	}

	/**
	 * Checks if the if() processor throws an exception on an invalid condition
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifThrowsExceptionOnInvalidCondition() {
		$subject = 'not needed here';
		$condition = NULL;
		$trueValue = 'I am really true!';
		$falseValue = 'I am more than just false!';

		try {
			$this->processors->processor_if($subject, $condition, $trueValue, $falseValue);
			$this->fail('The TypoScript processor "if" did not throw an F3::TypoScript::Exception on an invalid condition.');
		}
		catch (F3::TypoScript::Exception $exception) {
		}
	}

	/**
	 * Checks if the isEmpty() processor basically works
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isEmptyBasicallyWorks() {
		$subject = 'I am not empty.';

		$result = $this->processors->processor_isEmpty($subject);
		$this->assertFalse($result, 'The TypoScript processor "isEmpty" did not return false on a not empty subject.');

		$subject = '';
		$result = $this->processors->processor_isEmpty($subject);
		$this->assertTrue($result, 'The TypoScript processor "isEmpty" did not return true on an empty subject.');
	}

	/**
	 * Checks if the isEmpty() processor returns true on a zero value
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isEmptyReturnsTrueOnZeroValue() {
		$subject = '0';

		$result = $this->processors->processor_isEmpty($subject);
		$this->assertTrue($result, 'The TypoScript processor "isEmpty" did not return true on a \'0\' subject.');

		$subject = 0;
		$result = $this->processors->processor_isEmpty($subject);
		$this->assertTrue($result, 'The TypoScript processor "isEmpty" did not return true on a 0 subject.');
	}

	/**
	 * Checks if the isEmpty() processor can handle objects
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isEmptyCanHandleObjects() {
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue('I am not empty');

		$result = $this->processors->processor_isEmpty($subject);
		$this->assertFalse($result, 'The TypoScript processor "isEmpty" did not return false on a not empty subject. (We called it with an text object)');

		$subject->setValue('');
		$result = $this->processors->processor_isEmpty($subject);
		$this->assertTrue($result, 'The TypoScript processor "isEmpty" did not return true on an empty subject. (We called it with an text object)');
	}

	/**
	 * Checks if the isBlank() processor basically works
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isBlankBasicallyWorks() {
		$subject = '';
		$result = $this->processors->processor_isBlank($subject);
		$this->assertTrue($result, 'The TypoScript processor "isBlank" did not return true on a blank subject.');
	}

	/**
	 * Checks if the isBlank() processor returns false if the subject is not empty
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isBlankReturnsFalseIfSubjectIsNotEmpty() {
		$subject = 'Not to be killed!';
		$result = $this->processors->processor_isBlank($subject);
		$this->assertFalse($result, 'The TypoScript processor "isBlank" did return true even the subject was not empty.');
	}

	/**
	 * Checks if the isBlank() processor returns true for an 0 value of the subject
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isBlankReturnsFalseOnZeroSubjectValue() {
		$subject = 0;
		$result = $this->processors->processor_isBlank($subject);
		$this->assertFalse($result, 'The TypoScript processor "isBlank" did not return true for a zero subject.');
	}

	/**
	 * Checks if the isBlank() processor returns false for an not trimmed 0 value of the subject
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isBlankReturnsFalseOnNotTrimmedZeroValue() {
		$subject = '   0   ';
		$result = $this->processors->processor_isBlank($subject);
		$this->assertFalse($result, 'The TypoScript processor "isBlank" did return true for a subject that has a not trimmed zero value.');
	}

	/**
	 * Checks if the isBlank() processor returns false for a subject with one space character
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isBlankReturnsFalseForSubjectOfOneSpaceCharacter() {
		$subject = ' ';
		$result = $this->processors->processor_isBlank($subject);
		$this->assertFalse($result, 'The TypoScript processor "isBlank" did return true for a subject which is one space character.');
	}

	/**
	 * Checks if the isBlank() processor can be called with objects as parameters
	 *
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isBlankCanHandleObjectsAsParameters() {
		$testString = '';
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testString);

		$result = $this->processors->processor_isBlank($subject);
		$this->assertTrue($result, 'The TypoScript processor "isBlank" returned false. (We called it with objects that return strings as parameters)');


		$testString = 0;
		$subject = $this->componentFactory->create('F3::TYPO3::TypoScript::Text');
		$subject->setValue($testString);

		$result = $this->processors->processor_isBlank($subject);
		$this->assertFalse($result, 'The TypoScript processor "isBlank" returned true on a subject wich has a zero value. (We called it with objects that return integers as parameters)');
	}

	/**
	 * Checks if the round function works as expected when having regular input
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function roundWorksForFloatParameters() {
		$subject = 5.3;
		$expected = 5;
		$result = $this->processors->processor_round($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.7;
		$expected = 42;
		$result = $this->processors->processor_round($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.50001;
		$expected = 42;
		$result = $this->processors->processor_round($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.499999;
		$expected = 41;
		$result = $this->processors->processor_round($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.5;
		$expected = 42;
		$result = $this->processors->processor_round($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 42;
		$expected = 42;
		$result = $this->processors->processor_round($subject);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');
	}

	/**
	 * Checks if the round function works as expected when having an additional precision parameter
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function roundWorksWithPrecisionParameter() {
		$subject = 5.31;
		$expected = 5.3;
		$result = $this->processors->processor_round($subject, 1);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.7;
		$expected = 40;
		$result = $this->processors->processor_round($subject, -1);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');

		$subject = 41.50001;
		$expected = 41.5;
		$result = $this->processors->processor_round($subject,1);
		$this->assertEquals($expected, $result, 'Rounding of a float value did not return the expected result.');
	}

	/**
	 * Checks if the round function fails if passed a string
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException F3::TypoScript::Exception
	 */
	public function roundThrowsExceptionOnInvalidParameters() {
		$subject = 'Transition days rock.';
		$result = $this->processors->processor_round($subject);
	}

	/**
	 * Checks if the round function fails if precision is a string
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException F3::TypoScript::Exception
	 */
	public function roundThrowsExceptionOnInvalidPrecisionParameters() {
		$subject = 'Transition days rock.';
		$result = $this->processors->processor_round($subject, "hallo");
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function substringReturnsASubstringDefinedByStartAndLength() {
		$testString = 'The Transition Days rock!';
		$expectedResult = 'Transition Days';

		$actualResult = $this->processors->processor_substring($testString, 4, 15);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theLengthOfSubstringIsOptional() {
		$testString = 'I already had 5 coffees today';
		$expectedResult = 'already had 5 coffees today';

		$actualResult = $this->processors->processor_substring($testString, 2);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException F3::TypoScript::Exception
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingAStringInsteadOfTheStartPositionIntoSubstringThrowsAnException() {
		$this->processors->processor_substring('the subject', 'a string');
	}

	/**
	 * @test
	 * @expectedException F3::TypoScript::Exception
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingAStringInsteadOfTheLengthIntoSubstringThrowsAnException() {
		$this->processors->processor_substring('the subject', 2, 'a string');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toIntegerConvertsTheNumberFivePassedAsAStringIntoAnInteger() {
		$result = $this->processors->processor_toInteger('5');
		$this->assertSame(5, $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toIntegerConvertsTheNumberFourtyThreePassedAsAStringIntoAnInteger() {
		$result = $this->processors->processor_toInteger('43');
		$this->assertSame(43, $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toIntegerConvertsAnObjectToStringBeforeConvertingItToAnInteger() {
		$mockObject = new F3::TYPO3::TypoScript::MockTypoScriptObject();
		$mockObject->setValue('25');

		$result = $this->processors->processor_toInteger($mockObject);
		$this->assertSame(25, $result);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function multiplyReturnsTheCorrectNumbers() {
		$subject = '1';
		$result = $this->processors->processor_multiply($subject, 1.5);
		$this->assertEquals($result, 1.5, 'Multiply does not output the right result.');

		$subject = '1.5';
		$result = $this->processors->processor_multiply($subject, 2);
		$this->assertEquals($result, 3, 'Multiply does not output the right result (2).');
	}

	/**
	 * @test
	 * @expectedException F3::TypoScript::Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function multiplyThrowsExceptionIfNonNumericStringPassedAsSubject() {
		$this->processors->processor_multiply(' ', 1);
	}

	/**
	 * @test
	 * @expectedException F3::TypoScript::Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function multiplyThrowsExceptionIfStringPassedAsFactor() {
		$this->processors->processor_multiply('1.43', 'bla');
	}
}
?>
<?php
declare(ENCODING = 'utf-8');
namespace F3\TypoScript\Parser;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
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
 * Testcase for the TypoScript Parser - tests the regex patterns
 *
 * @version $Id:\F3\FLOW3\Object\ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PatternTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks the regular expression SCAN_PATTERN_COMMENT
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function testSCAN_PATTERN_COMMENT() {
		$pattern = \F3\TypoScript\Parser::SCAN_PATTERN_COMMENT;
		$this->assertEquals(preg_match($pattern, '/* This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a block comment start.');
		$this->assertEquals(preg_match($pattern, '# This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a hash comment start.');
		$this->assertEquals(preg_match($pattern, '// This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a double slash comment start.');
		$this->assertEquals(preg_match($pattern, ' # This is a comment start ...'), 1, 'The SCAN_PATTERN_COMMENT pattern did not match a hash comment start with preceeding whitespace.');
		$this->assertEquals(preg_match($pattern, '/ This is not a comment start ...'), 0, 'The SCAN_PATTERN_COMMENT pattern matched a single slash.');
		$this->assertEquals(preg_match($pattern, '*/ This is not a comment start ...'), 0, 'The SCAN_PATTERN_COMMENT pattern matched a comment block ending.');
	}

	/**
	 * Checks the regular expression SCAN_PATTERN_DECLARATION
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function testSCAN_PATTERN_DECLARATION() {
		$pattern = \F3\TypoScript\Parser::SCAN_PATTERN_DECLARATION;
		$this->assertEquals(preg_match($pattern, 'include : source = "resource"'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an include declaration.');
		$this->assertEquals(preg_match($pattern, 'include:source = "resource"'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an include declaration without whitespaces.');
		$this->assertEquals(preg_match($pattern, 'namespace: cms = Test'), 1, 'The SCAN_PATTERN_DECLARATION pattern did not match an namespace declaration.');
		$this->assertEquals(preg_match($pattern, '// This is a comment ...'), 0, 'The SCAN_PATTERN_DECLARATION pattern matched a comment.');
	}

	/**
	 * Checks the regular expression SCAN_PATTERN_OBJECTDEFINITION
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function testSCAN_PATTERN_OBJECTDEFINITION() {
		$pattern = \F3\TypoScript\Parser::SCAN_PATTERN_OBJECTDEFINITION;
		$this->assertEquals(preg_match($pattern, 'myObject = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment.');
		$this->assertEquals(preg_match($pattern, 'myObject.content = "stuff"'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match a literal assignment of a property.');
		$this->assertEquals(preg_match($pattern, 'myObject.10 = Text'), 1, 'The SCAN_PATTERN_OBJECTDEFINITION pattern did not match an object type assignment of a content array item.');
	}

	/**
	 * Checks the regular expression SPLIT_PATTERN_VALUEVARIABLE
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function testSPLIT_PATTERN_VALUEVARIABLE() {
		$pattern = \F3\TypoScript\Parser::SPLIT_PATTERN_VALUEVARIABLE;
		$this->assertEquals(preg_match($pattern, '$a'), 1, 'The SPLIT_PATTERN_VALUEVARIABLE pattern did not match a one-letter variable.');
		$this->assertEquals(preg_match($pattern, '$message'), 1, 'The SPLIT_PATTERN_VALUEVARIABLE pattern did not match the variable $message.');
		$this->assertEquals(preg_match($pattern, 'message'), 0, 'The SPLIT_PATTERN_VALUEVARIABLE pattern matched a variable without dollar sign.');
	}

	/**
	 * Checks the regular expression SPLIT_PATTERN_VALUENUMBER
	 *
	 * @test
	 * @author Sebastian Kurf�rst <sebastian@typo3.org>
	 */
	public function testSPLIT_PATTERN_VALUENUMBER() {
		$pattern = \F3\TypoScript\Parser::SPLIT_PATTERN_VALUENUMBER;
		$this->assertEquals(preg_match($pattern, ' 1'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match a number with a space in front.');
		$this->assertEquals(preg_match($pattern, '12221'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match the number 12221.');
		$this->assertEquals(preg_match($pattern, '-12'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match a negative number.');
		$this->assertEquals(preg_match($pattern, ' -42'), 1, 'The SPLIT_PATTERN_VALUENUMBER pattern did not match a negative number with a space in front.');
		$this->assertEquals(preg_match($pattern, '-12.5'), 0, 'The SPLIT_PATTERN_VALUENUMBER pattern matched a negative float number.');
		$this->assertEquals(preg_match($pattern, '42.5'), 0, 'The SPLIT_PATTERN_VALUENUMBER pattern matched a positive float number.');
	}

	/**
	 * Checks the regular expression SPLIT_PATTERN_METHODARGUMENTS
	 *
	 *
	 */
	public function testSPLIT_PATTERN_METHODARGUMENTS() {
		$pattern = \F3\TypoScript\Parser::SPLIT_PATTERN_METHODARGUMENTS;

		$this->assertEquals(preg_match($pattern, '" hallo"'), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a double-quoted string.');
		$this->assertEquals(preg_match($pattern, '" ha\"llo"'), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a double-quoted string with an escaped quote inside.');
		$this->assertEquals(preg_match($pattern, '\'huhu\''), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a single-quoted string.');
		$this->assertEquals(preg_match($pattern, "'huh\'u'"), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a single-quoted string with an escaped quote.');
		$this->assertEquals(preg_match($pattern, '$bEdkLKla'), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a variable.');
		//$this->assertEquals(preg_match($pattern, '\$bla'), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a negative number.');
		$this->assertEquals(preg_match($pattern, '-12'), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a negative number.');
		$this->assertEquals(preg_match($pattern, '12'), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a number.');
		$this->assertEquals(preg_match($pattern, '123.23'), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a float number.');
		$this->assertEquals(preg_match($pattern, '-123.23'), 1, 'The SPLIT_PATTERN_METHODARGUMENTS pattern did not match a negative float number.');
	}
}
?>
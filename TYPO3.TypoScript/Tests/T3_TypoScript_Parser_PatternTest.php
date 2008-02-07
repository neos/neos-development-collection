<?php
declare(encoding = 'utf-8');

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
 * Testcase for the TypoScript Parser - tests the regex patterns
 * 
 * @package		TypoScript
 * @version 	$Id:T3_FLOW3_Component_ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TypoScript_Parser_PatternTest extends T3_Testing_BaseTestCase {

	/**
	 * Checks the regular expression SCAN_PATTERN_COMMENT
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function testSCAN_PATTERN_COMMENT() {
		$pattern = T3_TypoScript_Parser::SCAN_PATTERN_COMMENT;
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
		$pattern = T3_TypoScript_Parser::SCAN_PATTERN_DECLARATION;
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
		$pattern = T3_TypoScript_Parser::SCAN_PATTERN_OBJECTDEFINITION;
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
		$pattern = T3_TypoScript_Parser::SPLIT_PATTERN_VALUEVARIABLE;
		$this->assertEquals(preg_match($pattern, '$a'), 1, 'The SPLIT_PATTERN_VALUEVARIABLE pattern did not match a one-letter variable.');
		$this->assertEquals(preg_match($pattern, '$message'), 1, 'The SPLIT_PATTERN_VALUEVARIABLE pattern did not match the variable $message.');
		$this->assertEquals(preg_match($pattern, 'message'), 0, 'The SPLIT_PATTERN_VALUEVARIABLE pattern matched a variable without dollar sign.');
	}
}
?>
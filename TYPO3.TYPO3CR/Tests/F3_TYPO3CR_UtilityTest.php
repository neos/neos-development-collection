<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR;

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
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Tests for the utility class of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class UtilityTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeNullFromArrayReturnsEmptyArrayUnchanged() {
		$this->assertEquals(array(), F3::TYPO3CR::Utility::removeNullFromArray(array()), 'An array containing nothing should be returned unchanged.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeNullFromArrayReturnsEmptyArrayFromArrayWithOnlyNulls() {
		$this->assertEquals(array(), F3::TYPO3CR::Utility::removeNullFromArray(array(NULL, NULL)), 'An array containing only NULL should be returned empty.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeNullFromArrayReturnsEmptyNestedArrayFromNestedArrayWithOnlyNulls() {
		$this->assertEquals(array(1 => array()), F3::TYPO3CR::Utility::removeNullFromArray(array(NULL, array(NULL), NULL)), 'An array containing only NULL should be returned empty.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeNullFromArrayReturnsCompactedArrayFromArray() {
		$this->assertEquals(array(1 => 'two'), F3::TYPO3CR::Utility::removeNullFromArray(array(NULL, 'two', NULL)));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeNullFromArrayReturnsCompactedNestedArrayFromNestedArray() {
		$this->assertEquals(array(1 => array('one'), 3 => 3), F3::TYPO3CR::Utility::removeNullFromArray(array(NULL, array('one'), NULL, 3)));
	}

}
?>
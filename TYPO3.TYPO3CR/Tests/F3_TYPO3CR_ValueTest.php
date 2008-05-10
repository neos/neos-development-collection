<?php
declare(ENCODING = 'utf-8');

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
 * Tests for the Value implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_ValueTest extends F3_Testing_BaseTestCase {

	/**
	 * Checks if a newly created Value object is of undefined type and
	 * requesting a type actually works.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function typeAssignmentWorks() {

		$value = new F3_TYPO3CR_Value('test');
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::UNDEFINED, 'New Value object was not of type UNDEFINED, although expected.');

		$value = new F3_TYPO3CR_Value('test', F3_phpCR_PropertyType::UNDEFINED);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::UNDEFINED, 'New Value object was not of type UNDEFINED, although requested.');

		$value = new F3_TYPO3CR_Value('test', F3_phpCR_PropertyType::STRING);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::STRING, 'New Value object was not of type STRING, although requested.');

		$value = new F3_TYPO3CR_Value('test', F3_phpCR_PropertyType::BINARY);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::BINARY, 'New Value object was not of type BINARY, although requested.');

		$value = new F3_TYPO3CR_Value(10, F3_phpCR_PropertyType::LONG);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::LONG, 'New Value object was not of type LONG, although requested.');

		$value = new F3_TYPO3CR_Value(1.5, F3_phpCR_PropertyType::DOUBLE);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::DOUBLE, 'New Value object was not of type DOUBLE, although requested.');

		$value = new F3_TYPO3CR_Value('2007-09-22', F3_phpCR_PropertyType::DATE);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::DATE, 'New Value object was not of type DATE, although requested.');

		$value = new F3_TYPO3CR_Value(TRUE, F3_phpCR_PropertyType::BOOLEAN);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::BOOLEAN, 'New Value object was not of type BOOLEAN, although requested.');

		$value = new F3_TYPO3CR_Value('name', F3_phpCR_PropertyType::NAME);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::NAME, 'New Value object was not of type NAME, although requested.');

		$value = new F3_TYPO3CR_Value('/some/path/to/something', F3_phpCR_PropertyType::PATH);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::PATH, 'New Value object was not of type PATH, although requested.');

		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array(), '', FALSE);
		$value = new F3_TYPO3CR_Value(new F3_TYPO3CR_Node($mockSession, $mockStorageAccess, $this->componentManager), F3_phpCR_PropertyType::REFERENCE);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::REFERENCE, 'New Value object was not of type REFERENCE, although requested.');

		$value = new F3_TYPO3CR_Value(new F3_TYPO3CR_Node($mockSession, $mockStorageAccess, $this->componentManager), F3_phpCR_PropertyType::WEAKREFERENCE);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::WEAKREFERENCE, 'New Value object was not of type WEAKREFERENCE, although requested.');

		$value = new F3_TYPO3CR_Value('http://typo3.org/gimmefive/', F3_phpCR_PropertyType::URI);
		$this->assertEquals($value->getType(), F3_phpCR_PropertyType::URI, 'New Value object was not of type URI, although requested.');

	}

	/**
	 * Checks if getString returns the expected result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getStringWorksOnStringValue() {
		$value = new F3_TYPO3CR_Value('123.5 test', F3_phpCR_PropertyType::STRING);
		$this->assertSame($value->getString(), '123.5 test', 'getString() did not return the expected result.');
		try {
			$value->getStream();
			$this->fail('getStream() did not fail, although a non-stream method had been called before.');
		} catch (BadMethodCallException $e) {
			// ok
		}
	}

	/**
	 * Checks if getString returns the expected result on a DATE value
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getStringWorksOnDateValue() {
		$value = new F3_TYPO3CR_Value('2007-08-31T16:47+00:00', F3_phpCR_PropertyType::DATE);
		$this->assertSame($value->getString(), '2007-08-31T16:47:00+0000', 'getString() did not return the expected result.');
		try {
			$value->getStream();
			$this->fail('getStream() did not fail, although a non-stream method had been called before.');
		} catch (BadMethodCallException $e) {
			// ok
		}
	}

	/**
	 * Checks if getBoolean returns the expected result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getBooleanWorksOnStringValue() {
		$value = new F3_TYPO3CR_Value('123.5 test', F3_phpCR_PropertyType::STRING);
		$this->assertSame($value->getBoolean(), TRUE, 'getBoolean() did not return the expected result.');

		$value = new F3_TYPO3CR_Value('0', F3_phpCR_PropertyType::STRING);
		$this->assertSame($value->getBoolean(), FALSE, 'getBoolean() did not return the expected result.');
		try {
			$value->getStream();
		} catch (BadMethodCallException $e) {
			// ok
		}
	}

	/**
	 * Checks if getDouble returns the expected result
	 * Also checks if getStream() is blocked afterwards
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDoubleWorksOnStringAndBlocksGetStream() {
		$value = new F3_TYPO3CR_Value('123.5 test', F3_phpCR_PropertyType::STRING);
		$this->assertSame($value->getDouble(), 123.5, 'getDouble() did not return the expected result.');
		try {
			$value->getStream();
			$this->fail('getStream() did not fail, although a non-stream method had been called before.');
		} catch (BadMethodCallException $e) {
			// ok
		}
	}

	/**
	 * Checks if getLong returns the expected result
	 * Also checks if getStream() is blocked afterwards
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getLongWorksOnStringValueAndBlocksGetStream() {
		$value = new F3_TYPO3CR_Value('123.5 test', F3_phpCR_PropertyType::STRING);
		$this->assertSame($value->getLong(), 123.5, 'getLong() did not return the expected result.');
		try {
			$value->getStream();
			$this->fail('getStream() did not fail, although a non-stream method had been called before.');
		} catch (BadMethodCallException $e) {
			// ok
		}
	}

	/**
	 * Checks if getDate returns the expected result
	 * Also checks if getStream() is blocked afterwards
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDateWorksOnDateValue() {
		$value = new F3_TYPO3CR_Value('2007-08-31T16:47+00:00', F3_phpCR_PropertyType::DATE);
		$DateTime = new DateTime('2007-08-31T16:47+00:00');
		$this->assertEquals($value->getDate(), $DateTime, 'getDate() did not return the expected result.');
		try {
			$value->getStream();
			$this->fail('getStream() did not fail, although a non-stream method had been called before.');
		} catch (BadMethodCallException $e) {
			// ok
		}
	}
}
?>

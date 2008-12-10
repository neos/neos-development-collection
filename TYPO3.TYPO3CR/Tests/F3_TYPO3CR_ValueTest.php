<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

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
class ValueTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks if a newly created Value object is of undefined type and
	 * requesting a type actually works.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function typeAssignmentWorks() {

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::UNDEFINED);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::UNDEFINED, 'New Value object was not of type UNDEFINED, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::STRING);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::STRING, 'New Value object was not of type STRING, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::BINARY);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::BINARY, 'New Value object was not of type BINARY, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::LONG);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::LONG, 'New Value object was not of type LONG, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::DECIMAL);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::DECIMAL, 'New Value object was not of type DECIMAL, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::DOUBLE);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::DOUBLE, 'New Value object was not of type DOUBLE, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::DATE);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::DATE, 'New Value object was not of type DATE, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::BOOLEAN);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::BOOLEAN, 'New Value object was not of type BOOLEAN, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::NAME);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::NAME, 'New Value object was not of type NAME, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::PATH);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::PATH, 'New Value object was not of type PATH, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::REFERENCE);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::REFERENCE, 'New Value object was not of type REFERENCE, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::WEAKREFERENCE);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::WEAKREFERENCE, 'New Value object was not of type WEAKREFERENCE, although requested.');

		$value = new \F3\TYPO3CR\Value(NULL, \F3\PHPCR\PropertyType::URI);
		$this->assertEquals($value->getType(), \F3\PHPCR\PropertyType::URI, 'New Value object was not of type URI, although requested.');

	}

	/**
	 * Checks if getString returns the expected result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getStringWorksOnStringValue() {
		$value = new \F3\TYPO3CR\Value('123.5 test', \F3\PHPCR\PropertyType::STRING);
		$this->assertSame($value->getString(), '123.5 test', 'getString() did not return the expected result.');
	}

	/**
	 * Checks if getString returns the expected result on a DATE value
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getStringWorksOnDateValue() {
		$value = new \F3\TYPO3CR\Value('2007-08-31T16:47+00:00', \F3\PHPCR\PropertyType::DATE);
		$this->assertSame($value->getString(), '2007-08-31T16:47:00+0000', 'getString() did not return the expected result.');
	}

	/**
	 * Checks if getBoolean returns the expected result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getBooleanWorksOnStringValue() {
		$value = new \F3\TYPO3CR\Value('123.5 test', \F3\PHPCR\PropertyType::STRING);
		$this->assertSame($value->getBoolean(), TRUE, 'getBoolean() did not return the expected result.');

		$value = new \F3\TYPO3CR\Value('0', \F3\PHPCR\PropertyType::STRING);
		$this->assertSame($value->getBoolean(), FALSE, 'getBoolean() did not return the expected result.');
	}

	/**
	 * Checks if getDouble returns the expected result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDoubleWorksOnString() {
		$value = new \F3\TYPO3CR\Value('123.5 test', \F3\PHPCR\PropertyType::STRING);
		$this->assertSame($value->getDouble(), 123.5, 'getDouble() did not return the expected result.');
	}

	/**
	 * Checks if getDecimal returns the expected result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDecimalWorksOnString() {
		$value = new \F3\TYPO3CR\Value('123.5 test', \F3\PHPCR\PropertyType::STRING);
		$this->assertSame($value->getDecimal(), 123.5, 'getDecimal() did not return the expected result.');
	}

	/**
	 * Checks if getLong returns the expected result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getLongWorksOnStringValue() {
		$value = new \F3\TYPO3CR\Value('123.5 test', \F3\PHPCR\PropertyType::STRING);
		$this->assertSame($value->getLong(), 123, 'getLong() did not return the expected result.');
	}

	/**
	 * Checks if getDate returns the expected result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDateWorksOnDateValue() {
		$value = new \F3\TYPO3CR\Value('2007-08-31T16:47+00:00', \F3\PHPCR\PropertyType::DATE);
		$DateTime = new \DateTime('2007-08-31T16:47+00:00');
		$this->assertEquals($value->getDate(), $DateTime, 'getDate() did not return the expected result.');
	}

	/**
	 * Checks if getDate returns the expected result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDateWorksOnStringValue() {
		$value = new \F3\TYPO3CR\Value('2007-08-31T16:47+00:00', \F3\PHPCR\PropertyType::STRING);
		$DateTime = new \DateTime('2007-08-31T16:47+00:00');
		$this->assertEquals($value->getDate(), $DateTime, 'getDate() did not return the expected result.');
	}
}
?>
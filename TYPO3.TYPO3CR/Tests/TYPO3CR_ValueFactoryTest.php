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

require_once('TYPO3CR_BaseTest.php');

/**
 * Tests for the ValueFactory implementation of TYPO3CR
 *
 * @package		TYPO3CR
 * @subpackage	Tests
 * @version 	$Id$
 * @author 		Karsten Dambekalns <karsten@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_ValueFactoryTest extends TYPO3CR_BaseTest {

	/**
	 * @var T3_phpCR_ValueFactory
	 */
	protected $valueFactory;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->valueFactory = $this->session->getValueFactory();
	}

	/**
	 * Checks if createValue can guess the STRING type
	 * @test
	 */
	public function createValueFromStringGuessesCorrectType() {
		$value = $this->valueFactory->createValue('This is a string');
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::STRING, 'New Value object was not of type STRING.');
	}

	/**
	 * Checks if createValue can guess the LONG type
	 * @test
	 */
	public function createValueFromLongGuessesCorrectType() {
		$value = $this->valueFactory->createValue(10);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::LONG, 'New Value object was not of type LONG.');
	}

	/**
	 * Checks if createValue can guess the DOUBLE type
	 * @test
	 */
	public function createValueFromDoubleGuessesCorrectType() {
		$value = $this->valueFactory->createValue(1.5);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::DOUBLE, 'New Value object was not of type DOUBLE.');
	}

	/**
	 * Checks if createValue can guess the BOOLEAN type
	 * @test
	 */
	public function createValueFromBooleanGuessesCorrectType() {
		$value = $this->valueFactory->createValue(FALSE);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::BOOLEAN, 'New Value object was not of type BOOLEAN.');
	}

	/**
	 * Checks if createValue can guess the DATE type
	 * @test
	 */
	public function createValueFromDateGuessesCorrectType() {
		$value = $this->valueFactory->createValue(new DateTime('2007-09-22'));
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::DATE, 'New Value object was not of type DATE.');
	}

	/**
	 * Checks if createValue can guess the REFERENCE type
	 * @test
	 */
	public function createValueFromReferenceGuessesCorrectType() {
		$node = $this->session->getRootNode();
		$value = $this->valueFactory->createValue($node);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::REFERENCE, 'New Value object was not of type REFERENCE.');
		$this->assertEquals($value->getString(), $node->getUUID(), 'The Value did not contain the UUID of the passed Node object.');
	}

	/**
	 * Checks if createValue can guess the BINARY type
	 * @test
	 */
	public function createValueFromBinaryGuessesCorrectType() {
		$fileHandle = fopen(TYPO3_PATH_ROOT . 'Packages/TYPO3CR/Tests/Fixtures/binaryGarbage.dat', 'rb');
		$value = $this->valueFactory->createValue($fileHandle);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::BINARY, 'New Value object was not of type BINARY.');
		$this->assertFalse(is_resource($fileHandle), 'ValueFactory did not close a passed file handle as expected.');

			// The following differentiates between PHP with is_binary and without.
		$data = file_get_contents(TYPO3_PATH_ROOT . 'Packages/TYPO3CR/Tests/Fixtures/binaryGarbage.dat');
		$value = $this->valueFactory->createValue($data);
		if(function_exists('is_binary')) {
			$this->assertEquals($value->getType(), T3_phpCR_PropertyType::BINARY, 'New Value object was not of type BINARY.');
		} else {
			$this->assertEquals($value->getType(), T3_phpCR_PropertyType::STRING, 'New Value object was not of type STRING.');
		}
	}

	/**
	 * Checks if type conversion works, if requested using createValue()
	 * @test
	 */
	public function createValueConvertsTypeIfRequested() {
		throw new PHPUnit_Framework_IncompleteTestError('Test not implemented yet.');
	}
}
?>

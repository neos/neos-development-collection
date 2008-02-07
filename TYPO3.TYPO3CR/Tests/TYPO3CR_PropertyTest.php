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
 * Tests for the Property implementation of TYPO3CR
 *
 * @package		TYPO3CR
 * @subpackage	Tests
 * @version 	$Id$
 * @author 		Karsten Dambekalns <karsten@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_PropertyTest extends TYPO3CR_BaseTest {

	/**
	 * Checks if getValue returns a Value object
	 * @test
	 */
	public function getValueReturnsAValueObject() {
		$uuid = '96bca35d-1ef5-4a47-8b0c-0bfc69507d04';
		$node = $this->session->getNodeByUUID($uuid);
		$valueObject = $node->getProperties()->next()->getValue();
		$this->assertType('T3_phpCR_ValueInterface', $valueObject, 'getValue() a Value object.');
	}

	/**
	 * Checks if getValues returns an exception if called with on a single value
	 * @test
	 */
	public function getValuesReturnsAnExceptionIfCalledOnSingleValue() {
		$uuid = '96bca35d-1ef5-4a47-8b0c-0bfc69507d04';
		$node = $this->session->getNodeByUUID($uuid);
		
		try {
			$valueObject = $node->getProperties()->next()->getValues();
			$this->fail('getValues needs to return an exception if called on a single value');
		} catch (T3_phpCR_ValueFormatException $e) {
		}
	}

	/**
	 * Checks if getPath works
	 * @test
	 */
	public function getPathWorks() {
		$uuid = '96bca35d-1ef5-4a47-8b0c-0bfc79507d08';
		$propertyPath = $this->session->getNodeByUUID($uuid)->getProperty('title')->getPath();
		$this->assertEquals($propertyPath, '/Content/Categories/Pages/Home/News/title', 'The path '.$propertyPath.' was not correct.');
	}
}
?>

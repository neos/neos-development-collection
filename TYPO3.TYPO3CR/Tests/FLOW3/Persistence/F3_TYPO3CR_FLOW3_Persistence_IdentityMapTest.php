<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::FLOW3::Persistence;

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
 * Testcase for F3::TYPO3CR::FLOW3::Persistence::IdentityMap
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class IdentityMapTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasObjectReturnsTrueForRegisteredObject() {
		$object1 = new stdClass();
		$object2 = new stdClass();
		$identityMap = new F3::TYPO3CR::FLOW3::Persistence::IdentityMap();
		$identityMap->registerObject($object1, 12345);

		$this->assertTrue($identityMap->hasObject($object1), 'IdentityMap claims it does not have registered object.');
		$this->assertFalse($identityMap->hasObject($object2), 'IdentityMap claims it does have unregistered object.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierReturnsRegisteredIdentifierForObject() {
		$object = new stdClass();
		$identityMap = new F3::TYPO3CR::FLOW3::Persistence::IdentityMap();
		$identityMap->registerObject($object, 12345);

		$this->assertEquals($identityMap->getIdentifier($object), 12345, 'Did not get identifier registered for object.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterObjectRemovesRegisteredObject() {
		$object1 = new stdClass();
		$object2 = new stdClass();
		$identityMap = new F3::TYPO3CR::FLOW3::Persistence::IdentityMap();
		$identityMap->registerObject($object1, 12345);
		$identityMap->registerObject($object2, 67890);

		$this->assertTrue($identityMap->hasObject($object1), 'IdentityMap claims it does not have registered object.');
		$this->assertTrue($identityMap->hasObject($object2), 'IdentityMap claims it does not have registered object.');

		$identityMap->unregisterObject($object1);

		$this->assertFalse($identityMap->hasObject($object1), 'IdentityMap claims it does have unregistered object.');
		$this->assertTrue($identityMap->hasObject($object2), 'IdentityMap claims it does not have registered object.');
	}

}

?>
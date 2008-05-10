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
 * Tests for the Repository implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_RepositoryTest extends F3_Testing_BaseTestCase {

	/**
	 * Checks of the login() method returns a Session object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function repositoryLoginReturnsASession() {
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');
		$repository = new F3_TYPO3CR_Repository($this->componentManager, $mockStorageAccess);
		$session = $repository->login();
		$this->assertType('F3_phpCR_SessionInterface', $session, 'The repository login did not return a session object.');
	}

	/**
	 * Credentials of an invalid type must throw an exception
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function credentialsOfInvalidTypeThrowException() {
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');
		$repository = new F3_TYPO3CR_Repository($this->componentManager, $mockStorageAccess);
		try {
			$session = $repository->login(new ArrayObject);
			$this->fail('Invalid credentials did not throw an exception.');
		} catch (Exception $exception) {
			$this->assertTrue($exception instanceof F3_phpCR_RepositoryException, 'The thrown exception is not of the expected type.');
		}
	}

	/**
	 * Checks if getDesciptorKeys returns an array and if the values are all strings.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDescriptorKeysReturnsAnArray() {
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');
		$repository = new F3_TYPO3CR_Repository($this->componentManager, $mockStorageAccess);
		$descriptorKeys = $repository->getDescriptorKeys();
		$this->assertTrue(is_array($descriptorKeys), 'The getDescriptorKeys method did not return an array.');
		foreach ($descriptorKeys as $k => $v) {
			$this->assertTrue(is_string($v), 'An element (' . $v . ') returned by getDescriptorKeys was not a string.');
		}
	}

	/**
	 * Checks if getDesciptor('SPEC_VERSION_DESC') returns '2.0'.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDescriptorReturnsCorrectVersionString() {
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');
		$repository = new F3_TYPO3CR_Repository($this->componentManager, $mockStorageAccess);
		$descriptor = $repository->getDescriptor('SPEC_VERSION_DESC');
		$this->assertEquals('2.0', $descriptor, 'getDescriptor(\'SPEC_VERSION_DESC\') did not return \'2.0\'.');
	}
}
?>

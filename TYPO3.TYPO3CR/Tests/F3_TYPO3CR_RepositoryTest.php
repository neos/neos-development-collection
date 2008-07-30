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
	 * Probably the most mocked & stubbed test in FLOW3.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function repositoryLoginAsksForASessionToReturn() {
		$mockStorageBackend = $this->getMock('F3_TYPO3CR_Storage_BackendInterface');
		$mockTYPO3CRSession = $this->getMock('F3_PHPCR_SessionInterface', array(), array(), '', FALSE);

		$settings = new F3_FLOW3_Configuration_Container();
		$settings->storage->backend = 'mockStorageBackend';
		$settings->storage->backendOptions = array();
		$configurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array(), array(), '', FALSE);
		$configurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$componentFactory = $this->getMock('F3_FLOW3_Component_Factory', array(), array(), '', FALSE);
		$componentFactory->expects($this->exactly(3))->method('getComponent')->will($this->onConsecutiveCalls($configurationManager, $mockStorageBackend, $mockTYPO3CRSession));

		$repository = new F3_TYPO3CR_Repository($componentFactory);
		$session = $repository->login();
		$this->assertSame($mockTYPO3CRSession, $session, 'The repository login did not return the requested session object.');
	}

	/**
	 * @test
	 * @expectedException F3_PHPCR_RepositoryException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function credentialsOfInvalidTypeThrowException() {
		$repository = new F3_TYPO3CR_Repository($this->componentFactory);
		$repository->login(new ArrayObject);
	}

	/**
	 * Checks if getDesciptorKeys returns an array and if the values are all strings.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDescriptorKeysReturnsAnArrayOfStrings() {
		$repository = new F3_TYPO3CR_Repository($this->componentFactory);
		$descriptorKeys = $repository->getDescriptorKeys();
		$this->assertTrue(is_array($descriptorKeys), 'The getDescriptorKeys method did not return an array.');
		foreach ($descriptorKeys as $k => $v) {
			$this->assertTrue(is_string($v), 'An element (' . $k . ' => ' . $v . ') returned by getDescriptorKeys was not a string.');
		}
	}

	/**
	 * Checks if getDesciptor(SPEC_VERSION_DESC) returns '2.0'.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDescriptorReturnsCorrectVersionString() {
		$repository = new F3_TYPO3CR_Repository($this->componentFactory);
		$descriptor = $repository->getDescriptor(F3_TYPO3CR_Repository::SPEC_VERSION_DESC);
		$this->assertEquals('2.0', $descriptor, 'getDescriptor(SPEC_VERSION_DESC) did not return \'2.0\'.');
	}
}
?>
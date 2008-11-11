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
 * Tests for the Repository implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RepositoryTest extends F3::Testing::BaseTestCase {

	/**
	 * Probably the most mocked & stubbed test in FLOW3.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function repositoryLoginAsksForASessionToReturn() {
		$mockNamespaceRegistry = $this->getMock('F3::TYPO3CR::NamespaceRegistry', array(), array(), '', FALSE);
		$mockWorkspace = $this->getMock('F3::TYPO3CR::Workspace', array(), array(), '', FALSE);
		$mockWorkspace->expects($this->once())->method('getNamespaceRegistry')->will($this->returnValue($mockNamespaceRegistry));
		$mockStorageBackend = $this->getMock('F3::TYPO3CR::Storage::BackendInterface');
		$mockTYPO3CRSession = $this->getMock('F3::PHPCR::SessionInterface', array(), array(), '', FALSE);
		$mockTYPO3CRSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSearchEngine = $this->getMock('F3::TYPO3CR::Storage::SearchInterface');

		$settings = array();
		$settings['storage']['backend'] = 'mockStorageBackend';
		$settings['storage']['backendOptions'] = array();
		$settings['search']['backend'] = 'mockSearchEngine';
		$settings['search']['backendOptions'] = array();
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$objectFactory = $this->getMock('F3::FLOW3::Object::Factory', array(), array(), '', FALSE);
		$objectFactory->expects($this->exactly(3))->method('create')->will($this->onConsecutiveCalls($mockStorageBackend, $mockSearchEngine, $mockTYPO3CRSession));

		$repository = new F3::TYPO3CR::Repository($objectFactory);
		$repository->injectConfigurationManager($mockConfigurationManager);
		$session = $repository->login();
		$this->assertSame($mockTYPO3CRSession, $session, 'The repository login did not return the requested session object.');
	}

	/**
	 * @test
	 * @expectedException F3::PHPCR::RepositoryException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function credentialsOfInvalidTypeThrowException() {
		$repository = new F3::TYPO3CR::Repository($this->objectFactory);
		$repository->login(new ::ArrayObject);
	}

	/**
	 * Checks if getDesciptorKeys returns an array and if the values are all strings.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getDescriptorKeysReturnsAnArrayOfStrings() {
		$repository = new F3::TYPO3CR::Repository($this->objectFactory);
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
		$repository = new F3::TYPO3CR::Repository($this->objectFactory);
		$descriptor = $repository->getDescriptor(F3::TYPO3CR::Repository::SPEC_VERSION_DESC);
		$this->assertEquals('2.0', $descriptor, 'getDescriptor(SPEC_VERSION_DESC) did not return \'2.0\'.');
	}
}
?>
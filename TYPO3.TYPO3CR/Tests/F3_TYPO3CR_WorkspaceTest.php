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
 * Tests for the Workspace implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_WorkspaceTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_TYPO3CR_Session
	 */
	protected $mockSession;

	/**
	 * @var F3_TYPO3CR_StorageAccessInterface
	 */
	protected $mockStorageAccess;

	/**
	 * @var F3_TYPO3CR_Workspace
	 */
	protected $workspace;

	/**
	 * Set up the test environment
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array(), '', FALSE);
		$this->mockStorageAccess = $this->getMock('F3_TYPO3CR_StorageAccessInterface');
		$this->workspace = new F3_TYPO3CR_Workspace('workspaceName', $this->mockSession, $this->mockStorageAccess, $this->componentManager);
	}

	/**
	 * Checks if getSession returns the same Session object used to create the Workspace object.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getSessionReturnsCreatingSession() {
		$this->assertSame($this->mockSession, $this->workspace->getSession(), 'The workspace did not return the session from which it was created.');
	}

	/**
	 * Checks if getNamespaceRegistry() returns a NameSpaceRegistry object.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNamespaceRegistryReturnsANameSpaceRegistry() {
		$this->assertType('F3_PHPCR_NamespaceRegistryInterface', $this->workspace->getNamespaceRegistry(), 'The workspace did not return a NamespaceRegistry object on getNamespaceRegistry().');
	}

	/**
	 * Checks if getName() returns the expected string.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNameReturnsTheExpectedName() {
		$this->assertSame('workspaceName', $this->workspace->getName(), 'The workspace did not return the expected name on getName().');
	}
}
?>
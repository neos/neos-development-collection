<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Tests for the Workspace implementation of TYPO3CR
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class WorkspaceTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3CR\Session
	 */
	protected $mockSession;

	/**
	 * @var \F3\TYPO3CR\Storage\BackendInterface
	 */
	protected $mockStorageBackend;

	/**
	 * @var \F3\TYPO3CR\Workspace
	 */
	protected $workspace;

	/**
	 * Set up the test environment
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$this->mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->getMock('F3\TYPO3CR\Storage\BackendInterface')));
		$this->workspace = new \F3\TYPO3CR\Workspace('workspaceName', $this->mockSession, $this->objectFactory);
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
		$this->assertType('F3\PHPCR\NamespaceRegistryInterface', $this->workspace->getNamespaceRegistry(), 'The workspace did not return a NamespaceRegistry object on getNamespaceRegistry().');
	}

	/**
	 * Checks if getName() returns the expected string.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNameReturnsTheExpectedName() {
		$this->assertSame('workspaceName', $this->workspace->getName(), 'The workspace did not return the expected name on getName().');
	}

	/**
	 * Checks if getNodeTypeManager() returns a NodeTypeManager object.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeTypeManagerReturnsANodeTypeManager() {
		$this->assertType('F3\PHPCR\NodeType\NodeTypeManagerInterface', $this->workspace->getNodeTypeManager(),'The workspace did not return a NodeTypeManager object on getNodeTypeManager().');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getQueryManagerReturnsAQueryManager() {
		$this->assertType('F3\PHPCR\Query\QueryManagerInterface', $this->workspace->getQueryManager(),'The workspace did not return a QueryManager object on getQueryManager().');
	}

}
?>
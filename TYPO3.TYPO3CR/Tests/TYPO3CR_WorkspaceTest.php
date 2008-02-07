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
 * Tests for the Workspace implementation of TYPO3CR
 *
 * @package		TYPO3CR
 * @subpackage	Tests
 * @version 	$Id$
 * @author 		Karsten Dambekalns <karsten@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_WorkspaceTest extends TYPO3CR_BaseTest {

	/**
	 * @var T3_TYPO3CR_Workspace
	 */
	protected $workspace;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->workspace = $this->session->getWorkspace();
	}

	/**
	 * Checks if getSession returns the Session object used to create the Workspace object.
	 * @test
	 */
	public function getSessionReturnsCreatingSession() {
		$this->assertSame($this->session, $this->workspace->getSession(), 'The workspace did not return the session from which it was created.');
	}

	/**
	 * Checks if getSession() returns the same session as was used to aquire the workspace object.
	 * @test
	 */
	public function getNamespaceRegistryReturnsANameSpaceRegistry() {
		$this->assertType('T3_phpCR_NamespaceRegistryInterface', $this->workspace->getNamespaceRegistry(), 'The workspace did not return a NamespaceRegistry object on getNamespaceRegistry().');
	}
	
	/**
	 * Checks if getName() returns a string.
	 * @test
	 */
	public function getNameReturnsAString() {
		$this->assertType('string', $this->workspace->getName(), 'The workspace did not return a string on getName().');
	}
}
?>

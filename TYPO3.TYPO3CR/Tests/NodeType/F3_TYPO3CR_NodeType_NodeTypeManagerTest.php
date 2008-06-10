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
class F3_TYPO3CR_NodeType_NodeTypeManagerTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_TYPO3CR_Storage_BackendInterface
	 */
	protected $mockStorageAccess;

	/**
	 * @var F3_TYPO3CR_NodeType_NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * Set up the test environment
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockStorageAccess = $this->getMock('F3_TYPO3CR_Storage_BackendInterface');
		$this->nodeTypeManager = new F3_TYPO3CR_NodeType_NodeTypeManager($this->mockStorageAccess, $this->componentManager);
	}

	/**
	 * Checks if createNodeTypeTemplate() returns the an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeTypeTemplateReturnsEmptyTemplate() {
		$nodeTypeTemplate = new F3_TYPO3CR_NodeType_NodeTypeTemplate();
		$this->assertEquals($nodeTypeTemplate, $this->nodeTypeManager->createNodeTypeTemplate(), 'The nodetype manager did not return the expected empty nodetype template.');
	}

		/**
	 * Checks if createNodeDefinitionTemplate() returns the an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeDefinitionTemplateReturnsEmptyTemplate() {
		$nodeDefinitionTemplate = new F3_TYPO3CR_NodeType_NodeDefinitionTemplate();
		$this->assertEquals($nodeDefinitionTemplate, $this->nodeTypeManager->createNodeDefinitionTemplate(), 'The nodetype manager did not return the expected empty node definition template.');
	}

	/**
	 * Checks if createPropertyDefinitionTemplate() returns the an empty propertydefinition template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createPropertyDefinitionTemplateReturnsEmptyTemplate() {
		$propertyDefinitionTemplate = new F3_TYPO3CR_NodeType_PropertyDefinitionTemplate();
		$this->assertEquals($propertyDefinitionTemplate, $this->nodeTypeManager->createPropertyDefinitionTemplate(), 'The nodetype manager did not return the expected empty property definition template.');
	}

}
?>
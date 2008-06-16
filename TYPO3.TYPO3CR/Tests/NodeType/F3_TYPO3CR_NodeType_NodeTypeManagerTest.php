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
	 * Checks if createNodeTypeTemplate() returns an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeTypeTemplateReturnsEmptyTemplate() {
		$nodeTypeTemplate = new F3_TYPO3CR_NodeType_NodeTypeTemplate();
		$this->assertEquals($nodeTypeTemplate, $this->nodeTypeManager->createNodeTypeTemplate(), 'The nodetype manager did not return the expected empty nodetype template.');
	}

	/**
	 * Checks if createNodeDefinitionTemplate() returns an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeDefinitionTemplateReturnsEmptyTemplate() {
		$nodeDefinitionTemplate = new F3_TYPO3CR_NodeType_NodeDefinitionTemplate();
		$this->assertEquals($nodeDefinitionTemplate, $this->nodeTypeManager->createNodeDefinitionTemplate(), 'The nodetype manager did not return the expected empty node definition template.');
	}

	/**
	 * Checks if createPropertyDefinitionTemplate() returns an empty propertydefinition template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createPropertyDefinitionTemplateReturnsEmptyTemplate() {
		$propertyDefinitionTemplate = new F3_TYPO3CR_NodeType_PropertyDefinitionTemplate();
		$this->assertEquals($propertyDefinitionTemplate, $this->nodeTypeManager->createPropertyDefinitionTemplate(), 'The nodetype manager did not return the expected empty property definition template.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeTypeThrowsExceptionOnUnknownNodeType() {
		$this->mockStorageAccess->expects($this->once())->method('getRawNodeType')->with('unknownNodeTypeName')->will($this->returnValue(FALSE));
		try {
			$this->nodeTypeManager->getNodeType('unknownNodeTypeName');
			$this->fail('When asked for an unknown NodeType getNodeType must throw a NoSuchNodeTypeException');
		} catch (F3_PHPCR_NodeType_NoSuchNodeTypeException $e) {
		}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypesAcceptsOnlyNodeTypeDefinitions() {
		$input = array(
			new F3_TYPO3CR_NodeType_NodeTypeDefinition(),
			'some string',
			123,
			new F3_TYPO3CR_NodeType_NodeTypeDefinition()
		);
		try {
			$this->nodeTypeManager->registerNodeTypes($input, FALSE);
			$this->fail('registerNodeTypes must only accept an array of NodeTypeDefinition');
		} catch (F3_PHPCR_NodeType_InvalidNodeTypeDefinitionException $e) {
		}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypeReturnsNodeTypeOnSuccess() {
		$this->mockStorageAccess->expects($this->once())->method('getRawNodeType')->with('testNodeType')->will($this->returnValue(array('name' => 'testNodeType')));
		$nodeTypeTemplate = new F3_TYPO3CR_NodeType_NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$nodeType = $this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		$this->assertType('F3_PHPCR_NodeType_NodeTypeInterface', $nodeType, 'registerNodeType did not return a NodeType');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypeReturnsCallsStorageBackend() {
		$nodeTypeTemplate = new F3_TYPO3CR_NodeType_NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$this->mockStorageAccess->expects($this->once())->method('addNodeType')->with($nodeTypeTemplate);
		$this->mockStorageAccess->expects($this->once())->method('getRawNodeType')->with('testNodeType')->will($this->returnValue(array('name' => 'testNodeType')));
		$this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function unregisterNodeTypeThrowsExceptionOnUnknownNodeType() {
		$this->mockStorageAccess->expects($this->once())->method('getRawNodeType')->with('unknownNodeTypeName')->will($this->returnValue(FALSE));
		try {
			$this->nodeTypeManager->unregisterNodeType('unknownNodeTypeName');
			$this->fail('When asked to unregister an unknown NodeType unregisterNodeType must throw a NoSuchNodeTypeException');
		} catch (F3_PHPCR_NodeType_NoSuchNodeTypeException $e) {
		}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function unregisterNodeTypeRemovesNodeType() {
		$this->mockStorageAccess->expects($this->exactly(2))->method('getRawNodeType')->with('testNodeTypeName')->will($this->onConsecutiveCalls(array('name' => 'testNodeTypeName'), FALSE));
		$nodeTypeDefintionTemplate = new F3_TYPO3CR_NodeType_NodeTypeTemplate();
		$nodeTypeDefintionTemplate->setName('testNodeTypeName');
		$this->nodeTypeManager->registerNodeType($nodeTypeDefintionTemplate, FALSE);
		$this->nodeTypeManager->unregisterNodeType('testNodeTypeName');
		try {
			$this->nodeTypeManager->getNodeType('testNodeTypeName');
			$this->fail('unregisterNodeType did not remove the nodetype');
		} catch (F3_PHPCR_NodeType_NoSuchNodeTypeException $e) {
		}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function unregisterNodeTypeReturnsCallsStorageBackend() {
		$nodeTypeTemplate = new F3_TYPO3CR_NodeType_NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$this->mockStorageAccess->expects($this->once())->method('getRawNodeType')->with('testNodeType')->will($this->returnValue(array('name' => 'testNodeType')));
		$this->mockStorageAccess->expects($this->once())->method('deleteNodeType')->with('testNodeType');
		$this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		$this->nodeTypeManager->unregisterNodeType('testNodeType');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function nodeTypeManagerLoadsExistingNodeTypes() {
		$this->mockStorageAccess->expects($this->atLeastOnce())->method('getRawNodeTypes')->will($this->returnValue(array(array('name' => 'nt:base'))));
		$nodeTypeManager = new F3_TYPO3CR_NodeType_NodeTypeManager($this->mockStorageAccess, $this->componentManager);
		$this->assertTrue($nodeTypeManager->hasNodeType('nt:base'), 'nt:base is missing');
	}
}
?>
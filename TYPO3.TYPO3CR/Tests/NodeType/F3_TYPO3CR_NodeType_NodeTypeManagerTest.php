<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::NodeType;

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
class NodeTypeManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::TYPO3CR::Storage::BackendInterface
	 */
	protected $mockStorageBackend;

	/**
	 * @var F3::TYPO3CR::NodeType::NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * Set up the test environment
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockStorageBackend = $this->getMock('F3::TYPO3CR::Storage::BackendInterface');
		$this->nodeTypeManager = new F3::TYPO3CR::NodeType::NodeTypeManager($this->mockStorageBackend, $this->componentFactory);
	}

	/**
	 * Checks if createNodeTypeTemplate() returns an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeTypeTemplateReturnsEmptyTemplate() {
		$nodeTypeTemplate = new F3::TYPO3CR::NodeType::NodeTypeTemplate();
		$this->assertEquals($nodeTypeTemplate, $this->nodeTypeManager->createNodeTypeTemplate(), 'The nodetype manager did not return the expected empty nodetype template.');
	}

	/**
	 * Checks if createNodeDefinitionTemplate() returns an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeDefinitionTemplateReturnsEmptyTemplate() {
		$nodeDefinitionTemplate = new F3::TYPO3CR::NodeType::NodeDefinitionTemplate();
		$this->assertEquals($nodeDefinitionTemplate, $this->nodeTypeManager->createNodeDefinitionTemplate(), 'The nodetype manager did not return the expected empty node definition template.');
	}

	/**
	 * Checks if createPropertyDefinitionTemplate() returns an empty propertydefinition template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createPropertyDefinitionTemplateReturnsEmptyTemplate() {
		$propertyDefinitionTemplate = new F3::TYPO3CR::NodeType::PropertyDefinitionTemplate();
		$this->assertEquals($propertyDefinitionTemplate, $this->nodeTypeManager->createPropertyDefinitionTemplate(), 'The nodetype manager did not return the expected empty property definition template.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeTypeThrowsExceptionOnUnknownNodeType() {
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('unknownNodeTypeName')->will($this->returnValue(FALSE));
		try {
			$this->nodeTypeManager->getNodeType('unknownNodeTypeName');
			$this->fail('When asked for an unknown NodeType getNodeType must throw a NoSuchNodeTypeException');
		} catch (F3::PHPCR::NodeType::NoSuchNodeTypeException $e) {
		}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypesAcceptsOnlyNodeTypeDefinitions() {
		$input = array(
			new F3::TYPO3CR::NodeType::NodeTypeDefinition(),
			'some string',
			123,
			new F3::TYPO3CR::NodeType::NodeTypeDefinition()
		);
		try {
			$this->nodeTypeManager->registerNodeTypes($input, FALSE);
			$this->fail('registerNodeTypes must only accept an array of NodeTypeDefinition');
		} catch (F3::PHPCR::NodeType::InvalidNodeTypeDefinitionException $e) {
		}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypeReturnsNodeTypeOnSuccess() {
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('testNodeType')->will($this->returnValue(array('name' => 'testNodeType')));
		$nodeTypeTemplate = new F3::TYPO3CR::NodeType::NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$nodeType = $this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		$this->assertType('F3::PHPCR::NodeType::NodeTypeInterface', $nodeType, 'registerNodeType did not return a NodeType');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypeReturnsCallsStorageBackend() {
		$nodeTypeTemplate = new F3::TYPO3CR::NodeType::NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$this->mockStorageBackend->expects($this->once())->method('addNodeType')->with($nodeTypeTemplate);
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('testNodeType')->will($this->returnValue(array('name' => 'testNodeType')));
		$this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function unregisterNodeTypeThrowsExceptionOnUnknownNodeType() {
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('unknownNodeTypeName')->will($this->returnValue(FALSE));
		try {
			$this->nodeTypeManager->unregisterNodeType('unknownNodeTypeName');
			$this->fail('When asked to unregister an unknown NodeType unregisterNodeType must throw a NoSuchNodeTypeException');
		} catch (F3::PHPCR::NodeType::NoSuchNodeTypeException $e) {
		}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function unregisterNodeTypeRemovesNodeType() {
		$this->mockStorageBackend->expects($this->exactly(2))->method('getRawNodeType')->with('testNodeTypeName')->will($this->onConsecutiveCalls(array('name' => 'testNodeTypeName'), FALSE));
		$nodeTypeDefintionTemplate = new F3::TYPO3CR::NodeType::NodeTypeTemplate();
		$nodeTypeDefintionTemplate->setName('testNodeTypeName');
		$this->nodeTypeManager->registerNodeType($nodeTypeDefintionTemplate, FALSE);
		$this->nodeTypeManager->unregisterNodeType('testNodeTypeName');
		try {
			$this->nodeTypeManager->getNodeType('testNodeTypeName');
			$this->fail('unregisterNodeType did not remove the nodetype');
		} catch (F3::PHPCR::NodeType::NoSuchNodeTypeException $e) {
		}
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function unregisterNodeTypeReturnsCallsStorageBackend() {
		$nodeTypeTemplate = new F3::TYPO3CR::NodeType::NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('testNodeType')->will($this->returnValue(array('name' => 'testNodeType')));
		$this->mockStorageBackend->expects($this->once())->method('deleteNodeType')->with('testNodeType');
		$this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		$this->nodeTypeManager->unregisterNodeType('testNodeType');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function nodeTypeManagerLoadsExistingNodeTypes() {
		$this->mockStorageBackend->expects($this->atLeastOnce())->method('getRawNodeTypes')->will($this->returnValue(array(array('name' => 'nt:base'))));
		$nodeTypeManager = new F3::TYPO3CR::NodeType::NodeTypeManager($this->mockStorageBackend, $this->componentFactory);
		$this->assertTrue($nodeTypeManager->hasNodeType('nt:base'), 'nt:base is missing');
	}
}
?>
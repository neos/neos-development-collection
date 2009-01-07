<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\NodeType;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class NodeTypeManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3CR\Storage\BackendInterface
	 */
	protected $mockStorageBackend;

	/**
	 * @var \F3\TYPO3CR\NodeType\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * Set up the test environment
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$this->nodeTypeManager = new \F3\TYPO3CR\NodeType\NodeTypeManager($this->mockStorageBackend, $this->objectFactory);
	}

	/**
	 * Checks if createNodeTypeTemplate() returns an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeTypeTemplateReturnsEmptyTemplate() {
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$this->assertEquals($nodeTypeTemplate, $this->nodeTypeManager->createNodeTypeTemplate(), 'The nodetype manager did not return the expected empty nodetype template.');
	}

	/**
	 * Checks if createNodeDefinitionTemplate() returns an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeDefinitionTemplateReturnsEmptyTemplate() {
		$nodeDefinitionTemplate = new \F3\TYPO3CR\NodeType\NodeDefinitionTemplate();
		$this->assertEquals($nodeDefinitionTemplate, $this->nodeTypeManager->createNodeDefinitionTemplate(), 'The nodetype manager did not return the expected empty node definition template.');
	}

	/**
	 * Checks if createPropertyDefinitionTemplate() returns an empty propertydefinition template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createPropertyDefinitionTemplateReturnsEmptyTemplate() {
		$propertyDefinitionTemplate = new \F3\TYPO3CR\NodeType\PropertyDefinitionTemplate();
		$this->assertEquals($propertyDefinitionTemplate, $this->nodeTypeManager->createPropertyDefinitionTemplate(), 'The nodetype manager did not return the expected empty property definition template.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\NoSuchNodeTypeException
	 */
	public function getNodeTypeThrowsExceptionOnUnknownNodeType() {
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('unknownNodeTypeName')->will($this->returnValue(FALSE));
		$this->nodeTypeManager->getNodeType('unknownNodeTypeName');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\InvalidNodeTypeDefinitionException
	 */
	public function registerNodeTypesAcceptsOnlyNodeTypeDefinitions() {
		$input = array(
			new \F3\TYPO3CR\NodeType\NodeTypeDefinition(),
			'some string',
			123,
			new \F3\TYPO3CR\NodeType\NodeTypeDefinition()
		);
		$this->nodeTypeManager->registerNodeTypes($input, FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypeReturnsNodeTypeOnSuccess() {
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('testNodeType')->will($this->returnValue(array('name' => 'testNodeType')));
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$nodeType = $this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		$this->assertType('F3\PHPCR\NodeType\NodeTypeInterface', $nodeType, 'registerNodeType did not return a NodeType');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypeReturnsCallsStorageBackend() {
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$this->mockStorageBackend->expects($this->once())->method('addNodeType')->with($nodeTypeTemplate);
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('testNodeType')->will($this->returnValue(array('name' => 'testNodeType')));
		$this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\NoSuchNodeTypeException
	 */
	public function unregisterNodeTypeThrowsExceptionOnUnknownNodeType() {
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('unknownNodeTypeName')->will($this->returnValue(FALSE));
		$this->nodeTypeManager->unregisterNodeType('unknownNodeTypeName');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\NoSuchNodeTypeException
	 */
	public function unregisterNodeTypeRemovesNodeType() {
		$this->mockStorageBackend->expects($this->exactly(2))->method('getRawNodeType')->with('testNodeTypeName')->will($this->onConsecutiveCalls(array('name' => 'testNodeTypeName'), FALSE));
		$nodeTypeDefintionTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeDefintionTemplate->setName('testNodeTypeName');
		$this->nodeTypeManager->registerNodeType($nodeTypeDefintionTemplate, FALSE);
		$this->nodeTypeManager->unregisterNodeType('testNodeTypeName');

		$this->nodeTypeManager->getNodeType('testNodeTypeName');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function unregisterNodeTypeReturnsCallsStorageBackend() {
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
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
		$nodeTypeManager = new \F3\TYPO3CR\NodeType\NodeTypeManager($this->mockStorageBackend, $this->objectFactory);
		$this->assertTrue($nodeTypeManager->hasNodeType('nt:base'), 'nt:base is missing');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\NodeTypeExistsException
	 */
	public function registerNodeTypeThrowsExceptionIfNodeTypeExistsAndUpdateIsDisallowed() {
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		$this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\NodeTypeExistsException
	 */
	public function registerNodeTypesThrowsExceptionIfNodeTypeExistsAndUpdateIsDisallowed() {
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$this->nodeTypeManager->registerNodeTypes(array($nodeTypeTemplate, $nodeTypeTemplate), FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypesRegistersNothingIfNodeTypeExistsAndUpdateIsDisallowed() {
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');
		$this->mockStorageBackend->expects($this->once())->method('addNodeType')->with($nodeTypeTemplate);
		$this->mockStorageBackend->expects($this->once())->method('getRawNodeType')->with('testNodeType')->will($this->returnValue(array('name' => 'testNodeType')));
		$nodeTypeTemplate1 = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate1->setName('testNodeType1');

		$this->nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		try {
			$this->nodeTypeManager->registerNodeTypes(array($nodeTypeTemplate1, $nodeTypeTemplate), FALSE);
		} catch(\F3\PHPCR\NodeType\NodeTypeExistsException $e) {
				// assertion is the addNodeType expectation, not the exception!
		}
	}

}
?>
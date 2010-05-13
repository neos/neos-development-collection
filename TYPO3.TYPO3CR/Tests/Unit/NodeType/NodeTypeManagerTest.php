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
 * Tests for the Workspace implementation of TYPO3CR
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

		$this->mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$this->nodeTypeManager = new \F3\TYPO3CR\NodeType\NodeTypeManager($this->mockStorageBackend, $this->mockObjectManager);
	}

	/**
	 * Checks if createNodeTypeTemplate() returns an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeTypeTemplateAsksObjectManagerForNodeTypeTemplateInterface() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\NodeType\NodeTypeTemplateInterface');
		$this->nodeTypeManager->createNodeTypeTemplate();
	}

	/**
	 * Checks if createNodeDefinitionTemplate() returns an empty nodetype template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createNodeDefinitionTemplateReturnsEmptyTemplate() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\NodeType\NodeDefinitionTemplateInterface');
		$this->nodeTypeManager->createNodeDefinitionTemplate();
	}

	/**
	 * Checks if createPropertyDefinitionTemplate() returns an empty propertydefinition template.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createPropertyDefinitionTemplateReturnsEmptyTemplate() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\NodeType\PropertyDefinitionTemplateInterface');
		$this->nodeTypeManager->createPropertyDefinitionTemplate();
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
		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$nodeTypeManager = $this->getMock('F3\TYPO3CR\NodeType\NodeTypeManager', array('getNodeType'), array($this->mockStorageBackend, $this->mockObjectManager));
		$nodeTypeManager->expects($this->once())->method('getNodeType')->with('testNodeType')->will($this->returnValue($nodeType));
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');

		$returnedNodeType = $nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
		$this->assertSame($nodeType, $returnedNodeType, 'registerNodeType did not return a NodeType');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function registerNodeTypeCallsStorageBackend() {
		$nodeTypeManager = $this->getMock('F3\TYPO3CR\NodeType\NodeTypeManager', array('getNodeType'), array($this->mockStorageBackend, $this->mockObjectManager));
		$nodeTypeManager->expects($this->once())->method('getNodeType')->with('testNodeType');
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');

		$this->mockStorageBackend->expects($this->once())->method('addNodeType')->with($nodeTypeTemplate);
		$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\NoSuchNodeTypeException
	 */
	public function unregisterNodeTypeThrowsExceptionOnUnknownNodeType() {
		$nodeTypeManager = $this->getMock('F3\TYPO3CR\NodeType\NodeTypeManager', array('getNodeType'), array($this->mockStorageBackend, $this->mockObjectManager));
		$nodeTypeManager->expects($this->once())->method('getNodeType')->with('unknownNodeTypeName')->will($this->throwException(new \F3\PHPCR\NodeType\NoSuchNodeTypeException()));
		$nodeTypeManager->unregisterNodeType('unknownNodeTypeName');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function unregisterNodeTypeRemovesNodeType() {
		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$nodeTypeManager = $this->getMock('F3\TYPO3CR\NodeType\NodeTypeManager', array('getNodeType'), array($this->mockStorageBackend, $this->mockObjectManager));
		$nodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnValue($nodeType));
		$this->mockStorageBackend->expects($this->once())->method('deleteNodeType')->with('testNodeTypeName');
		$nodeTypeDefintionTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeDefintionTemplate->setName('testNodeTypeName');
		$nodeTypeManager->unregisterNodeType('testNodeTypeName');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function nodeTypeManagerLoadsExistingNodeTypes() {
		$nodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\PHPCR\NodeType\NodeTypeInterface', 'nt:base')->will($this->returnValue($nodeType));
		$this->mockStorageBackend->expects($this->atLeastOnce())->method('getRawNodeTypes')->will($this->returnValue(array(array('name' => 'nt:base'))));
		new \F3\TYPO3CR\NodeType\NodeTypeManager($this->mockStorageBackend, $this->mockObjectManager);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\NodeTypeExistsException
	 */
	public function registerNodeTypeThrowsExceptionIfNodeTypeExistsAndUpdateIsDisallowed() {
		$nodeTypeManager = $this->getMock('F3\TYPO3CR\NodeType\NodeTypeManager', array('hasNodeType'), array($this->mockStorageBackend, $this->mockObjectManager));
		$nodeTypeManager->expects($this->any())->method('hasNodeType')->with('testNodeTypeName')->will($this->returnValue(TRUE));
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeTypeName');
		$nodeTypeManager->registerNodeType($nodeTypeTemplate, FALSE);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\NodeType\NodeTypeExistsException
	 */
	public function registerNodeTypesThrowsExceptionIfNodeTypeExistsAndUpdateIsDisallowed() {
		$nodeTypeManager = $this->getMock('F3\TYPO3CR\NodeType\NodeTypeManager', array('hasNodeType', 'registerNodeType'), array($this->mockStorageBackend, $this->mockObjectManager));
		$nodeTypeManager->expects($this->any())->method('hasNodeType')->with('testNodeTypeName')->will($this->returnValue(TRUE));
		$nodeTypeManager->expects($this->never())->method('registerNodeType');
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeTypeName');
		$nodeTypeManager->registerNodeTypes(array($nodeTypeTemplate), FALSE);
	}

}
?>
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
 * @version $Id:F3_TYPO3CR_Storage_Backend_TestBase.php 888 2008-05-30 16:00:05Z k-fish $
 */

/**
 * Tests for the storage backend implementations of TYPO3CR. Needs to be extended
 * for various storage types
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id:F3_TYPO3CR_Storage_Backend_TestBase.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Storage_Backend_TestBase extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_TYPO3CR_Storage_BackendInterface
	 */
	protected $storageAccess;

	/**
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageAccess, $this->componentManager));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageAccess));

		$rawRootNode = array(
			'parent' => 0,
			'name' => '',
			'identifier' => F3_FLOW3_Utility_Algorithms::generateUUID(),
			'nodetype' => 'nt:base'
		);
		$rootNode = new F3_TYPO3CR_Node($rawRootNode, $mockSession, $this->componentManager);

		$identifier = F3_FLOW3_Utility_Algorithms::generateUUID();
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);
		$node = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentManager);
		$expectedRawNode = array(
			'parent' => $rootNode->getIdentifier(),
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);

		$this->storageAccess->addNode($node);
		$retrievedRawNode = $this->storageAccess->getRawNodeByIdentifier($identifier);
		$this->assertSame($expectedRawNode, $retrievedRawNode, 'The returned raw node had not the expected values.');
	}

	/**
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeNodeWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageAccess, $this->componentManager));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageAccess));

		$rawRootNode = array(
			'parent' => 0,
			'name' => '',
			'identifier' => F3_FLOW3_Utility_Algorithms::generateUUID(),
			'nodetype' => 'nt:base'
		);
		$rootNode = new F3_TYPO3CR_Node($rawRootNode, $mockSession, $this->componentManager);

		$identifier = F3_FLOW3_Utility_Algorithms::generateUUID();
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);
		$node = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentManager);
		$this->storageAccess->addNode($node);

		$this->storageAccess->removeNode($node);
		$retrievedRawNode = $this->storageAccess->getRawNodeByIdentifier($identifier);
		$this->assertFalse($retrievedRawNode, 'getRawNodeByIdentifier() did not return FALSE for a just removed node entry.');
	}

	/**
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function updateNodeWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageAccess, $this->componentManager));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageAccess));

		$rawRootNode = array(
			'parent' => 0,
			'name' => '',
			'identifier' => F3_FLOW3_Utility_Algorithms::generateUUID(),
			'nodetype' => 'nt:base'
		);
		$rootNode = new F3_TYPO3CR_Node($rawRootNode, $mockSession, $this->componentManager);

		$identifier = F3_FLOW3_Utility_Algorithms::generateUUID();
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);
		$node = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentManager);
		$this->storageAccess->addNode($node);

			// recreate node with different name and nodetype
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode2',
			'identifier' => $identifier,
			'nodetype' => 'nt:unstructured'
		);
		$node = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentManager);
		$expectedRawNodeUpdated = array(
			'parent' => $rootNode->getIdentifier(),
			'name' => 'TestNode2',
			'identifier' => $identifier,
			'nodetype' => 'nt:unstructured'
		);
		$this->storageAccess->updateNode($node);
		$rawNodeUpdated = $this->storageAccess->getRawNodeByIdentifier($identifier);
		$this->assertSame($expectedRawNodeUpdated, $rawNodeUpdated, 'The returned raw node had not the expected (updated) values.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeTypeAndDeleteNodeTypeWork() {
		$nodeTypeTemplate = new F3_TYPO3CR_NodeType_NodeTypeTemplate();
		$nodeTypeTemplate->setName('testNodeType');

		$expectedRawNodeType = array(
			'name' => 'testNodeType'
		);
		$this->storageAccess->addNodeType($nodeTypeTemplate);
		$rawNodeType = $this->storageAccess->getRawNodeType('testNodeType');
		$this->assertTrue(is_array($rawNodeType), 'getRawNodeType() did not return an array for a just created nodetype entry.');
		$this->assertSame($expectedRawNodeType, $rawNodeType, 'The returned raw node had not the expected values.');

		$this->storageAccess->deleteNodeType('testNodeType');
		$rawNodeType = $this->storageAccess->getRawNodeType('testNodeType');
		$this->assertFalse($rawNodeType, 'getRawNodeType() did return an array for a just removed nodetype entry.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addPropertyWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageAccess, $this->componentManager));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageAccess));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentManager);
		$property = new F3_TYPO3CR_Property('someProp', 'someValue', F3_PHPCR_PropertyType::STRING, $node, $mockSession, $this->componentManager);
		$this->storageAccess->addProperty($property);

		$expectedRawProperties = array(array(
			'name' => 'someProp',
			'value' => 'someValue',
			'namespace' => '',
			'multivalue' => 0,
			'type' => F3_PHPCR_PropertyType::STRING
		));
		$retrievedRawProperties = $this->storageAccess->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');

	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function updatePropertyWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageAccess, $this->componentManager));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageAccess));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentManager);
		$property = new F3_TYPO3CR_Property('someProp', 'someValue', F3_PHPCR_PropertyType::STRING, $node, $mockSession, $this->componentManager);
		$this->storageAccess->addProperty($property);
		$property->setValue('newValue');
		$this->storageAccess->updateProperty($property);

		$expectedRawProperties = array(array(
			'name' => 'someProp',
			'value' => 'newValue',
			'namespace' => '',
			'multivalue' => 0,
			'type' => F3_PHPCR_PropertyType::STRING
		));
		$retrievedRawProperties = $this->storageAccess->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');

	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removePropertyWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageAccess, $this->componentManager));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageAccess));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentManager);
		$property = new F3_TYPO3CR_Property('someProp', 'someValue', F3_PHPCR_PropertyType::STRING, $node, $mockSession, $this->componentManager);
		$this->storageAccess->addProperty($property);
		$this->storageAccess->removeProperty($property);

		$retrievedRawProperties = $this->storageAccess->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals(array(), $retrievedRawProperties, 'A removed property could be retrieved.');

	}

}
?>
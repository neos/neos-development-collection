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
	protected $storageBackend;

	/**
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$rawRootNode = array(
			'parent' => 0,
			'name' => '',
			'identifier' => F3_FLOW3_Utility_Algorithms::generateUUID(),
			'nodetype' => 'nt:base'
		);
		$rootNode = new F3_TYPO3CR_Node($rawRootNode, $mockSession, $this->componentFactory);

		$identifier = F3_FLOW3_Utility_Algorithms::generateUUID();
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);
		$node = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentFactory);
		$expectedRawNode = array(
			'parent' => $rootNode->getIdentifier(),
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);

		$this->storageBackend->addNode($node);
		$retrievedRawNode = $this->storageBackend->getRawNodeByIdentifier($identifier);
		$this->assertSame($expectedRawNode, $retrievedRawNode, 'The returned raw node had not the expected values.');
	}

	/**
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeNodeWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$rawRootNode = array(
			'parent' => 0,
			'name' => '',
			'identifier' => F3_FLOW3_Utility_Algorithms::generateUUID(),
			'nodetype' => 'nt:base'
		);
		$rootNode = new F3_TYPO3CR_Node($rawRootNode, $mockSession, $this->componentFactory);

		$identifier = F3_FLOW3_Utility_Algorithms::generateUUID();
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);
		$node = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentFactory);
		$this->storageBackend->addNode($node);

		$this->storageBackend->removeNode($node);
		$retrievedRawNode = $this->storageBackend->getRawNodeByIdentifier($identifier);
		$this->assertFalse($retrievedRawNode, 'getRawNodeByIdentifier() did not return FALSE for a just removed node entry.');
	}

	/**
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function updateNodeWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$rawRootNode = array(
			'parent' => 0,
			'name' => '',
			'identifier' => F3_FLOW3_Utility_Algorithms::generateUUID(),
			'nodetype' => 'nt:base'
		);
		$rootNode = new F3_TYPO3CR_Node($rawRootNode, $mockSession, $this->componentFactory);

		$identifier = F3_FLOW3_Utility_Algorithms::generateUUID();
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);
		$node = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentFactory);
		$this->storageBackend->addNode($node);

			// recreate node with different name and nodetype
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode2',
			'identifier' => $identifier,
			'nodetype' => 'nt:unstructured'
		);
		$node = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentFactory);
		$expectedRawNodeUpdated = array(
			'parent' => $rootNode->getIdentifier(),
			'name' => 'TestNode2',
			'identifier' => $identifier,
			'nodetype' => 'nt:unstructured'
		);
		$this->storageBackend->updateNode($node);
		$rawNodeUpdated = $this->storageBackend->getRawNodeByIdentifier($identifier);
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
		$this->storageBackend->addNodeType($nodeTypeTemplate);
		$rawNodeType = $this->storageBackend->getRawNodeType('testNodeType');
		$this->assertTrue(is_array($rawNodeType), 'getRawNodeType() did not return an array for a just created nodetype entry.');
		$this->assertSame($expectedRawNodeType, $rawNodeType, 'The returned raw node had not the expected values.');

		$this->storageBackend->deleteNodeType('testNodeType');
		$rawNodeType = $this->storageBackend->getRawNodeType('testNodeType');
		$this->assertFalse($rawNodeType, 'getRawNodeType() did return an array for a just removed nodetype entry.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addSingleValuedPropertyWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$property = new F3_TYPO3CR_Property('someProp', 'someValue', F3_PHPCR_PropertyType::STRING, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);

		$expectedRawProperties = array(array(
			'name' => 'someProp',
			'value' => 'someValue',
			'namespace' => '',
			'multivalue' => 0,
			'type' => F3_PHPCR_PropertyType::STRING
		));
		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');
	}

	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addMultiValuedPropertyWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$someValue0 = new F3_TYPO3CR_Value('someValue0', F3_PHPCR_PropertyType::STRING);
		$someValue1 = new F3_TYPO3CR_Value('someValue1', F3_PHPCR_PropertyType::STRING);
		$mockValueFactory->expects($this->exactly(2))->method('createValue')->will($this->onConsecutiveCalls($someValue0, $someValue1));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$property = new F3_TYPO3CR_Property('someProp', array('someValue0','someValue1'), F3_PHPCR_PropertyType::STRING, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);

		$expectedRawProperties = array(array(
			'name' => 'someProp',
			'value' => array('someValue0', 'someValue1'),
			'namespace' => '',
			'multivalue' => 1,
			'type' => F3_PHPCR_PropertyType::STRING
		));
		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function updateSingleValuedPropertyWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$property = new F3_TYPO3CR_Property('someProp', 'someValue', F3_PHPCR_PropertyType::STRING, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);
		$property->setValue('newValue');
		$this->storageBackend->updateProperty($property);

		$expectedRawProperties = array(array(
			'name' => 'someProp',
			'value' => 'newValue',
			'namespace' => '',
			'multivalue' => 0,
			'type' => F3_PHPCR_PropertyType::STRING
		));
		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');
	}

	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function updateMultiValuedPropertyWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$someValue0 = new F3_TYPO3CR_Value('someValue0', F3_PHPCR_PropertyType::STRING);
		$someValue1 = new F3_TYPO3CR_Value('someValue1', F3_PHPCR_PropertyType::STRING);
		$newValue0 = new F3_TYPO3CR_Value('newValue0', F3_PHPCR_PropertyType::STRING);
		$newValue1 = new F3_TYPO3CR_Value('newValue1', F3_PHPCR_PropertyType::STRING);
		$newValue2 = new F3_TYPO3CR_Value('newValue2', F3_PHPCR_PropertyType::STRING);
		$mockValueFactory->expects($this->exactly(5))->method('createValue')->will($this->onConsecutiveCalls($someValue0, $someValue1, $newValue0, $newValue1, $newValue2));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$property = new F3_TYPO3CR_Property('someProp', array('someValue0','someValue1'), F3_PHPCR_PropertyType::STRING, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);
		$property->setValue(array('newValue0','newValue1','newValue2'));
		$this->storageBackend->updateProperty($property);

		$expectedRawProperties = array(array(
			'name' => 'someProp',
			'value' => array('newValue0','newValue1','newValue2'),
			'namespace' => '',
			'multivalue' => 1,
			'type' => F3_PHPCR_PropertyType::STRING
		));
		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeSingleValuedPropertyWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$property = new F3_TYPO3CR_Property('someProp', 'someValue', F3_PHPCR_PropertyType::STRING, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);
		$this->storageBackend->removeProperty($property);

		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals(array(), $retrievedRawProperties, 'A removed property could be retrieved.');
	}

	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeMultiValuedPropertyWorks() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$someValue0 = new F3_TYPO3CR_Value('someValue0', F3_PHPCR_PropertyType::STRING);
		$someValue1 = new F3_TYPO3CR_Value('someValue1', F3_PHPCR_PropertyType::STRING);
		$mockValueFactory->expects($this->exactly(2))->method('createValue')->will($this->onConsecutiveCalls($someValue0, $someValue1));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$property = new F3_TYPO3CR_Property('someProp', array('someValue0','someValue1'), F3_PHPCR_PropertyType::STRING, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);
		$this->storageBackend->removeProperty($property);

		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals(array(), $retrievedRawProperties, 'A removed property could be retrieved.');
	}

}
?>
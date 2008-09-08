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
	 * Provides test data for single valued property test
	 *
	 * @return array of arrays with parameters for addSingleValuedStringPropertyWorks, updateSingleValuedPropertyWorks, removeSingleValuedPropertyWorks
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function singleValuedProperties() {
		return array(
			array(F3_PHPCR_PropertyType::STRING, 'someProp', 'someValue', 'newValue'),
			array(F3_PHPCR_PropertyType::STRING, 'jcr:someProp', 'someValue', 'newValue'),
			array(F3_PHPCR_PropertyType::STRING, 'xml:someProp', 'someValue', 'newValue'),
			array(F3_PHPCR_PropertyType::LONG, 'someProp', 42, 24),
			array(F3_PHPCR_PropertyType::DOUBLE, 'someProp', 42.5, 52.4),
			array(F3_PHPCR_PropertyType::BOOLEAN, 'someProp', TRUE, FALSE),
			array(F3_PHPCR_PropertyType::BOOLEAN, 'someProp', FALSE, TRUE),
			array(F3_PHPCR_PropertyType::NAME, 'someProp', 'flow3:blub', 'xml:blob'),
			array(F3_PHPCR_PropertyType::PATH, 'someProp', '/flow3:path1/path2/jcr:path3[2]/xml:path4', '/flow3:path1new/path2new/jcr:path3[5]/jcr:path4new'),
			array(F3_PHPCR_PropertyType::URI, 'someProp', 'http://typo3.org', 'http://forge.typo3.org'),
		);
	}


	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 * @dataProvider singleValuedProperties
	 */
	public function addSingleValuedPropertyWorks($propertyType, $propertyName, $propertyValue) {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$mockValueFactory->expects($this->any())->method('createValue')->will($this->returnValue(new F3_TYPO3CR_Value($propertyValue, $propertyType)));
		$property = new F3_TYPO3CR_Property($propertyName, $propertyValue, $propertyType, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);

		$expectedRawProperties = array(array(
			'name' => $propertyName,
			'value' => $propertyValue,
			'multivalue' => 0,
			'type' => $propertyType
		));
		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 * @dataProvider singleValuedProperties
	 */
	public function updateSingleValuedPropertyWorks($propertyType, $propertyName, $propertyValue, $newPropertyValue) {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$mockValueFactory->expects($this->any())->method('createValue')->will($this->returnValue(new F3_TYPO3CR_Value($newPropertyValue, $propertyType)));
		$property = new F3_TYPO3CR_Property($propertyName, $propertyValue, $propertyType, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);
		$property->setValue($newPropertyValue);
		$this->storageBackend->updateProperty($property);

		$expectedRawProperties = array(array(
			'name' => $propertyName,
			'value' => $newPropertyValue,
			'multivalue' => 0,
			'type' => $propertyType
		));
		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 * @dataProvider singleValuedProperties
	 */
	public function removeSingleValuedPropertyWorks($propertyType, $propertyName, $propertyValue) {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$mockValueFactory->expects($this->any())->method('createValue')->will($this->returnValue(new F3_TYPO3CR_Value($propertyValue, $propertyType)));
		$property = new F3_TYPO3CR_Property($propertyName, $propertyValue, $propertyType, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);
		$this->storageBackend->removeProperty($property);

		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals(array(), $retrievedRawProperties, 'A removed property could be retrieved.');
	}

	/**
	 * Provides test data for multi valued property test
	 *
	 * @return array of arrays with parameters for addMultiValuedStringPropertyWorks, updateMultiValuedPropertyWorks, removeMultiValuedPropertyWorks
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function multiValuedProperties() {
		return array(
			array(F3_PHPCR_PropertyType::STRING, 'someProp', array('someValue0','someValue1'), array('newValue0','newValue1')),
			array(F3_PHPCR_PropertyType::STRING, 'jcr:someProp', array('someValue0','someValue1'), array('newValue0','newValue1')),
			array(F3_PHPCR_PropertyType::STRING, 'xml:someProp', array('someValue0','someValue1'), array('newValue0','newValue1')),
			array(F3_PHPCR_PropertyType::LONG, 'someProp', array(42,43), array(24,23)),
			array(F3_PHPCR_PropertyType::DOUBLE, 'someProp', array(42.5, 42.6), array(52.4, 62.4)),
			array(F3_PHPCR_PropertyType::BOOLEAN, 'someProp', array(TRUE, TRUE), array(FALSE, FALSE)),
			array(F3_PHPCR_PropertyType::BOOLEAN, 'someProp', array(FALSE, TRUE), array(TRUE, FALSE)),
			array(F3_PHPCR_PropertyType::NAME, 'someProp', array('flow3:blub','xml:blib'), array('xml:blob', 'jcr:blab')),
			array(F3_PHPCR_PropertyType::PATH, 'someProp', array('/flow3:path1/path2/jcr:path3[2]/xml:path4', '/jcr:path5'), array('/flow3:path1new/path2new/jcr:path3[5]/jcr:path4new', '/jcr:path5new')),
			array(F3_PHPCR_PropertyType::URI, 'someProp', array('http://old.typo3.org', 'http://old2.typo3.org'), array('http://forge1.typo3.org', 'http://forge2.typo3.org')),
		);
	}

	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider multiValuedProperties
	 */
	public function addMultiValuedPropertyWorks($propertyType, $propertyName, $propertyValues) {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		foreach($propertyValues as $index => $propertyValue) {
			$propertyValueObjects[$index] = new F3_TYPO3CR_Value($propertyValue, $propertyType);
		}
		$mockValueFactory->expects($this->exactly(2))->method('createValue')->will(call_user_func_array(array($this, 'onConsecutiveCalls'), $propertyValueObjects));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$property = new F3_TYPO3CR_Property($propertyName, array('someValue0','someValue1'), $propertyType, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);

		$expectedRawProperties = array(array(
			'name' => $propertyName,
			'value' => $propertyValues,
			'multivalue' => 1,
			'type' => $propertyType
		));
		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');
	}

	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider multiValuedProperties
	 */
	public function updateMultiValuedPropertyWorks($propertyType, $propertyName, $propertyValues, $newPropertyValues) {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		foreach ($propertyValues as $index => $propertyValue) {
			$propertyValueObjects[$index] = new F3_TYPO3CR_Value($propertyValue, $propertyType);
		}
		foreach ($newPropertyValues as $index => $propertyValue) {
			$newPropertyValueObjects[$index] = new F3_TYPO3CR_Value($propertyValue, $propertyType);
		}
		$allPropertyValueObjects = array_merge($propertyValueObjects, $newPropertyValueObjects);
		$mockValueFactory->expects($this->exactly(count($allPropertyValueObjects)))->method('createValue')->will(call_user_func_array(array($this, 'onConsecutiveCalls'), $allPropertyValueObjects));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$property = new F3_TYPO3CR_Property($propertyName, $propertyValues, $propertyType, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);
		$property->setValue($newPropertyValues);
		$this->storageBackend->updateProperty($property);

		$expectedRawProperties = array(array(
			'name' => $propertyName,
			'value' => $newPropertyValues,
			'multivalue' => 1,
			'type' => $propertyType
		));
		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals($expectedRawProperties, $retrievedRawProperties, 'The returned raw property had not the expected values.');
	}


	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider multiValuedProperties
	 */
	public function removeMultiValuedPropertyWorks($propertyType, $propertyName, $propertyValues) {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		foreach ($propertyValues as $index => $propertyValue) {
			$propertyValueObjects[$index] = new F3_TYPO3CR_Value($propertyValue, $propertyType);
		}
		$mockValueFactory->expects($this->exactly(2))->method('createValue')->will(call_user_func_array(array($this, 'onConsecutiveCalls'), $propertyValueObjects));

		$node = new F3_TYPO3CR_Node(array(), $mockSession, $this->componentFactory);
		$property = new F3_TYPO3CR_Property($propertyName, $propertyValues, $propertyType, $node, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);
		$this->storageBackend->removeProperty($property);

		$retrievedRawProperties = $this->storageBackend->getRawPropertiesOfNode($node->getIdentifier());
		$this->assertEquals(array(), $retrievedRawProperties, 'A removed property could be retrieved.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasIdentifierWorks() {
		$this->assertTrue($this->storageBackend->hasIdentifier('96b4a35d-1ef5-4a47-8b3c-0d6d69507e01'), 'hasIdentifier() did not return TRUE for existing identifier.');
		$this->assertFalse($this->storageBackend->hasIdentifier('96b4a35d-0000-4a47-8b3c-0d6d69507e01'), 'hasIdentifier() did not return FALSE for non-existing identifier.');
	}

	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getRawPropertiesOfTypedValueReturnsNothingIfNoPropertiesOfTheTypeExist() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$rawRootNode = $this->storageBackend->getRawRootNode();
		$rootNode = new F3_TYPO3CR_Node($rawRootNode, $mockSession, $this->componentFactory);
		$refTargetUUID = F3_FLOW3_Utility_Algorithms::generateUUID();

		$rawNode = array(
			'parent' => $rootNode,
			'name' => '',
			'identifier' => $refTargetUUID,
			'nodetype' => 'nt:base'
		);
		$refTargetNode = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentFactory);
		$this->storageBackend->addNode($refTargetNode);

		$resultReferences = $this->storageBackend->getRawPropertiesOfTypedValue(NULL, F3_PHPCR_PropertyType::REFERENCE, $refTargetUUID);
		$this->assertEquals(array(), $resultReferences);
	}



	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getRawPropertiesOfTypedValueReturnsExactlyAddedProperty() {
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('default', $mockRepository, $this->storageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));

		$rawRootNode = $this->storageBackend->getRawRootNode();
		$rootNode = new F3_TYPO3CR_Node($rawRootNode, $mockSession, $this->componentFactory);
		$refTargetUUID = F3_FLOW3_Utility_Algorithms::generateUUID();

		$rawNode = array(
			'parent' => $rootNode,
			'name' => '',
			'identifier' => $refTargetUUID,
			'nodetype' => 'nt:base'
		);
		$refTargetNode = new F3_TYPO3CR_Node($rawNode, $mockSession, $this->componentFactory);
		$this->storageBackend->addNode($refTargetNode);

		$expectedReferences = array(
			array(
				'type' => F3_PHPCR_PropertyType::REFERENCE,
				'name' => 'ref',
				'multivalue' => 0,
				'value' => $refTargetUUID
			));

		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$mockValueFactory->expects($this->any())->method('createValue')->will($this->returnValue(new F3_TYPO3CR_Value($refTargetUUID, F3_PHPCR_PropertyType::REFERENCE)));
		$property = new F3_TYPO3CR_Property('ref', $refTargetUUID, F3_PHPCR_PropertyType::REFERENCE, $rootNode, $mockSession, $mockValueFactory);
		$this->storageBackend->addProperty($property);

		$resultReferences = $this->storageBackend->getRawPropertiesOfTypedValue(NULL, F3_PHPCR_PropertyType::REFERENCE, $refTargetUUID);
		$this->assertEquals($expectedReferences, $resultReferences);
	}

}
?>
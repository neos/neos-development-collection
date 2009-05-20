<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Storage\Backend;

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
 * Tests for the storage backend implementations of TYPO3CR. Needs to be extended
 * for various storage types
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TestBase extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3CR\Storage\BackendInterface
	 */
	protected $storageBackend;

	/**
	 * @var array
	 */
	protected $namespaces = array(
		\F3\PHPCR\NamespaceRegistryInterface::PREFIX_JCR => \F3\PHPCR\NamespaceRegistryInterface::NAMESPACE_JCR,
		\F3\PHPCR\NamespaceRegistryInterface::PREFIX_NT => \F3\PHPCR\NamespaceRegistryInterface::NAMESPACE_NT,
		\F3\PHPCR\NamespaceRegistryInterface::PREFIX_MIX => \F3\PHPCR\NamespaceRegistryInterface::NAMESPACE_MIX,
		\F3\PHPCR\NamespaceRegistryInterface::PREFIX_XML => \F3\PHPCR\NamespaceRegistryInterface::NAMESPACE_XML,
		\F3\PHPCR\NamespaceRegistryInterface::PREFIX_EMPTY => \F3\PHPCR\NamespaceRegistryInterface::NAMESPACE_EMPTY,
		'flow3' => 'http://forge.typo3.org/namespaces/flow3'
	);

	public function setUp() {
		$this->mockRepository = $this->getMock('F3\PHPCR\RepositoryInterface');

		$this->mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$this->mockNamespaceRegistry = $this->getMock('F3\PHPCR\NamespaceRegistryInterface');
		$this->mockNamespaceRegistry->expects($this->any())->method('getURI')->will($this->returnCallback(array($this, 'namespaceRegistryGetURICallback')));
		$this->mockNamespaceRegistry->expects($this->any())->method('getPrefix')->will($this->returnCallback(array($this, 'namespaceRegistryGetPrefixCallback')));
		$this->storageBackend->setNamespaceRegistry($this->mockNamespaceRegistry);

		$this->mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$this->mockWorkspace->expects($this->any())->method('getNodeTypeManager')->will($this->returnValue($this->mockNodeTypeManager));

		$this->mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$this->mockSession->expects($this->any())->method('getWorkspace')->will($this->returnValue($this->mockWorkspace));
		$this->mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($this->storageBackend));
	}

	public function namespaceRegistryGetURICallback($prefix) {
		return $this->namespaces[$prefix];
	}

	public function namespaceRegistryGetPrefixCallback($uri) {
		return array_search($uri, $this->namespaces);
	}

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWorks() {
		$this->mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnValue($this->objectFactory->create('F3\PHPCR\NodeType\NodeTypeInterface', 'nt:base')));

		$rawRootNode = array(
			'parent' => 0,
			'name' => '',
			'identifier' => \F3\FLOW3\Utility\Algorithms::generateUUID(),
			'nodetype' => 'nt:base'
		);
		$rootNode = new \F3\TYPO3CR\Node($rawRootNode, $this->mockSession, $this->objectFactory);

		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);
		$node = new \F3\TYPO3CR\Node($rawNode, $this->mockSession, $this->objectFactory);
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function removeNodeWorks() {
		$this->mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->returnValue($this->objectFactory->create('F3\PHPCR\NodeType\NodeTypeInterface', 'nt:base')));

		$rawRootNode = array(
			'parent' => 0,
			'name' => '',
			'identifier' => \F3\FLOW3\Utility\Algorithms::generateUUID(),
			'nodetype' => 'nt:base'
		);
		$rootNode = new \F3\TYPO3CR\Node($rawRootNode, $this->mockSession, $this->objectFactory);

		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);
		$node = new \F3\TYPO3CR\Node($rawNode, $this->mockSession, $this->objectFactory);
		$this->storageBackend->addNode($node);

		$this->storageBackend->removeNode($node);
		$retrievedRawNode = $this->storageBackend->getRawNodeByIdentifier($identifier);
		$this->assertFalse($retrievedRawNode, 'getRawNodeByIdentifier() did not return FALSE for a just removed node entry.');
	}

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function updateNodeWorks() {
		$this->mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->onConsecutiveCalls(
			$this->objectFactory->create('F3\PHPCR\NodeType\NodeTypeInterface', 'nt:base'),
			$this->objectFactory->create('F3\PHPCR\NodeType\NodeTypeInterface', 'nt:unstructured')
		));

		$rawRootNode = array(
			'parent' => 0,
			'name' => '',
			'identifier' => \F3\FLOW3\Utility\Algorithms::generateUUID(),
			'nodetype' => 'nt:base'
		);
		$rootNode = new \F3\TYPO3CR\Node($rawRootNode, $this->mockSession, $this->objectFactory);

		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode1',
			'identifier' => $identifier,
			'nodetype' => 'nt:base'
		);
		$node = new \F3\TYPO3CR\Node($rawNode, $this->mockSession, $this->objectFactory);
		$this->storageBackend->addNode($node);

			// recreate node with different name and nodetype
		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'TestNode2',
			'identifier' => $identifier,
			'nodetype' => 'nt:unstructured'
		);
		$node = new \F3\TYPO3CR\Node($rawNode, $this->mockSession, $this->objectFactory);
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
		$nodeTypeTemplate = new \F3\TYPO3CR\NodeType\NodeTypeTemplate();
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
			array(\F3\PHPCR\PropertyType::STRING, 'someProp', 'someValue', 'newValue'),
			array(\F3\PHPCR\PropertyType::STRING, 'jcr:someProp', 'someValue', 'newValue'),
			array(\F3\PHPCR\PropertyType::STRING, 'xml:someProp', 'someValue', 'newValue'),
			array(\F3\PHPCR\PropertyType::LONG, 'someLongProp', 42, 24),
			array(\F3\PHPCR\PropertyType::DOUBLE, 'someDoubleProp', 42.5, 52.4),
			array(\F3\PHPCR\PropertyType::BOOLEAN, 'someBooleanTrueProp', TRUE, FALSE),
			array(\F3\PHPCR\PropertyType::BOOLEAN, 'someBooleanFalseProp', FALSE, TRUE),
			array(\F3\PHPCR\PropertyType::NAME, 'someNameProp', 'flow3:blub', 'xml:blob'),
			array(\F3\PHPCR\PropertyType::PATH, 'somePathProp', '/flow3:path1/path2/jcr:path3[2]/xml:path4', '/flow3:path1new/path2new/jcr:path3[5]/jcr:path4new'),
			array(\F3\PHPCR\PropertyType::URI, 'someURIProp', 'http://typo3.org', 'http://forge.typo3.org'),
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 * @dataProvider singleValuedProperties
	 */
	public function addSingleValuedPropertyWorks($propertyType, $propertyName, $propertyValue) {
		$node = new \F3\TYPO3CR\Node(array('identifier' => \F3\FLOW3\Utility\Algorithms::generateUUID(), 'nodetype' => 'nt:base'), $this->mockSession, $this->objectFactory);
		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$mockValueFactory->expects($this->any())->method('createValue')->will($this->returnValue(new \F3\TYPO3CR\Value($propertyValue, $propertyType)));
		$this->mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		$property = new \F3\TYPO3CR\Property($propertyName, $propertyValue, $propertyType, $node, $this->mockSession);
		$this->storageBackend->addProperty($property);

		$expectedRawProperties = array(array(
			'name' => $propertyName,
			'parent' => $node->getIdentifier(),
			'value' => $propertyValue,
			'multivalue' => FALSE,
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
		$node = new \F3\TYPO3CR\Node(array('identifier' => \F3\FLOW3\Utility\Algorithms::generateUUID(), 'nodetype' => 'nt:base'), $this->mockSession, $this->objectFactory);
		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$mockValueFactory->expects($this->any())->method('createValue')->will($this->returnValue(new \F3\TYPO3CR\Value($newPropertyValue, $propertyType)));
		$this->mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		$property = new \F3\TYPO3CR\Property($propertyName, $propertyValue, $propertyType, $node, $this->mockSession);
		$this->storageBackend->addProperty($property);
		$property->setValue($newPropertyValue);
		$this->storageBackend->updateProperty($property);

		$expectedRawProperties = array(array(
			'name' => $propertyName,
			'parent' => $node->getIdentifier(),
			'value' => $newPropertyValue,
			'multivalue' => FALSE,
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
		$node = new \F3\TYPO3CR\Node(array('identifier' => \F3\FLOW3\Utility\Algorithms::generateUUID(), 'nodetype' => 'nt:base'), $this->mockSession, $this->objectFactory);
		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$mockValueFactory->expects($this->any())->method('createValue')->will($this->returnValue(new \F3\TYPO3CR\Value($propertyValue, $propertyType)));
		$this->mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		$property = new \F3\TYPO3CR\Property($propertyName, $propertyValue, $propertyType, $node, $this->mockSession);
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
			array(\F3\PHPCR\PropertyType::STRING, 'someProp', array('someValue0','someValue1'), array('newValue0','newValue1')),
			array(\F3\PHPCR\PropertyType::STRING, 'jcr:someProp', array('someValue0','someValue1'), array('newValue0','newValue1')),
			array(\F3\PHPCR\PropertyType::STRING, 'xml:someProp', array('someValue0','someValue1'), array('newValue0','newValue1')),
			array(\F3\PHPCR\PropertyType::LONG, 'someLongProp', array(42,43), array(24,23)),
			array(\F3\PHPCR\PropertyType::DOUBLE, 'someDoubleProp', array(42.5, 42.6), array(52.4, 62.4)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, 'someBooleanProp1', array(TRUE, TRUE), array(FALSE, FALSE)),
			array(\F3\PHPCR\PropertyType::BOOLEAN, 'someBooleanProp2', array(FALSE, TRUE), array(TRUE, FALSE)),
			array(\F3\PHPCR\PropertyType::NAME, 'someNameProp', array('flow3:blub','xml:blib'), array('xml:blob', 'jcr:blab')),
			array(\F3\PHPCR\PropertyType::PATH, 'somePathProp', array('/flow3:path1/path2/jcr:path3[2]/xml:path4', '/jcr:path5'), array('/flow3:path1new/path2new/jcr:path3[5]/jcr:path4new', '/jcr:path5new')),
			array(\F3\PHPCR\PropertyType::URI, 'someURIProp', array('http://old.typo3.org', 'http://old2.typo3.org'), array('http://forge1.typo3.org', 'http://forge2.typo3.org')),
		);
	}

	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider multiValuedProperties
	 */
	public function addMultiValuedPropertyWorks($propertyType, $propertyName, $propertyValues) {
		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$this->mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		foreach($propertyValues as $index => $propertyValue) {
			$propertyValueObjects[$index] = new \F3\TYPO3CR\Value($propertyValue, $propertyType);
		}
		$mockValueFactory->expects($this->exactly(2))->method('createValue')->will(call_user_func_array(array($this, 'onConsecutiveCalls'), $propertyValueObjects));

		$node = new \F3\TYPO3CR\Node(array('identifier' => \F3\FLOW3\Utility\Algorithms::generateUUID(), 'nodetype' => 'nt:base'), $this->mockSession, $this->objectFactory);
		$property = new \F3\TYPO3CR\Property($propertyName, array('someValue0','someValue1'), $propertyType, $node, $this->mockSession);
		$this->storageBackend->addProperty($property);

		$expectedRawProperties = array(array(
			'name' => $propertyName,
			'parent' => $node->getIdentifier(),
			'value' => $propertyValues,
			'multivalue' => TRUE,
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
		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$this->mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		foreach ($propertyValues as $index => $propertyValue) {
			$propertyValueObjects[$index] = new \F3\TYPO3CR\Value($propertyValue, $propertyType);
		}
		foreach ($newPropertyValues as $index => $propertyValue) {
			$newPropertyValueObjects[$index] = new \F3\TYPO3CR\Value($propertyValue, $propertyType);
		}
		$allPropertyValueObjects = array_merge($propertyValueObjects, $newPropertyValueObjects);
		$mockValueFactory->expects($this->exactly(count($allPropertyValueObjects)))->method('createValue')->will(call_user_func_array(array($this, 'onConsecutiveCalls'), $allPropertyValueObjects));

		$node = new \F3\TYPO3CR\Node(array('identifier' => \F3\FLOW3\Utility\Algorithms::generateUUID(), 'nodetype' => 'nt:base'), $this->mockSession, $this->objectFactory);
		$property = new \F3\TYPO3CR\Property($propertyName, $propertyValues, $propertyType, $node, $this->mockSession);
		$this->storageBackend->addProperty($property);
		$property->setValue($newPropertyValues);
		$this->storageBackend->updateProperty($property);

		$expectedRawProperties = array(array(
			'name' => $propertyName,
			'parent' => $node->getIdentifier(),
			'value' => $newPropertyValues,
			'multivalue' => TRUE,
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
		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$this->mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		foreach ($propertyValues as $index => $propertyValue) {
			$propertyValueObjects[$index] = new \F3\TYPO3CR\Value($propertyValue, $propertyType);
		}
		$mockValueFactory->expects($this->exactly(2))->method('createValue')->will(call_user_func_array(array($this, 'onConsecutiveCalls'), $propertyValueObjects));

		$node = new \F3\TYPO3CR\Node(array('identifier' => \F3\FLOW3\Utility\Algorithms::generateUUID(), 'nodetype' => 'nt:base'), $this->mockSession, $this->objectFactory);
		$property = new \F3\TYPO3CR\Property($propertyName, $propertyValues, $propertyType, $node, $this->mockSession);
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
		$this->mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->onConsecutiveCalls($this->objectFactory->create('F3\PHPCR\NodeType\NodeTypeInterface', 'nt:base')));

		$rawRootNode = $this->storageBackend->getRawRootNode();
		$rootNode = new \F3\TYPO3CR\Node($rawRootNode, $this->mockSession, $this->objectFactory);
		$refTargetUUID = \F3\FLOW3\Utility\Algorithms::generateUUID();

		$rawNode = array(
			'parent' => $rootNode,
			'name' => '',
			'identifier' => $refTargetUUID,
			'nodetype' => 'nt:base'
		);
		$refTargetNode = new \F3\TYPO3CR\Node($rawNode, $this->mockSession, $this->objectFactory);
		$this->storageBackend->addNode($refTargetNode);

		$resultReferences = $this->storageBackend->getRawPropertiesOfTypedValue(NULL, \F3\PHPCR\PropertyType::REFERENCE, $refTargetUUID);
		$this->assertEquals(array(), $resultReferences);
	}



	/**
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getRawPropertiesOfTypedValueReturnsExactlyAddedProperty() {
		$this->mockNodeTypeManager->expects($this->any())->method('getNodeType')->will($this->onConsecutiveCalls($this->objectFactory->create('F3\PHPCR\NodeType\NodeTypeInterface', 'nt:base')));

		$rawRootNode = $this->storageBackend->getRawRootNode();
		$rootNode = new \F3\TYPO3CR\Node($rawRootNode, $this->mockSession, $this->objectFactory);
		$refTargetUUID = \F3\FLOW3\Utility\Algorithms::generateUUID();

		$rawNode = array(
			'parent' => $rootNode,
			'name' => 'refTargetNode',
			'identifier' => $refTargetUUID,
			'nodetype' => 'nt:base'
		);
		$refTargetNode = new \F3\TYPO3CR\Node($rawNode, $this->mockSession, $this->objectFactory);
		$this->storageBackend->addNode($refTargetNode);

		$expectedReferences = array(
			array(
				'type' => \F3\PHPCR\PropertyType::REFERENCE,
				'name' => 'ref',
				'parent' => $rootNode->getIdentifier(),
				'multivalue' => FALSE,
				'value' => $refTargetUUID
			));

		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$mockValueFactory->expects($this->any())->method('createValue')->will($this->returnValue(new \F3\TYPO3CR\Value($refTargetUUID, \F3\PHPCR\PropertyType::REFERENCE)));
		$this->mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		$property = new \F3\TYPO3CR\Property('ref', $refTargetUUID, \F3\PHPCR\PropertyType::REFERENCE, $rootNode, $this->mockSession);
		$this->storageBackend->addProperty($property);

		$resultReferences = $this->storageBackend->getRawPropertiesOfTypedValue(NULL, \F3\PHPCR\PropertyType::REFERENCE, $refTargetUUID);
		$this->assertEquals($expectedReferences, $resultReferences);
	}

}
?>
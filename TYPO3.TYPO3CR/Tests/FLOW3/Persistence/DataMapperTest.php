<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\FLOW3\Persistence;

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
 * Testcase for \F3\TYPO3CR\FLOW3\Persistence\DataMapper
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DataMapperTest extends \F3\Testing\BaseTestCase {

	/**
	 * The main assertion is the expectation on the mocked identity map.
	 * If this ever breaks, one will have to look at the inner workings of map()
	 * and it's called methods, though. Sorry.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapMapsNodesToObjectsAndRegistersObjectsWithPersistenceSession() {
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$nodeIterator = new \F3\TYPO3CR\NodeIterator(array($node));
		$object = new \stdClass();

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->once())->method('registerReconstitutedObject')->with($object);
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\ManagerInterface');
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($mockPersistenceSession));

		$dataMapper = $this->getMock('F3\TYPO3CR\FLOW3\Persistence\DataMapper', array('mapSingleNode'));
		$dataMapper->expects($this->once())->method('mapSingleNode')->with($node)->will($this->returnValue($object));
		$dataMapper->injectPersistenceManager($mockPersistenceManager);

		$dataMapper->map($nodeIterator);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapSingleNodeReturnsObjectFromIdentityMapIfAvailable() {
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->exactly(2))->method('getIdentifier')->will($this->returnValue('1234'));
		$object = new \stdClass();

		$mockIdentityMap = $this->getMock('F3\TYPO3CR\FLOW3\Persistence\IdentityMap');
		$mockIdentityMap->expects($this->once())->method('hasUUID')->with('1234')->will($this->returnValue(TRUE));
		$mockIdentityMap->expects($this->once())->method('getObjectByUUID')->with('1234')->will($this->returnValue($object));

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('dummy'));
		$dataMapper->injectIdentityMap($mockIdentityMap);
		$dataMapper->_call('mapSingleNode', $node);
	}

	/**
	 * Test that an object is reconstituted, registered with the identity map
	 * and memorizes it's clean state.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapSingleNodeReconstitutesExpectedObjectForNodeAndRegistersItWithIdentityMap() {
		$mockEntityClassName = uniqid('Entity');
		$mockEntity = $this->getMock('F3\FLOW3\AOP\ProxyInterface', array('FLOW3_Persistence_memorizeCleanState', 'FLOW3_AOP_Proxy_invokeJoinPoint', 'FLOW3_AOP_Proxy_getProperty', 'FLOW3_AOP_Proxy_setProperty', 'FLOW3_AOP_Proxy_getProxyTargetClassName'));
		$mockEntity->expects($this->once())->method('FLOW3_Persistence_memorizeCleanState');
		$mockPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:' . $mockEntityClassName));
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPrimaryNodeType));
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue('1234'));
		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array(), '', FALSE);
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\ManagerInterface', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->with($mockEntityClassName)->will($this->returnValue($mockClassSchema));
		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->with($mockEntityClassName)->will($this->returnValue($mockObjectConfiguration));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->once())->method('createEmptyObject')->with($mockEntityClassName, $mockObjectConfiguration)->will($this->returnValue($mockEntity));
		$mockObjectBuilder->expects($this->once())->method('reinjectDependencies')->with($mockEntity, $mockObjectConfiguration);
		$mockIdentityMap = $this->getMock('F3\TYPO3CR\FLOW3\Persistence\IdentityMap');
		$mockIdentityMap->expects($this->once())->method('registerObject')->with($mockEntity, '1234');

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('thawProperties'));
		$dataMapper->expects($this->once())->method('thawProperties')->with($mockEntity, $node, $mockClassSchema);
		$dataMapper->injectPersistenceManager($mockPersistenceManager);
		$dataMapper->injectObjectManager($mockObjectManager);
		$dataMapper->injectObjectBuilder($mockObjectBuilder);
		$dataMapper->injectIdentityMap($mockIdentityMap);
		$dataMapper->_call('mapSingleNode', $node);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function thawPropertiesDetectsAndMapsObjectProxyNodes() {
			// set up classes
		$authorClassName = uniqid('Author');
		$qualifiedAuthorClassName = 'F3\\' . $authorClassName;
		$postClassName = uniqid('Post');
		$qualifiedPostClassName = 'F3\\' . $postClassName;
		eval('namespace F3; abstract class ' . $postClassName . ' implements \F3\FLOW3\AOP\ProxyInterface { public function FLOW3_AOP_Proxy_getProxyTargetClassName() { return get_class($this); } public function FLOW3_Persistence_isNew() { return TRUE; } public function FLOW3_Persistence_memorizeCleanState() {} }');

			// set up (mock) objects
		$mockPost = $this->getMock($qualifiedPostClassName);

		$postClassSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\Post');
		$postClassSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
		$postClassSchema->setAggregateRoot(TRUE);
		$postClassSchema->addProperty('author', $qualifiedAuthorClassName);

		$mockProxyPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockProxyPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_OBJECTPROXY));
		$authorProxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$authorProxyNode->expects($this->at(0))->method('getPrimaryNodeType')->will($this->returnValue($mockProxyPrimaryNodeType));

		$mockPostPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPostPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:' . $qualifiedPostClassName));
		$postNode = $this->getMock('F3\PHPCR\NodeInterface');
		$postNode->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPostPrimaryNodeType));
		$postNode->expects($this->any())->method('hasNode')->will($this->returnValue(TRUE));
		$postNode->expects($this->once())->method('getNode')->with('flow3:author')->will($this->returnValue($authorProxyNode));

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('mapObjectProxyNode'));
		$dataMapper->expects($this->once())->method('mapObjectProxyNode')->with($authorProxyNode);
		$dataMapper->_call('thawProperties', $mockPost, $postNode, $postClassSchema);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapObjectProxyNodeMapsTargetNode() {
		$targetNode = $this->getMock('F3\PHPCR\NodeInterface');
		$targetProperty = $this->getMock('F3\PHPCR\PropertyInterface');
		$targetProperty->expects($this->once())->method('getNode')->will($this->returnValue($targetNode));
		$proxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$proxyNode->expects($this->once())->method('getProperty')->with('flow3:target')->will($this->returnValue($targetProperty));

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('mapSingleNode'));
		$dataMapper->_call('mapObjectProxyNode', $proxyNode);
	}

	/**
	 * @test
	 */
	public function mapObjectProxyNodeCreatesLazyLoadingProxyWhenLazyLoading() {
		$mockProxyPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockProxyPrimaryNodeType->expects($this->once())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_SPLOBJECTSTORAGEPROXY));
		$parent = new \stdClass();
		$proxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$proxyNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($mockProxyPrimaryNodeType));

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('dummy'));
		$result = $dataMapper->_call('mapSplObjectStorageProxyNode', $parent, 'objectsProperty', $proxyNode, TRUE);
		$this->assertType('F3\FLOW3\Persistence\LazyLoadingProxy', $result);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function thawPropertiesSetsPropertyValues() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->at(0))->method('FLOW3_AOP_Proxy_setProperty')->with('firstProperty', 'firstValue');
		$object->expects($this->at(1))->method('FLOW3_AOP_Proxy_setProperty')->with('secondProperty', 1234);

		$firstValue = $this->getMock('F3\PHPCR\ValueInterface');
		$firstValue->expects($this->any())->method('getString')->will($this->returnValue('firstValue'));
		$secondValue = $this->getMock('F3\PHPCR\ValueInterface');
		$secondValue->expects($this->any())->method('getLong')->will($this->returnValue(1234));
		$firstProperty = $this->getMock('F3\PHPCR\PropertyInterface');
		$firstProperty->expects($this->any())->method('getType')->will($this->returnValue(\F3\PHPCR\PropertyType::STRING));
		$firstProperty->expects($this->any())->method('getValue')->will($this->returnValue($firstValue));
		$secondProperty = $this->getMock('F3\PHPCR\PropertyInterface');
		$secondProperty->expects($this->any())->method('getType')->will($this->returnValue(\F3\PHPCR\PropertyType::LONG));
		$secondProperty->expects($this->any())->method('getValue')->will($this->returnValue($secondValue));
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('hasProperty')->will($this->returnValue(TRUE));
		$node->expects($this->at(1))->method('getProperty')->with('flow3:firstProperty')->will($this->returnValue($firstProperty));
		$node->expects($this->at(3))->method('getProperty')->with('flow3:secondProperty')->will($this->returnValue($secondProperty));

		$classSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\Post');
		$classSchema->addProperty('firstProperty', 'string');
		$classSchema->addProperty('secondProperty', 'integer');

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('dummy'));
		$dataMapper->_call('thawProperties', $object, $node, $classSchema);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function thawPropertiesDelegatesHandlingOfArraysAndSplObjectStorage() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');

		$proxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('hasNode')->will($this->returnValue(TRUE));
		$node->expects($this->at(1))->method('getNode')->with('flow3:firstProperty')->will($this->returnValue($proxyNode));
		$node->expects($this->at(3))->method('getNode')->with('flow3:secondProperty')->will($this->returnValue($proxyNode));
		$node->expects($this->at(5))->method('getNode')->with('flow3:thirdProperty')->will($this->returnValue($proxyNode));

		$classSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\Post');
		$classSchema->addProperty('firstProperty', 'array');
		$classSchema->addProperty('secondProperty', 'SplObjectStorage');
		$classSchema->addProperty('thirdProperty', 'SplObjectStorage', TRUE);

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('mapArrayProxyNode', 'mapSplObjectStorageProxyNode'));
		$dataMapper->expects($this->at(0))->method('mapArrayProxyNode')->with($proxyNode);
		$dataMapper->expects($this->at(1))->method('mapSplObjectStorageProxyNode')->with($object, 'secondProperty', $proxyNode, FALSE);
		$dataMapper->expects($this->at(2))->method('mapSplObjectStorageProxyNode')->with($object, 'thirdProperty', $proxyNode, TRUE);
		$dataMapper->_call('thawProperties', $object, $node, $classSchema);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function thawPropertiesDelegatesHandlingOfObjects() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');

		$primaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$primaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('F3_SomeObject'));
		$objectNode = $this->getMock('F3\PHPCR\NodeInterface');
		$objectNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($primaryNodeType));
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('hasNode')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('getNode')->with('flow3:firstProperty')->will($this->returnValue($objectNode));

		$classSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\Post');
		$classSchema->addProperty('firstProperty', 'F3\SomeObject');

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('mapSingleNode'));
		$dataMapper->expects($this->once())->method('mapSingleNode')->with($objectNode);
		$dataMapper->_call('thawProperties', $object, $node, $classSchema);
	}

	/**
	 * @test
	 */
	public function mapArrayProxyNodeDelegatesHandlingOfComplexTypesAndTakesCareOfSimpleTypesDirectly() {
		$mockProxyPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockProxyPrimaryNodeType->expects($this->once())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_ARRAYPROXY));
		$proxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$proxyNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($mockProxyPrimaryNodeType));

			// array proxy
		$arrayProxyNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$arrayProxyNodeType->expects($this->atLeastOnce())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_ARRAYPROXY));
		$arrayProxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$arrayProxyNode->expects($this->once())->method('getName')->will($this->returnValue('flow:someArray'));
		$arrayProxyNode->expects($this->exactly(2))->method('getPrimaryNodeType')->will($this->returnValue($arrayProxyNodeType));
		$arrayProxyNode->expects($this->once())->method('getNodes')->will($this->returnValue(array()));
		$arrayProxyNode->expects($this->once())->method('getProperties')->will($this->returnValue(array()));

			// object proxy
		$objectProxyNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$objectProxyNodeType->expects($this->once())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_OBJECTPROXY));
		$objectProxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$objectProxyNode->expects($this->once())->method('getName')->will($this->returnValue('flow3:someObjectProxy'));
		$objectProxyNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($objectProxyNodeType));

			// object
		$objectNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$objectNodeType->expects($this->once())->method('getName')->will($this->returnValue('flow3:F3_SomeObject'));
		$objectNode = $this->getMock('F3\PHPCR\NodeInterface');
		$objectNode->expects($this->once())->method('getName')->will($this->returnValue('flow3:someObject'));
		$objectNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($objectNodeType));

		$proxyNode->expects($this->once())->method('getNodes')->will($this->returnValue(array($arrayProxyNode, $objectProxyNode, $objectNode)));

			// simple property
		$property = $this->getMock('F3\PHPCR\PropertyInterface');
		$property->expects($this->once())->method('getName')->will($this->returnValue('flow3:property'));
		$proxyNode->expects($this->once())->method('getProperties')->will($this->returnValue(array($property)));

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('mapObjectProxyNode', 'mapSingleNode', 'getNativeValue'));
		$dataMapper->expects($this->once())->method('mapObjectProxyNode')->with($objectProxyNode);
		$dataMapper->expects($this->once())->method('mapSingleNode')->with($objectNode);
		$dataMapper->expects($this->once())->method('getNativeValue')->with($property);
		$dataMapper->_call('mapArrayProxyNode', $proxyNode);
	}

	/**
	 * @test
	 */
	public function mapSplObjectStorageProxyNodeCreatesLazyLoadingProxyWhenLazyLoading() {
		$mockProxyPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockProxyPrimaryNodeType->expects($this->once())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_SPLOBJECTSTORAGEPROXY));
		$parent = new \stdClass();
		$proxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$proxyNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($mockProxyPrimaryNodeType));

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('dummy'));
		$result = $dataMapper->_call('mapSplObjectStorageProxyNode', $parent, 'objectsProperty', $proxyNode, TRUE);
		$this->assertType('F3\FLOW3\Persistence\LazyLoadingProxy', $result);
	}

	/**
	 * @test
	 */
	public function mapSplObjectStorageProxyNodeCreatesSplObjectStorageWhenEagerLoading() {
		$mockProxyPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockProxyPrimaryNodeType->expects($this->once())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_SPLOBJECTSTORAGEPROXY));
		$proxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$proxyNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($mockProxyPrimaryNodeType));

			// object proxy
		$objectProxyNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$objectProxyNodeType->expects($this->once())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_OBJECTPROXY));
		$objectProxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$objectProxyNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($objectProxyNodeType));

			// object
		$objectNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$objectNodeType->expects($this->once())->method('getName')->will($this->returnValue('flow3:F3_SomeObject'));
		$objectNode = $this->getMock('F3\PHPCR\NodeInterface');
		$objectNode->expects($this->once())->method('getPrimaryNodeType')->will($this->returnValue($objectNodeType));

		$itemNode1 = $this->getMock('F3\PHPCR\NodeInterface');
		$itemNode1->expects($this->once())->method('getNode')->with('flow3:object')->will($this->returnValue($objectProxyNode));
		$itemNode2 = $this->getMock('F3\PHPCR\NodeInterface');
		$itemNode2->expects($this->once())->method('getNode')->with('flow3:object')->will($this->returnValue($objectNode));

		$proxyNode->expects($this->once())->method('getNodes')->will($this->returnValue(array($itemNode1, $itemNode2)));

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('mapObjectProxyNode', 'mapSingleNode'));
		$dataMapper->expects($this->once())->method('mapObjectProxyNode')->with($objectProxyNode)->will($this->returnValue(new \stdClass()));
		$dataMapper->expects($this->once())->method('mapSingleNode')->with($objectNode)->will($this->returnValue(new \stdClass()));
		$dataMapper->_call('mapSplObjectStorageProxyNode', new \stdClass(), 'objectsProperty', $proxyNode);
	}

}

?>
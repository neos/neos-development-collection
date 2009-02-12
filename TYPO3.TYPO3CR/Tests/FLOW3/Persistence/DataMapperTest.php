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
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapReturnsCorrectObjectsFromNodes() {
		$mockEntityClassName = uniqid('Entity');
		$qualifiedMockEntityClassName = 'Tests\Virtual\\' . $mockEntityClassName;
		$mockEntity = $this->getMock('F3\FLOW3\AOP\ProxyInterface', array('memorizeCleanState', 'AOPProxyInvokeJoinPoint', 'AOPProxyGetProperty', 'AOPProxySetProperty', 'AOPProxyGetProxyTargetClassName'), array(), $qualifiedMockEntityClassName, FALSE);
		$mockEntity->expects($this->once())->method('memorizeCleanState');

		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->once())->method('createEmptyObject')->with($qualifiedMockEntityClassName, $mockObjectConfiguration)->will($this->returnValue($mockEntity));
		$mockObjectBuilder->expects($this->once())->method('reinjectDependencies');
		$identityMap = new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Manager', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($persistenceSession));

		$mockPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:' . $qualifiedMockEntityClassName));
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPrimaryNodeType));
		$nodeIterator = new \F3\TYPO3CR\NodeIterator(array($node));

		$dataMapper = new \F3\TYPO3CR\FLOW3\Persistence\DataMapper();
		$dataMapper->injectObjectManager($mockObjectManager);
		$dataMapper->injectObjectBuilder($mockObjectBuilder);
		$dataMapper->injectIdentityMap($identityMap);
		$dataMapper->injectPersistenceManager($mockPersistenceManager);

		$objects = $dataMapper->map($nodeIterator);
	}

	/**
	 * The main assertion is the expectation on the mocked identity map.
	 * If this ever breaks, one will have to look at the inner workings of map()
	 * and it's called methods, though. Sorry.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapRegistersObjectsInIdentityMap() {
		$mockEntityClassName = uniqid('Entity');
		$qualifiedMockEntityClassName = 'Tests\Virtual\\' . $mockEntityClassName;
		$mockEntity = $this->getMock('F3\FLOW3\AOP\ProxyInterface', array('memorizeCleanState', 'AOPProxyInvokeJoinPoint', 'AOPProxyGetProperty', 'AOPProxySetProperty', 'AOPProxyGetProxyTargetClassName'), array(), $qualifiedMockEntityClassName, FALSE);

		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->any())->method('createEmptyObject')->will($this->returnValue($mockEntity));
		$mockObjectBuilder->expects($this->once())->method('reinjectDependencies');
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Manager', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($persistenceSession));
		$mockPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:Tests_Virtual_' . $mockEntityClassName));
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPrimaryNodeType));
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue(1221819436));
		$nodeIterator = new \F3\TYPO3CR\NodeIterator(array($node));
		$identityMap = $this->getMock('F3\TYPO3CR\FLOW3\Persistence\IdentityMap');

		$dataMapper = new \F3\TYPO3CR\FLOW3\Persistence\DataMapper();
		$dataMapper->injectObjectManager($mockObjectManager);
		$dataMapper->injectObjectBuilder($mockObjectBuilder);
		$dataMapper->injectIdentityMap($identityMap);
		$dataMapper->injectPersistenceManager($mockPersistenceManager);

		$identityMap->expects($this->once())->method('registerObject')->with($mockEntity, 1221819436);

		$dataMapper->map($nodeIterator);
	}

	/**
	 * The main assertion is the expectation on the mocked persistence session.
	 * If this ever breaks, one will have to look at the inner workings of map()
	 * and it's called methods, though. Sorry.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapRegistersObjectsAsReconstitutedWithPersistentSession() {
		$mockEntityClassName = uniqid('Entity');
		$qualifiedMockEntityClassName = 'Tests\Virtual\\' . $mockEntityClassName;
		$mockEntity = $this->getMock('F3\FLOW3\AOP\ProxyInterface', array('memorizeCleanState', 'AOPProxyInvokeJoinPoint', 'AOPProxyGetProperty', 'AOPProxySetProperty', 'AOPProxyGetProxyTargetClassName'), array(), $qualifiedMockEntityClassName, FALSE);

		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->once())->method('createEmptyObject')->will($this->returnValue($mockEntity));
		$mockObjectBuilder->expects($this->once())->method('reinjectDependencies');
		$identityMap = new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap();
		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Manager', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($mockPersistenceSession));
		$mockPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:Tests_Virtual_' . $mockEntityClassName));
		$node = $this->getMock('F3\PHPCR\NodeInterface');
		$node->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPrimaryNodeType));
		$node->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$node->expects($this->any())->method('getIdentifier')->will($this->returnValue(1221819436));
		$nodeIterator = new \F3\TYPO3CR\NodeIterator(array($node));

		$dataMapper = new \F3\TYPO3CR\FLOW3\Persistence\DataMapper();
		$dataMapper->injectObjectManager($mockObjectManager);
		$dataMapper->injectObjectBuilder($mockObjectBuilder);
		$dataMapper->injectIdentityMap($identityMap);
		$dataMapper->injectPersistenceManager($mockPersistenceManager);

		$mockPersistenceSession->expects($this->once())->method('registerReconstitutedObject')->with($mockEntity);

		$dataMapper->map($nodeIterator);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapDetectsAndResolvesObjectProxyNodes() {
			// set up classes
		$authorClassName = uniqid('Author');
		$qualifiedAuthorClassName = 'F3\\' . $authorClassName;
		eval('namespace F3; abstract class ' . $authorClassName . ' implements \F3\FLOW3\AOP\ProxyInterface { public function AOPProxyGetProxyTargetClassName() { return get_class($this); } public function isNew() { return TRUE; } public function memorizeCleanState() {} }');
		$postClassName = uniqid('Post');
		$qualifiedPostClassName = 'F3\\' . $postClassName;
		eval('namespace F3; abstract class ' . $postClassName . ' implements \F3\FLOW3\AOP\ProxyInterface { public function AOPProxyGetProxyTargetClassName() { return get_class($this); } public function isNew() { return TRUE; } public function memorizeCleanState() {} }');

			// set up (mock) objects
		$mockAuthor = $this->getMock($qualifiedAuthorClassName);
		$mockPost = $this->getMock($qualifiedPostClassName);

		$identityMap = new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap();
		$identityMap->registerObject($mockAuthor, 'fakeAuthorUUID');

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->once())->method('createEmptyObject')->with($qualifiedPostClassName, $mockObjectConfiguration)->will($this->returnValue($mockPost));
		$mockObjectBuilder->expects($this->once())->method('reinjectDependencies');

		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$postClassSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\Post');
		$postClassSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
		$postClassSchema->setRepositoryManaged(TRUE);
		$postClassSchema->setProperty('author', $qualifiedAuthorClassName);

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Manager', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($persistenceSession));
		$mockPersistenceManager->expects($this->once())->method('getClassSchema')->with($qualifiedPostClassName)->will($this->returnValue($postClassSchema));

		$authorNode = $this->getMock('F3\PHPCR\NodeInterface');
		$authorNode->expects($this->any())->method('getIdentifier')->will($this->returnValue('fakeAuthorUUID'));
		$authorProperty = $this->getMock('F3\PHPCR\PropertyInterface');
		$authorProperty->expects($this->any())->method('getNode')->will($this->returnValue($authorNode));
		$mockProxyPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockProxyPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_OBJECTPROXY));
		$authorProxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$authorProxyNode->expects($this->at(0))->method('getPrimaryNodeType')->will($this->returnValue($mockProxyPrimaryNodeType));
		$authorProxyNode->expects($this->at(1))->method('getProperty')->with('flow3:target')->will($this->returnValue($authorProperty));

		$mockPostPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPostPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:' . $qualifiedPostClassName));
		$postNode = $this->getMock('F3\PHPCR\NodeInterface');
		$postNode->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPostPrimaryNodeType));
		$postNode->expects($this->any())->method('hasNode')->will($this->returnValue(TRUE));
		$postNode->expects($this->once())->method('getNode')->with('flow3:author')->will($this->returnValue($authorProxyNode));

		$dataMapper = $this->getMock('F3\TYPO3CR\FLOW3\Persistence\DataMapper', array('thawProperties'), array());
		$dataMapper->injectObjectManager($mockObjectManager);
		$dataMapper->injectObjectBuilder($mockObjectBuilder);
		$dataMapper->injectIdentityMap($identityMap);
		$dataMapper->injectPersistenceManager($mockPersistenceManager);

		$dataMapper->map(new \F3\TYPO3CR\NodeIterator(array($postNode)));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function thawPropertiesSetsPropertyValues() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->at(0))->method('AOPProxySetProperty')->with('firstProperty', 'firstValue');
		$object->expects($this->at(1))->method('AOPProxySetProperty')->with('secondProperty', 'secondValue');

		$properties = array(
			'firstProperty' => 'firstValue',
			'secondProperty' => 'secondValue'
		);

		$dataMapper = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\FLOW3\Persistence\DataMapper'), array('dummy'), array(), '');
		$dataMapper->_call('thawProperties', $object, $properties);
	}

}

?>
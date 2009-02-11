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
		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->exactly(2))->method('reconstituteObject')->with('Tests\Virtual\Entity', $mockObjectConfiguration, array())->will($this->returnValue(new \stdClass()));
		$identityMap = new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Manager', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($persistenceSession));

		$mockPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:Tests\Virtual\Entity'));
		$node1 = $this->getMock('F3\PHPCR\NodeInterface');
		$node1->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPrimaryNodeType));
		$node2 = $this->getMock('F3\PHPCR\NodeInterface');
		$node2->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPrimaryNodeType));
		$nodeIterator = new \F3\TYPO3CR\NodeIterator(array($node1, $node2));

		$dataMapper = new \F3\TYPO3CR\FLOW3\Persistence\DataMapper();
		$dataMapper->injectObjectManager($mockObjectManager);
		$dataMapper->injectObjectBuilder($mockObjectBuilder);
		$dataMapper->injectIdentityMap($identityMap);
		$dataMapper->injectPersistenceManager($mockPersistenceManager);

		$dataMapper->map($nodeIterator);
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
		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockEntity = $this->getMock('stdClass');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->any())->method('reconstituteObject')->will($this->returnValue($mockEntity));
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Manager', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($persistenceSession));
		$mockPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:Tests_Virtual_Entity'));
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
		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockEntity = $this->getMock('stdClass');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->any())->method('reconstituteObject')->will($this->returnValue($mockEntity));
		$identityMap = new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap();
		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Manager', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($mockPersistenceSession));
		$mockPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:Tests_Virtual_Entity'));
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
		eval('namespace F3; class ' . $authorClassName . ' { public function AOPProxyGetProxyTargetClassName() { return get_class($this); } public function isNew() { return TRUE; } public function memorizeCleanState() {} }');

			// set up (mock) objects
		$mockAuthor = new $qualifiedAuthorClassName();

		$postClassSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\Post');
		$postClassSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
		$postClassSchema->setRepositoryManaged(TRUE);
		$postClassSchema->setProperty('author', $qualifiedAuthorClassName);

		$mockPostRepository = $this->getMock('F3\FLOW3\Persistence\Repository');
		$mockPostRepository->expects($this->once())->method('findByUUID')->with('fakeUUID')->will($this->returnValue($mockAuthor));

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectManager->expects($this->any())->method('getObject')->with('PostRepository')->will($this->returnValue($mockPostRepository));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->at(0))->method('reconstituteObject')->with('F3\Post', $mockObjectConfiguration, array('author' => $mockAuthor))->will($this->returnValue(new \stdClass()));

		$identityMap = new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Manager', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($persistenceSession));
		$mockPersistenceManager->expects($this->once())->method('getClassSchema')->with('F3\Post')->will($this->returnValue($postClassSchema));

		$mockProxyPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockProxyPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue(\F3\TYPO3CR\FLOW3\Persistence\Backend::NODETYPE_OBJECTPROXY));
		$uuidValue = $this->getMock('F3\PHPCR\ValueInterface');
		$uuidValue->expects($this->any())->method('getString')->will($this->returnValue('fakeUUID'));
		$repositoryClassNameValue = $this->getMock('F3\PHPCR\ValueInterface');
		$repositoryClassNameValue->expects($this->any())->method('getString')->will($this->returnValue('PostRepository'));
		$proxyNode = $this->getMock('F3\PHPCR\NodeInterface');
		$proxyNode->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockProxyPrimaryNodeType));
		$proxyNode->expects($this->at(1))->method('getProperty')->with('flow3:target')->will($this->returnValue($uuidValue));
		$proxyNode->expects($this->at(2))->method('getProperty')->with('flow3:repositoryClassName')->will($this->returnValue($repositoryClassNameValue));

		$mockPostPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPostPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:F3\Post'));
		$postNode = $this->getMock('F3\PHPCR\NodeInterface');
		$postNode->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPostPrimaryNodeType));
		$postNode->expects($this->any())->method('hasNode')->will($this->returnValue(TRUE));
		$postNode->expects($this->once())->method('getNode')->with('flow3:author')->will($this->returnValue($proxyNode));

		$nodeIterator = new \F3\TYPO3CR\NodeIterator(array($postNode));

		$dataMapper = new \F3\TYPO3CR\FLOW3\Persistence\DataMapper();
		$dataMapper->injectObjectManager($mockObjectManager);
		$dataMapper->injectObjectBuilder($mockObjectBuilder);
		$dataMapper->injectIdentityMap($identityMap);
		$dataMapper->injectPersistenceManager($mockPersistenceManager);

		$dataMapper->map($nodeIterator);
	}
}

?>
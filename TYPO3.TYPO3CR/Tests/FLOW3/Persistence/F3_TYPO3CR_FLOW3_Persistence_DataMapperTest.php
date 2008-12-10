<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\FLOW3\Persistence;

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
 * Testcase for \F3\TYPO3CR\FLOW3\Persistence\DataMapper
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DataMapperTest extends \F3\Testing\BaseTestCase {

	/**
	 * The assertions at the end of the test are nice, but the expectations of the
	 * mock objects are also very important. If this ever breaks, one will have to
	 * look at the inner workings of map() and it's called methods, though. Sorry.
	 *
	 * @ test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapReturnsCorrectObjectsFromNodes() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array()));

		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockEntity = $this->getMock('stdClass', array(), array(), 'Tests\Virtual\Entity');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);
		$mockObjectBuilder->expects($this->exactly(2))->method('reconstituteObject')->with('Tests\Virtual\Entity', $mockObjectConfiguration, array())->will($this->returnValue($mockEntity));
		$identityMap = new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap();
		$persistenceSession = new \F3\FLOW3\Persistence\Session();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Manager', array(), array(), '', FALSE);
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$mockPersistenceManager->expects($this->atLeastOnce())->method('getSession')->will($this->returnValue($persistenceSession));

		$mockPrimaryNodeType = $this->getMock('F3\PHPCR\NodeType\NodeTypeInterface');
		$mockPrimaryNodeType->expects($this->any())->method('getName')->will($this->returnValue('flow3:Tests\Virtual\Entity'));
		$node1 = $this->getMock('F3\PHPCR\NodeInterface');
		$node1->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPrimaryNodeType));
		$node1->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$node2 = $this->getMock('F3\PHPCR\NodeInterface');
		$node2->expects($this->any())->method('getPrimaryNodeType')->will($this->returnValue($mockPrimaryNodeType));
		$node2->expects($this->any())->method('getProperties')->will($this->returnValue(array()));
		$nodeIterator = new \F3\TYPO3CR\NodeIterator(array($node1, $node2));

		$dataMapper = new \F3\TYPO3CR\FLOW3\Persistence\DataMapper();
		$dataMapper->injectObjectManager($mockObjectManager);
		$dataMapper->injectObjectBuilder($mockObjectBuilder);
		$dataMapper->injectIdentityMap($identityMap);
		$dataMapper->injectPersistenceManager($mockPersistenceManager);

		$objects = $dataMapper->map($nodeIterator);
		$this->assertEquals(2, count($objects), 'Did not get back the expected 2 objects.');
		$this->assertType('Tests\Virtual\Entity', $objects[0], 'Did not get back the expected object type.');
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

}

?>
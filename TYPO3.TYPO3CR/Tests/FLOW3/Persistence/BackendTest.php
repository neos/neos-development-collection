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

require_once(__DIR__ . '/../../Fixtures/AnEntity.php');
require_once(__DIR__ . '/../../Fixtures/AValue.php');

/**
 * Testcase for \F3\TYPO3CR\FLOW3\Persistence\Backend
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class BackendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeCreatesStorageContainerNodeIfNotPresent() {
		$nodeTypeTemplate = $this->getMock('F3\TYPO3CR\NodeType\NodeTypeTemplate');
		$nodeTypeTemplate->expects($this->once())->method('setName')->with('flow3:arrayPropertyProxy');
		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->once())->method('hasNodeType')->with('flow3:arrayPropertyProxy')->will($this->returnValue(FALSE));
		$mockNodeTypeManager->expects($this->once())->method('createNodeTypeTemplate')->will($this->returnValue($nodeTypeTemplate));
		$mockNodeTypeManager->expects($this->once())->method('registerNodeType')->with($nodeTypeTemplate, FALSE);

		$mockBaseNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockPersistenceNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockPersistenceNode->expects($this->once())->method('addNode')->with('flow3:objects', 'nt:unstructured')->will($this->returnValue($mockBaseNode));
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->exactly(2))->method('hasNode')->will($this->returnValue(FALSE));
		$mockRootNode->expects($this->once())->method('addNode')->with('flow3:persistence', 'nt:unstructured')->will($this->returnValue($mockPersistenceNode));
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize(array());

		$this->assertAttributeSame($mockBaseNode, 'baseNode', $backend);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeCreatesNodeTypeFromClassSchema() {
		$classSchemata = array(
			new \F3\FLOW3\Persistence\ClassSchema('Some\Package\SomeClass')
		);
		$nodeTypeTemplate = $this->getMock('F3\TYPO3CR\NodeType\NodeTypeTemplate');
		$nodeTypeTemplate->expects($this->once())->method('setName')->with('flow3:Some_Package_SomeClass');
		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->exactly(2))->method('hasNodeType')->will($this->onConsecutiveCalls(TRUE, FALSE));
		$mockNodeTypeManager->expects($this->once())->method('createNodeTypeTemplate')->will($this->returnValue($nodeTypeTemplate));
		$mockNodeTypeManager->expects($this->once())->method('registerNodeType')->with($nodeTypeTemplate, FALSE);

		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->any())->method('hasNode')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects');
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize($classSchemata);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesNewObjects() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\TYPO3CR\\Tests\\' . $className;
		eval('namespace F3\\TYPO3CR\\Tests; class ' . $className . ' {
			public function isNew() { return TRUE; }
			public function isDirty($propertyName) { return FALSE; }
			public function memorizeCleanState($joinPoint = NULL) {}
			public function AOPProxyGetProxyTargetClassName() { return \'' . $fullClassName . '\';}
		}');
		$newObject = new $fullClassName();
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($newObject);

		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockInstanceNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockBaseNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_Tests_' . $className, 'flow3:F3_TYPO3CR_Tests_' . $className)->will($this->returnValue($mockInstanceNode));
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');

		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize(array($fullClassName => new \F3\FLOW3\Persistence\ClassSchema($fullClassName)));
		$backend->injectIdentityMap(new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap());
		$backend->setAggregateRootObjects($aggregateRootObjects);
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function uuidPropertyNameFromNewObjectIsUsedForNode() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\TYPO3CR\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\TYPO3CR\\Tests; class ' . $className . ' {
			public function isNew() { return TRUE; }
			public function isDirty($propertyName) { return FALSE; }
			public function memorizeCleanState($joinPoint = NULL) {}
			public function AOPProxyGetProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function AOPProxyGetProperty($name) { return \'' . $identifier . '\'; }
		}');
		$newObject = new $fullClassName();
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($newObject);

		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockInstanceNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockBaseNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->/*with('flow3:F3_TYPO3CR_Tests_' . $className, 'flow3:F3_TYPO3CR_Tests_' . $className, $identifier)->*/will($this->returnValue($mockInstanceNode));
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');
		$classSchema = new \F3\FLOW3\Persistence\ClassSchema($fullClassName);
		$classSchema->setUUIDPropertyName('idProp');

		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize(array($classSchema->getClassName() => $classSchema));
		$backend->injectIdentityMap(new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap());
		$backend->setAggregateRootObjects($aggregateRootObjects);
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesDirtyObjects() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\TYPO3CR\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\TYPO3CR\\Tests; class ' . $className . ' {
			public $simpleString;
			protected $dirty = TRUE;
			public function isNew() { return FALSE; }
			public function isDirty($propertyName) { return $this->dirty; }
			public function memorizeCleanState($joinPoint = NULL) { $this->dirty = FALSE; }
			public function AOPProxyGetProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function AOPProxyGetProperty($propertyName) { return $this->$propertyName; }
		}');
		$dirtyObject = new $fullClassName();
		$dirtyObject->simpleString = 'simpleValue';
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($dirtyObject);

		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockBaseNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockInstanceNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockInstanceNode->expects($this->once())->method('setProperty');
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with($identifier)->will($this->returnValue($mockInstanceNode));

		$classSchema = new \F3\FLOW3\Persistence\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
		$classSchema->setProperty('simpleString', 'string');

		$identityMap = new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap();
		$identityMap->registerObject($dirtyObject, $identifier);

		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize(array($classSchema->getClassName() => $classSchema));
		$backend->injectIdentityMap($identityMap);
		$backend->setAggregateRootObjects($aggregateRootObjects);

		$this->assertTrue($dirtyObject->isDirty('simpleString'));
		$backend->commit();
		$this->assertFalse($dirtyObject->isDirty('simpleString'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesDeletedObjects() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\TYPO3CR\\Tests\\' . $className;
		$identifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		eval('namespace F3\\TYPO3CR\\Tests; class ' . $className . ' {
			public function AOPProxyGetProxyTargetClassName() { return \'' . $fullClassName . '\'; }
		}');
		$deletedObject = new $fullClassName();
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($deletedObject);

		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockInstanceNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockInstanceNode->expects($this->once())->method('remove');
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockInstanceNode));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with($identifier)->will($this->returnValue($mockInstanceNode));
		$mockSession->expects($this->once())->method('save');
		$classSchema = new \F3\FLOW3\Persistence\ClassSchema($fullClassName);
		$identityMap = new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap();
		$identityMap->registerObject($deletedObject, $identifier);

		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize(array($classSchema->getClassName() => $classSchema));
		$backend->injectIdentityMap($identityMap);
		$backend->setDeletedObjects($aggregateRootObjects);
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function nestedObjectsAreStoredAsNestedNodes() {
			// set up object
		$A = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('A');
		$B = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('B');
		$B->add(new \F3\TYPO3CR\Tests\Fixtures\AnEntity('BA'));
		$B->add(new \F3\TYPO3CR\Tests\Fixtures\AnEntity('BB'));
		$A->add($B);
		$C = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('C');
		$A->add($C);
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($A);

			// set up assertions on created nodes
		$mockNodeBA = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeBA->expects($this->exactly(2))->method('setProperty');
		$mockNodeBB = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeBB->expects($this->exactly(2))->method('setProperty');
		$arrayProxyB = $this->getMock('F3\PHPCR\NodeInterface');
		$arrayProxyB->expects($this->exactly(2))->method('addNode')->will($this->onConsecutiveCalls($mockNodeBA, $mockNodeBB));
		$mockNodeB = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeB->expects($this->exactly(1))->method('addNode')->will($this->returnValue($arrayProxyB));
		$mockNodeB->expects($this->exactly(1))->method('setProperty')->with('flow3:name', 'B', \F3\PHPCR\PropertyType::STRING);
		$mockNodeC = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeC->expects($this->exactly(2))->method('setProperty');
		$arrayProxyA = $this->getMock('F3\PHPCR\NodeInterface');
		$arrayProxyA->expects($this->exactly(2))->method('addNode')->will($this->onConsecutiveCalls($mockNodeB, $mockNodeC));
		$mockNodeA = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeA->expects($this->exactly(1))->method('addNode')->will($this->returnValue($arrayProxyA));
		$mockNodeA->expects($this->exactly(1))->method('setProperty')->with('flow3:name', 'A', \F3\PHPCR\PropertyType::STRING);
		$mockBaseNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_Tests_Fixtures_AnEntity', 'flow3:F3_TYPO3CR_Tests_Fixtures_AnEntity')->will($this->returnValue($mockNodeA));

			// set up needed infrastructure
		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');

		$mockSession->expects($this->exactly(5))->method('getNodeByIdentifier')->will($this->onConsecutiveCalls($mockNodeA, $mockNodeB, $mockNodeBA, $mockNodeBB, $mockNodeC));

		$classSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\TYPO3CR\Tests\AnObject');
		$classSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
		$classSchema->setProperty('name', 'string');
		$classSchema->setProperty('members', 'array');

			// ... and here we go
		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize(array('F3\TYPO3CR\Tests\Fixtures\AnEntity' => $classSchema));
		$backend->injectIdentityMap(new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap());
		$backend->setAggregateRootObjects($aggregateRootObjects);
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesNewObjectsWithDateTimeMembers() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3\\TYPO3CR\\Tests\\' . $className;
		eval('namespace F3\\TYPO3CR\\Tests; class ' . $className . ' {
			public $date;
			public function isNew() { return TRUE; }
			public function isDirty($propertyName) { return TRUE; }
			public function memorizeCleanState($joinPoint = NULL) {}
			public function AOPProxyGetProxyTargetClassName() { return \'' . $fullClassName . '\';}
			public function AOPProxyGetProperty($propertyName) { return $this->$propertyName; }
		}');
		$newObject = new $fullClassName();
		$date = new \DateTime();
		$newObject->date = $date;
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($newObject);

		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockInstanceNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockInstanceNode->expects($this->never())->method('addNode');
		$mockInstanceNode->expects($this->once())->method('setProperty')->with('flow3:date', $date, \F3\PHPCR\PropertyType::DATE);
		$mockBaseNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_Tests_' . $className, 'flow3:F3_TYPO3CR_Tests_' . $className)->will($this->returnValue($mockInstanceNode));
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->will($this->returnValue($mockInstanceNode));

		$classSchema = new \F3\FLOW3\Persistence\ClassSchema($fullClassName);
		$classSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
		$classSchema->setProperty('date', 'DateTime');

		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize(array($fullClassName => $classSchema));
		$backend->injectIdentityMap(new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap());
		$backend->setAggregateRootObjects($aggregateRootObjects);
		$backend->commit();
	}


	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function valueObjectsAreStoredAsOftenAsUsedInAnEntity() {
			// set up object
		$A = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('A');
		$B = new \F3\TYPO3CR\Tests\Fixtures\AValue('B');
		$A->add($B);
		$A->add($B);
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($A);

			// set up assertions on created nodes
		$mockNodeB1 = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeB1->expects($this->once())->method('setProperty')->with('flow3:name', 'B', \F3\PHPCR\PropertyType::STRING);
		$mockNodeB2 = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeB2->expects($this->once())->method('setProperty')->with('flow3:name', 'B', \F3\PHPCR\PropertyType::STRING);
		$arrayProxy = $this->getMock('F3\PHPCR\NodeInterface');
		$arrayProxy->expects($this->exactly(2))->method('addNode')->will($this->onConsecutiveCalls($mockNodeB1, $mockNodeB2));
		$arrayProxy->expects($this->at(0))->method('addNode')->with('flow3:0');
		$arrayProxy->expects($this->at(1))->method('addNode')->with('flow3:1');
		$mockNodeA = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeA->expects($this->once())->method('addNode')->will($this->returnValue($arrayProxy));
		$mockNodeA->expects($this->once())->method('setProperty')->with('flow3:name', 'A', \F3\PHPCR\PropertyType::STRING);
		$mockBaseNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_Tests_Fixtures_AnEntity', 'flow3:F3_TYPO3CR_Tests_Fixtures_AnEntity')->will($this->returnValue($mockNodeA));

			// set up needed infrastructure
		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');

		$mockSession->expects($this->once())->method('getNodeByIdentifier')->will($this->returnValue($mockNodeA));

		$entityClassSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\TYPO3CR\Tests\Fixture\AnEntity');
		$entityClassSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
		$entityClassSchema->setProperty('name', 'string');
		$entityClassSchema->setProperty('members', 'array');
		$valueClassSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\TYPO3CR\Tests\Fixture\AValue');
		$valueClassSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_VALUEOBJECT);
		$valueClassSchema->setProperty('name', 'string');

			// ... and here we go
		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize(array('F3\TYPO3CR\Tests\Fixtures\AnEntity' => $entityClassSchema, 'F3\TYPO3CR\Tests\Fixtures\AValue' => $valueClassSchema));
		$backend->injectIdentityMap(new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap());
		$backend->setAggregateRootObjects($aggregateRootObjects);
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function aValueObjectIsStoredAsOftenAsUsedInEntities() {
			// set up object
		$A = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('A');
		$B = new \F3\TYPO3CR\Tests\Fixtures\AnEntity('B');
		$value = new \F3\TYPO3CR\Tests\Fixtures\AValue('value');
		$A->setValue($value);
		$B->setValue($value);
		$aggregateRootObjects = new \SplObjectStorage();
		$aggregateRootObjects->attach($A);
		$aggregateRootObjects->attach($B);

			// set up assertions on created nodes
		$mockNodeValue1 = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeValue1->expects($this->once())->method('setProperty')->with('flow3:name', 'value', \F3\PHPCR\PropertyType::STRING);
		$mockNodeValue2 = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeValue2->expects($this->once())->method('setProperty')->with('flow3:name', 'value', \F3\PHPCR\PropertyType::STRING);
		$mockNodeA = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeA->expects($this->once())->method('addNode')->with('flow3:value', 'flow3:F3_TYPO3CR_Tests_Fixtures_AValue')->will($this->returnValue($mockNodeValue1));
		$mockNodeA->expects($this->once())->method('setProperty')->with('flow3:name', 'A', \F3\PHPCR\PropertyType::STRING);
		$mockNodeB = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNodeB->expects($this->once())->method('addNode')->with('flow3:value', 'flow3:F3_TYPO3CR_Tests_Fixtures_AValue')->will($this->returnValue($mockNodeValue2));
		$mockNodeB->expects($this->once())->method('setProperty')->with('flow3:name', 'B', \F3\PHPCR\PropertyType::STRING);
		$mockBaseNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockBaseNode->expects($this->exactly(2))->method('addNode')->with('flow3:F3_TYPO3CR_Tests_Fixtures_AnEntity', 'flow3:F3_TYPO3CR_Tests_Fixtures_AnEntity')->will($this->onConsecutiveCalls($mockNodeA, $mockNodeB));

			// set up needed infrastructure
		$mockNodeTypeManager = $this->getMock('F3\PHPCR\NodeType\NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3\PHPCR\WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');

		$mockSession->expects($this->exactly(4))->method('getNodeByIdentifier')->will($this->onConsecutiveCalls($mockNodeA, $mockNodeValue1, $mockNodeB, $mockNodeValue2));

		$entityClassSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\TYPO3CR\Tests\Fixture\AnEntity');
		$entityClassSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
		$entityClassSchema->setProperty('name', 'string');
		$entityClassSchema->setProperty('value', 'F3\TYPO3CR\Tests\Fixture\AValue');
		$valueClassSchema = new \F3\FLOW3\Persistence\ClassSchema('F3\TYPO3CR\Tests\Fixture\AValue');
		$valueClassSchema->setModelType(\F3\FLOW3\Persistence\ClassSchema::MODELTYPE_VALUEOBJECT);
		$valueClassSchema->setProperty('name', 'string');

			// ... and here we go
		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->initialize(array('F3\TYPO3CR\Tests\Fixtures\AnEntity' => $entityClassSchema, 'F3\TYPO3CR\Tests\Fixtures\AValue' => $valueClassSchema));
		$backend->injectIdentityMap(new \F3\TYPO3CR\FLOW3\Persistence\IdentityMap());
		$backend->setAggregateRootObjects($aggregateRootObjects);
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getUUIDReturnsUUIDForKnownObject() {
		$knownObject = new \stdClass();
		$fakeUUID = '123-456';

		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockIdentityMap = $this->getMock('F3\TYPO3CR\FLOW3\Persistence\IdentityMap');
		$mockIdentityMap->expects($this->once())->method('hasObject')->with($knownObject)->will($this->returnValue(TRUE));
		$mockIdentityMap->expects($this->once())->method('getUUID')->with($knownObject)->will($this->returnValue($fakeUUID));
		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->injectIdentityMap($mockIdentityMap);

		$this->assertEquals($fakeUUID, $backend->getUUID($knownObject));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getUUIDReturnsNullForUnknownObject() {
		$unknownObject = new \stdClass();

		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockIdentityMap = $this->getMock('F3\TYPO3CR\FLOW3\Persistence\IdentityMap');
		$mockIdentityMap->expects($this->once())->method('hasObject')->with($unknownObject)->will($this->returnValue(FALSE));
		$backend = new \F3\TYPO3CR\FLOW3\Persistence\Backend($mockSession);
		$backend->injectIdentityMap($mockIdentityMap);

		$this->assertNull($backend->getUUID($unknownObject));
	}

}

?>

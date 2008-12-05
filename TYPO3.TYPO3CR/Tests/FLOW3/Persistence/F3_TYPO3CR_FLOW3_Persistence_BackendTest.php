<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::FLOW3::Persistence;

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

require_once(__DIR__ . '/../../Fixtures/F3_TYPO3CR_Tests_AnObject.php');

/**
 * Testcase for F3::TYPO3CR::FLOW3::Persistence::Backend
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class BackendTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeCreatesStorageContainerNodeIfNotPresent() {
		$nodeTypeTemplate = $this->getMock('F3::TYPO3CR::NodeType::NodeTypeTemplate');
		$nodeTypeTemplate->expects($this->once())->method('setName')->with('flow3:arrayPropertyProxy');
		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->once())->method('hasNodeType')->with('flow3:arrayPropertyProxy')->will($this->returnValue(FALSE));
		$mockNodeTypeManager->expects($this->once())->method('createNodeTypeTemplate')->will($this->returnValue($nodeTypeTemplate));
		$mockNodeTypeManager->expects($this->once())->method('registerNodeType')->with($nodeTypeTemplate, FALSE);

		$mockBaseNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockPersistenceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockPersistenceNode->expects($this->once())->method('addNode')->with('flow3:objects', 'nt:unstructured')->will($this->returnValue($mockBaseNode));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->exactly(2))->method('hasNode')->will($this->returnValue(FALSE));
		$mockRootNode->expects($this->once())->method('addNode')->with('flow3:persistence', 'nt:unstructured')->will($this->returnValue($mockPersistenceNode));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array());

		$this->assertAttributeSame($mockBaseNode, 'baseNode', $backend);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeCreatesNodeTypeFromClassSchema() {
		$classSchemata = array(
			new F3::FLOW3::Persistence::ClassSchema('Some::Package::SomeClass')
		);
		$nodeTypeTemplate = $this->getMock('F3::TYPO3CR::NodeType::NodeTypeTemplate');
		$nodeTypeTemplate->expects($this->once())->method('setName')->with('flow3:Some_Package_SomeClass');
		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->exactly(2))->method('hasNodeType')->will($this->onConsecutiveCalls(TRUE, FALSE));
		$mockNodeTypeManager->expects($this->once())->method('createNodeTypeTemplate')->will($this->returnValue($nodeTypeTemplate));
		$mockNodeTypeManager->expects($this->once())->method('registerNodeType')->with($nodeTypeTemplate, FALSE);

		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->any())->method('hasNode')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects');
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize($classSchemata);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesNewObjects() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3::TYPO3CR::Tests::' . $className;
		eval('namespace F3::TYPO3CR::Tests; class ' . $className . ' {
			public function isNew() { return TRUE; }
			public function isDirty($propertyName) { return FALSE; }
			public function memorizeCleanState($joinPoint = NULL) {}
			public function AOPProxyGetProxyTargetClassName() { return \'' . $fullClassName . '\';}
		}');
		$newObject = new $fullClassName();

		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockInstanceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockBaseNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_Tests_' . $className, 'flow3:F3_TYPO3CR_Tests_' . $className)->will($this->returnValue($mockInstanceNode));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array($fullClassName => new F3::FLOW3::Persistence::ClassSchema($fullClassName)));
		$backend->injectIdentityMap(new F3::TYPO3CR::FLOW3::Persistence::IdentityMap());
		$backend->setAggregateRootObjects(array(spl_object_hash($newObject) => $newObject));
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function identifierPropertyFromNewObjectIsUsedForNode() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3::TYPO3CR::Tests::' . $className;
		$identifier = F3::FLOW3::Utility::Algorithms::generateUUID();
		eval('namespace F3::TYPO3CR::Tests; class ' . $className . ' {
			public function isNew() { return TRUE; }
			public function isDirty($propertyName) { return FALSE; }
			public function memorizeCleanState($joinPoint = NULL) {}
			public function AOPProxyGetProxyTargetClassName() { return \'' . $fullClassName . '\'; }
			public function AOPProxyGetProperty($name) { return \'' . $identifier . '\'; }
		}');
		$newObject = new $fullClassName();

		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockInstanceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockBaseNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->/*with('flow3:F3_TYPO3CR_Tests_' . $className, 'flow3:F3_TYPO3CR_Tests_' . $className, $identifier)->*/will($this->returnValue($mockInstanceNode));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');
		$classSchema = new F3::FLOW3::Persistence::ClassSchema($fullClassName);
		$classSchema->setIdentifierProperty('idProp');

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array($classSchema->getClassName() => $classSchema));
		$backend->injectIdentityMap(new F3::TYPO3CR::FLOW3::Persistence::IdentityMap());
		$backend->setAggregateRootObjects(array(spl_object_hash($newObject) => $newObject));
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesDirtyObjects() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3::TYPO3CR::Tests::' . $className;
		$identifier = F3::FLOW3::Utility::Algorithms::generateUUID();
		eval('namespace F3::TYPO3CR::Tests; class ' . $className . ' {
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

		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockBaseNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockInstanceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockInstanceNode->expects($this->once())->method('setProperty');
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with($identifier)->will($this->returnValue($mockInstanceNode));

		$classSchema = new F3::FLOW3::Persistence::ClassSchema($fullClassName);
		$classSchema->setModelType(F3::FLOW3::Persistence::ClassSchema::MODELTYPE_ENTITY);
		$classSchema->setProperty('simpleString', 'string');

		$identityMap = new F3::TYPO3CR::FLOW3::Persistence::IdentityMap();
		$identityMap->registerObject($dirtyObject, $identifier);

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array($classSchema->getClassName() => $classSchema));
		$backend->injectIdentityMap($identityMap);
		$backend->setAggregateRootObjects(array(spl_object_hash($dirtyObject) => $dirtyObject));

		$this->assertTrue($dirtyObject->isDirty());
		$backend->commit();
		$this->assertFalse($dirtyObject->isDirty());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesDeletedObjects() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3::TYPO3CR::Tests::' . $className;
		$identifier = F3::FLOW3::Utility::Algorithms::generateUUID();
		eval('namespace F3::TYPO3CR::Tests; class ' . $className . ' {
			public function AOPProxyGetProxyTargetClassName() { return \'' . $fullClassName . '\'; }
		}');
		$deletedObject = new $fullClassName();

		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockInstanceNode));
		$mockInstanceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockInstanceNode->expects($this->once())->method('remove');
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with($identifier)->will($this->returnValue($mockInstanceNode));
		$mockSession->expects($this->once())->method('save');
		$classSchema = new F3::FLOW3::Persistence::ClassSchema($fullClassName);
		$identityMap = new F3::TYPO3CR::FLOW3::Persistence::IdentityMap();
		$identityMap->registerObject($deletedObject, $identifier);

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array($classSchema->getClassName() => $classSchema));
		$backend->injectIdentityMap($identityMap);
		$backend->setDeletedObjects(array(spl_object_hash($deletedObject) => $deletedObject));
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function nestedObjectsAreStoredAsNestedNodes() {
			// set up object
		$A = new F3::TYPO3CR::Tests::AnObject('A');
		$B = new F3::TYPO3CR::Tests::AnObject('B');
		$B->add(new F3::TYPO3CR::Tests::AnObject('BA'));
		$B->add(new F3::TYPO3CR::Tests::AnObject('BB'));
		$A->add($B);
		$C = new F3::TYPO3CR::Tests::AnObject('C');
		$A->add($C);

			// set up assertions on created nodes
		$mockNodeBA = $this->getMock('F3::PHPCR::NodeInterface');
		$mockNodeBA->expects($this->exactly(2))->method('setProperty');
		$mockNodeBB = $this->getMock('F3::PHPCR::NodeInterface');
		$mockNodeBB->expects($this->exactly(2))->method('setProperty');
		$arrayProxyB = $this->getMock('F3::PHPCR::NodeInterface');
		$arrayProxyB->expects($this->exactly(2))->method('addNode')->will($this->onConsecutiveCalls($mockNodeBA, $mockNodeBB));
		$mockNodeB = $this->getMock('F3::PHPCR::NodeInterface');
		$mockNodeB->expects($this->exactly(1))->method('addNode')->will($this->onConsecutiveCalls($arrayProxyB));
		$mockNodeB->expects($this->exactly(1))->method('setProperty')->with('flow3:name', 'B', F3::PHPCR::PropertyType::STRING);
		$mockNodeC = $this->getMock('F3::PHPCR::NodeInterface');
		$mockNodeC->expects($this->exactly(2))->method('setProperty');
		$arrayProxyA = $this->getMock('F3::PHPCR::NodeInterface');
		$arrayProxyA->expects($this->exactly(2))->method('addNode')->will($this->onConsecutiveCalls($mockNodeB, $mockNodeC));
		$mockNodeA = $this->getMock('F3::PHPCR::NodeInterface');
		$mockNodeA->expects($this->exactly(1))->method('addNode')->will($this->onConsecutiveCalls($arrayProxyA));
		$mockNodeA->expects($this->exactly(1))->method('setProperty')->with('flow3:name', 'A', F3::PHPCR::PropertyType::STRING);
		$mockBaseNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_Tests_AnObject', 'flow3:F3_TYPO3CR_Tests_AnObject')->will($this->returnValue($mockNodeA));

			// set up needed infrastructure
		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');

		$mockSession->expects($this->exactly(5))->method('getNodeByIdentifier')->will($this->onConsecutiveCalls($mockNodeA, $mockNodeB, $mockNodeBA, $mockNodeBB, $mockNodeC));

		$classSchema = new F3::FLOW3::Persistence::ClassSchema('F3::TYPO3CR::Tests::AnObject');
		$classSchema->setModelType(F3::FLOW3::Persistence::ClassSchema::MODELTYPE_ENTITY);
		$classSchema->setProperty('name', 'string');
		$classSchema->setProperty('members', 'array');

			// ... and here we go
		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array('F3::TYPO3CR::Tests::AnObject' => $classSchema));
		$backend->injectIdentityMap(new F3::TYPO3CR::FLOW3::Persistence::IdentityMap());
		$backend->setAggregateRootObjects(array(spl_object_hash($A) => $A));
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesNewObjectsWithDateTimeMembers() {
		$className = 'SomeClass' . uniqid();
		$fullClassName = 'F3::TYPO3CR::Tests::' . $className;
		eval('namespace F3::TYPO3CR::Tests; class ' . $className . ' {
			public $date;
			public function isNew() { return TRUE; }
			public function isDirty($propertyName) { return TRUE; }
			public function memorizeCleanState($joinPoint = NULL) {}
			public function AOPProxyGetProxyTargetClassName() { return \'' . $fullClassName . '\';}
			public function AOPProxyGetProperty($propertyName) { return $this->$propertyName; }
		}');
		$newObject = new $fullClassName();
		$date = new DateTime();
		$newObject->date = $date;

		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->any())->method('hasNodeType')->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockInstanceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockInstanceNode->expects($this->never())->method('addNode');
		$mockInstanceNode->expects($this->once())->method('setProperty')->with('flow3:date', $date, F3::PHPCR::PropertyType::DATE);
		$mockBaseNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_Tests_' . $className, 'flow3:F3_TYPO3CR_Tests_' . $className)->will($this->returnValue($mockInstanceNode));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->will($this->returnValue($mockInstanceNode));

		$classSchema = new F3::FLOW3::Persistence::ClassSchema($fullClassName);
		$classSchema->setModelType(F3::FLOW3::Persistence::ClassSchema::MODELTYPE_ENTITY);
		$classSchema->setProperty('date', 'DateTime');

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array($fullClassName => $classSchema));
		$backend->injectIdentityMap(new F3::TYPO3CR::FLOW3::Persistence::IdentityMap());
		$backend->setAggregateRootObjects(array(spl_object_hash($newObject) => $newObject));
		$backend->commit();
	}

}

?>

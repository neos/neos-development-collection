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
		$mockBaseNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockPersistenceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockPersistenceNode->expects($this->once())->method('addNode')->with('flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(FALSE));
		$mockRootNode->expects($this->once())->method('addNode')->with('flow3:persistence')->will($this->returnValue($mockPersistenceNode));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
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
		$nodeTypeTemplate->expects($this->once())->method('setName')->with('Some_Package_SomeClass');
		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->once())->method('hasNodeType')->with('Some_Package_SomeClass')->will($this->returnValue(FALSE));
		$mockNodeTypeManager->expects($this->once())->method('createNodeTypeTemplate')->will($this->returnValue($nodeTypeTemplate));
		$mockNodeTypeManager->expects($this->once())->method('registerNodeType')->with($nodeTypeTemplate, FALSE);

		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
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
		eval('class ' . $className . ' {
			public function AOPProxyGetProxyTargetClassName() { return \'F3::TYPO3CR::FLOW3::Persistence::' . $className . '\';}
		}');
		$newObject = new $className();

		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->once())->method('hasNodeType')->with('F3_TYPO3CR_FLOW3_Persistence_' . $className)->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockInstanceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockInstanceContainerNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockInstanceContainerNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_FLOW3_Persistence_' . $className . 'Instance', 'flow3:F3_TYPO3CR_FLOW3_Persistence_' . $className)->will($this->returnValue($mockInstanceNode));
		$mockBaseNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_FLOW3_Persistence_' . $className)->will($this->returnValue($mockInstanceContainerNode));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array('F3::TYPO3CR::FLOW3::Persistence::' . $className => new F3::FLOW3::Persistence::ClassSchema('F3::TYPO3CR::FLOW3::Persistence::' . $className)));
		$backend->injectIdentityMap(new F3::TYPO3CR::FLOW3::Persistence::IdentityMap());
		$backend->setNewObjects(array(spl_object_hash($newObject) => $newObject));
		$backend->commit();

		$this->assertAttributeSame(array(), 'newObjects', $backend);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function identifierPropertyFromNewObjectIsUsedForNode() {
		$className = 'SomeClass' . uniqid();
		$identifier = F3::FLOW3::Utility::Algorithms::generateUUID();
		eval('class ' . $className . ' {
			public function AOPProxyGetProxyTargetClassName() { return \'F3::TYPO3CR::FLOW3::Persistence::' . $className . '\'; }
			public function AOPProxyGetProperty($name) { return \'' . $identifier . '\'; }
		}');
		$newObject = new $className();

		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->once())->method('hasNodeType')->with('F3_TYPO3CR_FLOW3_Persistence_' . $className)->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockInstanceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockInstanceContainerNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockInstanceContainerNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_FLOW3_Persistence_' . $className . 'Instance', 'flow3:F3_TYPO3CR_FLOW3_Persistence_' . $className, $identifier)->will($this->returnValue($mockInstanceNode));
		$mockBaseNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockBaseNode->expects($this->once())->method('addNode')->with('flow3:F3_TYPO3CR_FLOW3_Persistence_' . $className)->will($this->returnValue($mockInstanceContainerNode));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('save');
		$classSchema = new F3::FLOW3::Persistence::ClassSchema('F3::TYPO3CR::FLOW3::Persistence::' . $className);
		$classSchema->setIdentifierProperty('idProp');

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array($classSchema->getClassName() => $classSchema));
		$backend->injectIdentityMap(new F3::TYPO3CR::FLOW3::Persistence::IdentityMap());
		$backend->setNewObjects(array(spl_object_hash($newObject) => $newObject));
		$backend->commit();

		$this->assertAttributeSame(array(), 'newObjects', $backend);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesUpdatedObjects() {
		$className = 'SomeClass' . uniqid();
		$identifier = F3::FLOW3::Utility::Algorithms::generateUUID();
		eval('class ' . $className . ' {
			public function AOPProxyGetProxyTargetClassName() { return \'F3::TYPO3CR::FLOW3::Persistence::' . $className . '\'; }
		}');
		$updatedObject = new $className();

		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->once())->method('hasNodeType')->with('F3_TYPO3CR_FLOW3_Persistence_' . $className)->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockInstanceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with($identifier)->will($this->returnValue($mockInstanceNode));
		$mockSession->expects($this->once())->method('save');
		$classSchema = new F3::FLOW3::Persistence::ClassSchema('F3::TYPO3CR::FLOW3::Persistence::' . $className);
		$identityMap = new F3::TYPO3CR::FLOW3::Persistence::IdentityMap();
		$identityMap->registerObject($updatedObject, $identifier);

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array($classSchema->getClassName() => $classSchema));
		$backend->injectIdentityMap($identityMap);
		$backend->setUpdatedObjects(array(spl_object_hash($updatedObject) => $updatedObject));
		$backend->commit();

		$this->assertAttributeSame(array(), 'updatedObjects', $backend);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitProcessesDeletedObjects() {
		$className = 'SomeClass' . uniqid();
		$identifier = F3::FLOW3::Utility::Algorithms::generateUUID();
		eval('class ' . $className . ' {
			public function AOPProxyGetProxyTargetClassName() { return \'F3::TYPO3CR::FLOW3::Persistence::' . $className . '\'; }
		}');
		$deletedObject = new $className();

		$mockNodeTypeManager = $this->getMock('F3::PHPCR::NodeType::NodeTypeManagerInterface');
		$mockNodeTypeManager->expects($this->once())->method('hasNodeType')->with('F3_TYPO3CR_FLOW3_Persistence_' . $className)->will($this->returnValue(TRUE));
		$mockWorkspace = $this->getMock('F3::PHPCR::WorkspaceInterface');
		$mockWorkspace->expects($this->once())->method('getNodeTypeManager')->will($this->returnValue($mockNodeTypeManager));
		$mockRootNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockRootNode->expects($this->once())->method('hasNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue(TRUE));
		$mockRootNode->expects($this->once())->method('getNode')->with('flow3:persistence/flow3:objects')->will($this->returnValue($mockBaseNode));
		$mockInstanceNode = $this->getMock('F3::PHPCR::NodeInterface');
		$mockInstanceNode->expects($this->once())->method('remove');
		$mockSession = $this->getMock('F3::PHPCR::SessionInterface');
		$mockSession->expects($this->once())->method('getRootNode')->will($this->returnValue($mockRootNode));
		$mockSession->expects($this->once())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with($identifier)->will($this->returnValue($mockInstanceNode));
		$mockSession->expects($this->once())->method('save');
		$classSchema = new F3::FLOW3::Persistence::ClassSchema('F3::TYPO3CR::FLOW3::Persistence::' . $className);
		$identityMap = new F3::TYPO3CR::FLOW3::Persistence::IdentityMap();
		$identityMap->registerObject($deletedObject, $identifier);

		$backend = new F3::TYPO3CR::FLOW3::Persistence::Backend($mockSession);
		$backend->initialize(array($classSchema->getClassName() => $classSchema));
		$backend->injectIdentityMap($identityMap);
		$backend->setDeletedObjects(array(spl_object_hash($deletedObject) => $deletedObject));
		$backend->commit();

		$this->assertAttributeSame(array(), 'deletedObjects', $backend);
	}

}

?>
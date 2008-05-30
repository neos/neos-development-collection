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
 * @version $Id$
 */

/**
 * Tests for the NodeType implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_NodeType_NodeTypeTest extends F3_Testing_BaseTestCase {

	/**
	 * Checks if the primary NodeType object returned by two different nodes is not the same one.
	 * This is a check to make sure we don't get singleton NodeType objects from the component manager
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function nodeTypeObjectReturnedIsDifferent() {
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_Storage_BackendInterface');
		$mockStorageAccess->expects($this->any())->method('getIdentifiersOfSubNodesOfNode')->will($this->returnValue(array()));
		$mockStorageAccess->expects($this->any())->method('getRawPropertiesOfNode')->will($this->returnValue(array()));
		$mockStorageAccess->expects($this->any())->method('getRawNodeTypeById')->will($this->returnValue(array('id' => 1, 'name' => 'nodeTypeName')));
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = new F3_TYPO3CR_Session('workspaceName', $mockRepository, $mockStorageAccess, $this->componentManager);

		$nodeA = new F3_TYPO3CR_Node($mockSession, $mockStorageAccess, $this->componentManager);
		$nodeA->initializeFromArray(array(
			'id' => '1',
			'identifier' => '',
			'pid' => '0',
			'nodetype' => '1',
			'name' => 'nodeA'
		));
		$nodeB = new F3_TYPO3CR_Node($mockSession, $mockStorageAccess, $this->componentManager);
		$nodeB->initializeFromArray(array(
			'id' => '2',
			'identifier' => '',
			'pid' => '0',
			'nodetype' => '1',
			'name' => 'nodeB'
		));
		$this->assertNotSame($nodeA->getPrimaryNodeType(), $nodeB->getPrimaryNodeType(), 'getPrimaryNodeType() did not return a different NodeType object on two different nodes.');
	}

	/**
	 * Checks if getName() returns the expected name of a NodeType object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNameReturnsTheExpectedName() {
		$mockStorageAccess = $this->getMock('F3_TYPO3CR_Storage_BackendInterface');
		$mockStorageAccess->expects($this->any())->method('getRawNodeTypeById')->with($this->equalTo(1))->will($this->returnValue(array('id' => 1, 'name' => 'nodeTypeName')));
		$nodeTypeObject = new F3_TYPO3CR_NodeType_NodeType(1, $mockStorageAccess, $this->componentManager);

		$this->assertEquals('nodeTypeName', $nodeTypeObject->getName(), 'getName() on the NodeType object did not return the expected name.');
	}
}
?>
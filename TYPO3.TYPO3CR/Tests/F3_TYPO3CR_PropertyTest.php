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
 * Tests for the Property implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_PropertyTest extends F3_Testing_BaseTestCase {

	/**
	 * Checks if getValue returns a Value object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getValueReturnsAValueObject() {
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array(), '', FALSE);
		$mockNode = $this->getMock('F3_TYPO3CR_Node', array(), array(), '', FALSE);

		$valueObject = new F3_TYPO3CR_Value('somevalue', F3_PHPCR_PropertyType::STRING);

		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');
		$mockValueFactory->expects($this->once())->
			method('createValue')->
			with('testvalue', F3_PHPCR_PropertyType::STRING)->
			will($this->returnValue($valueObject));

		$property = new F3_TYPO3CR_Property('testproperty', 'testvalue', F3_PHPCR_PropertyType::STRING, $mockNode, $mockSession, $mockValueFactory);
		$this->assertEquals($valueObject, $property->getValue());
	}

	/**
	 * Checks if getValues returns an exception if called with on a single value
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException F3_PHPCR_ValueFormatException
	 */
	public function getValuesReturnsAnExceptionIfCalledOnSingleValue() {
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array(), '', FALSE);
		$mockNode = $this->getMock('F3_TYPO3CR_Node', array(), array(), '', FALSE);

		$mockValueFactory = $this->getMock('F3_PHPCR_ValueFactoryInterface');

		$property = new F3_TYPO3CR_Property('testproperty', 'testvalue', F3_PHPCR_PropertyType::STRING, $mockNode, $mockSession, $mockValueFactory);
		$property->getValues();
	}

	/**
	 * Checks if getPath works as expected
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPathReturnsPathToProperty() {
		$mockStorageBackend = $this->getMock('F3_TYPO3CR_Storage_BackendInterface');
		$mockRepository = $this->getMock('F3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3_TYPO3CR_Session', array(), array('workspaceName', $mockRepository, $mockStorageBackend, $this->componentFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($mockStorageBackend));

		$rawData = array(
			'parent' => 0,
			'name' => '',
			'nodetype' => 'nt:base'
		);
		$rootNode = new F3_TYPO3CR_Node($rawData, $mockSession, $this->componentFactory);
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->will($this->returnValue($rootNode));
		$rawData = array(
			'parent' => $rootNode->getIdentifier(),
			'name' => 'testnode',
			'nodetype' => 'nt:base'
		);
		$node = new F3_TYPO3CR_Node($rawData, $mockSession, $this->componentFactory);
		$node->setProperty('testproperty', 'some test value');

		$testProperty = $node->getProperty('testproperty');
		$propertyPath = $testProperty->getPath();
		$this->assertEquals($propertyPath, '/testnode/testproperty', 'The path ' . $propertyPath . ' was not correct.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeReturnsANode() {
		$this->markTestIncomplete('Not yet implemented');
	}
}
?>
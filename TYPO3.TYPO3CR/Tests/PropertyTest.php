<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

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
 * Tests for the Property implementation of TYPO3CR
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PropertyTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks if getValue returns a Value object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getValueReturnsAValueObject() {
		$valueObject = new \F3\TYPO3CR\Value('somevalue', \F3\PHPCR\PropertyType::STRING);

		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$mockValueFactory->expects($this->once())->
			method('createValue')->
			with('testvalue', \F3\PHPCR\PropertyType::STRING)->
			will($this->returnValue($valueObject));

		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		$mockNode = $this->getMock('F3\TYPO3CR\Node', array(), array(), '', FALSE);

		$property = new \F3\TYPO3CR\Property('testproperty', 'testvalue', \F3\PHPCR\PropertyType::STRING, $mockNode, $mockSession);
		$this->assertEquals($valueObject, $property->getValue());
	}

	/**
	 * Checks if getValues returns an exception if called with on a single value
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @expectedException \F3\PHPCR\ValueFormatException
	 */
	public function getValuesReturnsAnExceptionIfCalledOnSingleValue() {
		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');

		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		$mockNode = $this->getMock('F3\TYPO3CR\Node', array(), array(), '', FALSE);

		$property = new \F3\TYPO3CR\Property('testproperty', 'testvalue', \F3\PHPCR\PropertyType::STRING, $mockNode, $mockSession);
		$property->getValues();
	}

	/**
	 * Checks if getPath works as expected
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @test
	 */
	public function getPathReturnsPathToProperty() {
		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockRepository = $this->getMock('F3\TYPO3CR\Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($mockStorageBackend));

		$rawData = array(
			'parent' => 0,
			'name' => '',
			'nodetype' => 'nt:base'
		);
		$rootNode = new \F3\TYPO3CR\Node($rawData, $mockSession, $this->objectFactory);
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->will($this->returnValue($rootNode));
		$rawData = array(
			'parent' => $rootNode->getIdentifier(),
			'name' => 'testnode',
			'nodetype' => 'nt:base'
		);
		$node = new \F3\TYPO3CR\Node($rawData, $mockSession, $this->objectFactory);
		$node->setProperty('testproperty', 'some test value', \F3\PHPCR\PropertyType::STRING);

		$testProperty = $node->getProperty('testproperty');
		$propertyPath = $testProperty->getPath();
		$this->assertEquals($propertyPath, '/testnode/testproperty', 'The path ' . $propertyPath . ' was not correct.');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeReturnsANode() {
		$mockValueFactory = $this->getMock('F3\PHPCR\ValueFactoryInterface');
		$mockStorageBackend = $this->getMock('F3\TYPO3CR\Storage\BackendInterface');
		$mockRepository = $this->getMock('F3\TYPO3CR\Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('F3\TYPO3CR\Session', array(), array(), '', FALSE);
		$mockSession->expects($this->any())->method('getValueFactory')->will($this->returnValue($mockValueFactory));
		$mockSession->expects($this->any())->method('getStorageBackend')->will($this->returnValue($mockStorageBackend));

		$rootNodeIdentifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		$rawData = array(
			'identifier' => $rootNodeIdentifier,
			'parent' => 0,
			'name' => '',
			'nodetype' => 'nt:base'
		);
		$rootNode = new \F3\TYPO3CR\Node($rawData, $mockSession, $this->objectFactory);

		$referenceNodeIdentifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		$rawData = array(
			'identifier' => $referenceNodeIdentifier,
			'parent' => $rootNode->getIdentifier(),
			'name' => 'testnode',
			'nodetype' => 'nt:base'
		);
		$referenceNode = new \F3\TYPO3CR\Node($rawData, $mockSession, $this->objectFactory);
		$mockSession->expects($this->once())->method('getNodeByIdentifier')->with($referenceNodeIdentifier)->will($this->returnValue($referenceNode));

		$value = new \F3\TYPO3CR\Value($referenceNodeIdentifier, \F3\PHPCR\PropertyType::REFERENCE);
		$mockValueFactory->expects($this->once())->method('createValue')->with($referenceNodeIdentifier, \F3\PHPCR\PropertyType::REFERENCE)->will($this->returnValue($value));

		$nodeIdentifier = \F3\FLOW3\Utility\Algorithms::generateUUID();
		$rawData = array(
			'identifier' => $nodeIdentifier,
			'parent' => $rootNode->getIdentifier(),
			'name' => 'testnode',
			'nodetype' => 'nt:base'
		);
		$node = new \F3\TYPO3CR\Node($rawData, $mockSession, $this->objectFactory);
		$node->setProperty('testproperty', $referenceNode);

		$testProperty = $node->getProperty('testproperty');
		$fetchedNode = $testProperty->getNode();

		$this->assertType('F3\PHPCR\NodeInterface', $fetchedNode, 'getNode() did not return a node.');
		$this->assertEquals($fetchedNode->getIdentifier(), $referenceNodeIdentifier, 'getNode() did not return the expected node.');
	}
}
?>
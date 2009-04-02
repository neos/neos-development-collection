<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Storage\Search;

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
 * Tests for the PDO search backend implementation of TYPO3CR.
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PDOTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3CR\Storage\SearchInterface
	 */
	protected $searchBackend;

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addNodeInsertsDataForSingleValuePropertyInIndexTable() {
		$mockValue = $this->getMock('F3\PHPCR\ValueInterface');
		$mockValue->expects($this->once())->method('getString')->will($this->returnValue('propvaluestring'));
		$mockProperty = $this->getMock('F3\TYPO3CR\Property', array(), array(), '', FALSE);
		$mockProperty->expects($this->once())->method('getName')->will($this->returnValue('flow3:propname'));
		$mockProperty->expects($this->once())->method('isMultiple')->will($this->returnValue(FALSE));
		$mockProperty->expects($this->once())->method('getType')->will($this->returnValue(1));
		$mockProperty->expects($this->once())->method('getValue')->will($this->returnValue($mockValue));
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->once())->method('getIdentifier')->will($this->returnValue('12345'));
		$mockNode->expects($this->once())->method('getProperties')->will($this->returnValue(array($mockProperty)));
		$mockPDOStatement = $this->getMock('PDOStatement');
		$mockPDOStatement->expects($this->once())->method('execute')->with(array('12345', 'propname', 'flow3.org/ns', 1, 'propvaluestring'));
		$mockPDO = $this->getMock('PDO', array(), array(), '', FALSE);
		$mockPDO->expects($this->once())->method('prepare')->with('INSERT INTO "index_properties" ("parent", "name", "namespace", "type", "value") VALUES (?, ?, ?, ?, ?)')->will($this->returnValue($mockPDOStatement));

		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\PDO'), array('splitName'));
		$searchBackend->_set('databaseHandle', $mockPDO);
		$searchBackend->expects($this->once())->method('splitName')->with('flow3:propname')->will($this->returnValue(array('name' => 'propname', 'namespaceURI' => 'flow3.org/ns')));

		$searchBackend->addNode($mockNode);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addNodeInsertsDataForMultiValuePropertyInIndexTable() {
		$mockValue = $this->getMock('F3\PHPCR\ValueInterface');
		$mockValue->expects($this->exactly(2))->method('getString')->will($this->onConsecutiveCalls('propvaluestring', 'secondvaluestring'));
		$mockProperty = $this->getMock('F3\TYPO3CR\Property', array(), array(), '', FALSE);
		$mockProperty->expects($this->once())->method('getName')->will($this->returnValue('flow3:propname'));
		$mockProperty->expects($this->once())->method('isMultiple')->will($this->returnValue(TRUE));
		$mockProperty->expects($this->exactly(2))->method('getType')->will($this->returnValue(1));
		$mockProperty->expects($this->once())->method('getValues')->will($this->returnValue(array($mockValue, $mockValue)));
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->exactly(2))->method('getIdentifier')->will($this->returnValue('12345'));
		$mockNode->expects($this->once())->method('getProperties')->will($this->returnValue(array($mockProperty)));
		$mockPDOStatement = $this->getMock('PDOStatement');
		$mockPDOStatement->expects($this->at(0))->method('execute')->with(array('12345', 'propname', 'flow3.org/ns', 1, 'propvaluestring'));
		$mockPDOStatement->expects($this->at(1))->method('execute')->with(array('12345', 'propname', 'flow3.org/ns', 1, 'secondvaluestring'));
		$mockPDO = $this->getMock('PDO', array(), array(), '', FALSE);
		$mockPDO->expects($this->once())->method('prepare')->with('INSERT INTO "index_properties" ("parent", "name", "namespace", "type", "value") VALUES (?, ?, ?, ?, ?)')->will($this->returnValue($mockPDOStatement));

		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\PDO'), array('splitName'));
		$searchBackend->_set('databaseHandle', $mockPDO);
		$searchBackend->expects($this->once())->method('splitName')->with('flow3:propname')->will($this->returnValue(array('name' => 'propname', 'namespaceURI' => 'flow3.org/ns')));

		$searchBackend->addNode($mockNode);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function deleteNodeProducesExpectedDELETEStatement() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->once())->method('getIdentifier')->will($this->returnValue('12345'));
		$mockPDOStatement = $this->getMock('PDOStatement');
		$mockPDOStatement->expects($this->once())->method('execute')->with(array('12345'));
		$mockPDO = $this->getMock('PDO', array(), array(), '', FALSE);
		$mockPDO->expects($this->once())->method('prepare')->with('DELETE FROM "index_properties" WHERE "parent" = ?')->will($this->returnValue($mockPDOStatement));
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\PDO'), array('dummy'));
		$searchBackend->_set('databaseHandle', $mockPDO);

		$searchBackend->deleteNode($mockNode);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNodeSimplyCallsDeleteNodeAndAddNode() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\PDO'), array('deleteNode', 'addNode'));
		$searchBackend->expects($this->at(0))->method('deleteNode')->with($mockNode);
		$searchBackend->expects($this->at(1))->method('addNode')->with($mockNode);

		$searchBackend->updateNode($mockNode);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseDynamicOperandTreatsPropertyValueCorrectly() {
		$mockPropertyValue = $this->getMock('F3\PHPCR\Query\QOM\PropertyValueInterface');
		$mockPropertyValue->expects($this->once())->method('getPropertyName')->will($this->returnValue('flow3:propname'));
		$mockPropertyValue->expects($this->once())->method('getSelectorName')->will($this->returnValue('_nodes'));
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\PDO'), array('splitName'));
		$searchBackend->expects($this->once())->method('splitName')->with('flow3:propname')->will($this->returnValue(array('name' => 'propname', 'namespaceURI' => 'flow3.org/ns')));

		$parameters = array();
		$sql = array();
		$constraintSQL = $searchBackend->_callRef('parseDynamicOperand', $mockPropertyValue, $sql, $parameters);
		$this->assertEquals(current($sql['where']), '("_nodesproperties0"."name" = ? AND "_nodesproperties0"."namespace" = ? AND "_nodesproperties0"."value" = ?) ');
		$this->assertEquals($parameters, array('propname', 'flow3.org/ns'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseDynamicOperandTreatsLowerCaseOnPropertyValueCorrectly() {
		$mockPropertyValue = $this->getMock('F3\PHPCR\Query\QOM\PropertyValueInterface');
		$mockPropertyValue->expects($this->once())->method('getPropertyName')->will($this->returnValue('flow3:propname'));
		$mockPropertyValue->expects($this->once())->method('getSelectorName')->will($this->returnValue('_nodes'));
		$mockLowerCase = $this->getMock('F3\PHPCR\Query\QOM\LowerCaseInterface');
		$mockLowerCase->expects($this->once())->method('getOperand')->will($this->returnValue($mockPropertyValue));
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\PDO'), array('splitName'));
		$searchBackend->expects($this->once())->method('splitName')->with('flow3:propname')->will($this->returnValue(array('name' => 'propname', 'namespaceURI' => 'flow3.org/ns')));

		$parameters = array();
		$sql = array();
		$constraintSQL = $searchBackend->_callRef('parseDynamicOperand', $mockLowerCase, $sql, $parameters);
		$this->assertEquals(current($sql['where']), '("_nodesproperties0"."name" = ? AND "_nodesproperties0"."namespace" = ? AND LOWER("_nodesproperties0"."value") = ?) ');
		$this->assertEquals($parameters, array('propname', 'flow3.org/ns'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseDynamicOperandTreatsUpperCaseOnPropertyValueCorrectly() {
		$mockPropertyValue = $this->getMock('F3\PHPCR\Query\QOM\PropertyValueInterface');
		$mockPropertyValue->expects($this->once())->method('getPropertyName')->will($this->returnValue('flow3:propname'));
		$mockPropertyValue->expects($this->once())->method('getSelectorName')->will($this->returnValue('_nodes'));
		$mockUpperCase = $this->getMock('F3\PHPCR\Query\QOM\UpperCaseInterface');
		$mockUpperCase->expects($this->once())->method('getOperand')->will($this->returnValue($mockPropertyValue));
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\PDO'), array('splitName'));
		$searchBackend->expects($this->once())->method('splitName')->with('flow3:propname')->will($this->returnValue(array('name' => 'propname', 'namespaceURI' => 'flow3.org/ns')));

		$parameters = array();
		$sql = array();
		$constraintSQL = $searchBackend->_callRef('parseDynamicOperand', $mockUpperCase, $sql, $parameters);
		$this->assertEquals(current($sql['where']), '("_nodesproperties0"."name" = ? AND "_nodesproperties0"."namespace" = ? AND UPPER("_nodesproperties0"."value") = ?) ');
		$this->assertEquals($parameters, array('propname', 'flow3.org/ns'));
	}
}
?>
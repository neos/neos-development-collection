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
 * Tests for the PDO search backend implementation of TYPO3CR.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PdoTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3CR\Storage\SearchInterface
	 */
	protected $searchBackend;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->loadPdoInterface();
	}

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
		$mockPDO = $this->getMock('PdoInterface');
		$mockPDO->expects($this->once())->method('prepare')->with('INSERT INTO "index_properties" ("parent", "name", "namespace", "type", "value") VALUES (?, ?, ?, ?, ?)')->will($this->returnValue($mockPDOStatement));

		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('splitName'));
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
		$mockPDO = $this->getMock('PdoInterface');
		$mockPDO->expects($this->once())->method('prepare')->with('INSERT INTO "index_properties" ("parent", "name", "namespace", "type", "value") VALUES (?, ?, ?, ?, ?)')->will($this->returnValue($mockPDOStatement));

		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('splitName'));
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
		$mockPDO = $this->getMock('PdoInterface');
		$mockPDO->expects($this->once())->method('prepare')->with('DELETE FROM "index_properties" WHERE "parent" = ?')->will($this->returnValue($mockPDOStatement));
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('dummy'));
		$searchBackend->_set('databaseHandle', $mockPDO);

		$searchBackend->deleteNode($mockNode);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNodeSimplyCallsDeleteNodeAndAddNode() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('deleteNode', 'addNode'));
		$searchBackend->expects($this->at(0))->method('deleteNode')->with($mockNode);
		$searchBackend->expects($this->at(1))->method('addNode')->with($mockNode);

		$searchBackend->updateNode($mockNode);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseSourceWorksAsExpectedForSelectorWithoutConstraint() {
		$source = $this->getMock('F3\PHPCR\Query\QOM\SelectorInterface');
		$source->expects($this->once())->method('getSelectorName')->will($this->returnValue('sel'));
		$source->expects($this->once())->method('getNodeTypeName')->will($this->returnValue('nt:base'));
		$query = $this->getMock('F3\TYPO3CR\Query\QOM\QueryObjectModel', array(), array(), '', FALSE);
		$query->expects($this->once())->method('getSource')->will($this->returnValue($source));
		$query->expects($this->once())->method('getConstraint')->will($this->returnValue(NULL));

		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('splitName'));
		$searchBackend->expects($this->once())->method('splitName')->with('nt:base')->will($this->returnValue(array('name' => 'base', 'namespaceURI' => 'jcr.invalid')));
		$searchBackend->expects($this->never())->method('parseConstraint');

		$sql = array();
		$parameters = array();
		$searchBackend->_callRef('parseSource', $query, $sql, $parameters);

		$expectedSql = array(
			'fields' => array('"sel"."identifier" AS "sel"'),
			'tables' => array('"nodes" AS "sel"'),
			'where' => array('("sel"."nodetype"=? AND "sel"."nodetypenamespace"=?)')
		);
		$expectedParameters = array('base', 'jcr.invalid');
		$this->assertEquals($expectedSql, $sql);
		$this->assertEquals($expectedParameters, $parameters);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseSourceWorksAsExpectedForSelectorWithConstraint() {
		$source = $this->getMock('F3\PHPCR\Query\QOM\SelectorInterface');
		$source->expects($this->once())->method('getSelectorName')->will($this->returnValue('sel'));
		$source->expects($this->once())->method('getNodeTypeName')->will($this->returnValue('nt:base'));
		$constraint = $this->getMock('F3\PHPCR\Query\QOM\ConstraintInterface');
		$query = $this->getMock('F3\TYPO3CR\Query\QOM\QueryObjectModel', array(), array(), '', FALSE);
		$query->expects($this->once())->method('getSource')->will($this->returnValue($source));
		$query->expects($this->exactly(2))->method('getConstraint')->will($this->returnValue($constraint));
		$query->expects($this->once())->method('getBoundVariableValues')->will($this->returnValue(array()));

		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('splitName', 'parseConstraint'));
		$searchBackend->expects($this->once())->method('splitName')->with('nt:base')->will($this->returnValue(array('name' => 'base', 'namespaceURI' => 'jcr.invalid')));
		$searchBackend->expects($this->once())->method('parseConstraint'); // ->with();

		$sql = array();
		$parameters = array();
		$searchBackend->_callRef('parseSource', $query, $sql, $parameters);

		$expectedSql = array(
			'fields' => array('"sel"."identifier" AS "sel"'),
			'tables' => array('"nodes" AS "sel" INNER JOIN "index_properties" AS "selproperties" ON "sel"."identifier" = "selproperties"."parent"'),
			'where' => array('("sel"."nodetype"=? AND "sel"."nodetypenamespace"=?) AND ')
		);
		$expectedParameters = array('base', 'jcr.invalid');
		$this->assertEquals($expectedSql, $sql);
		$this->assertEquals($expectedParameters, $parameters);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseSourceCallsParseJoinForJoinWithoutConstraint() {
		$source = $this->getMock('F3\PHPCR\Query\QOM\JoinInterface');
		$query = $this->getMock('F3\TYPO3CR\Query\QOM\QueryObjectModel', array(), array(), '', FALSE);
		$query->expects($this->once())->method('getSource')->will($this->returnValue($source));
		$query->expects($this->once())->method('getConstraint')->will($this->returnValue(NULL));

		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('parseJoin', 'parseConstraint'));
		$searchBackend->expects($this->once())->method('parseJoin')->with($source, array(), array());
		$searchBackend->expects($this->never())->method('parseConstraint');

		$sql = array();
		$parameters = array();
		$searchBackend->_callRef('parseSource', $query, $sql, $parameters);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseSourceCallsParseJoinAndParseConstraintForJoinWithConstraint() {
		$source = $this->getMock('F3\PHPCR\Query\QOM\JoinInterface');
		$constraint = $this->getMock('F3\PHPCR\Query\QOM\ConstraintInterface');
		$query = $this->getMock('F3\TYPO3CR\Query\QOM\QueryObjectModel', array(), array(), '', FALSE);
		$query->expects($this->once())->method('getSource')->will($this->returnValue($source));
		$query->expects($this->exactly(2))->method('getConstraint')->will($this->returnValue($constraint));
		$query->expects($this->once())->method('getBoundVariableValues')->will($this->returnValue(array()));

		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('parseJoin', 'parseConstraint'));
		$searchBackend->expects($this->once())->method('parseJoin')->with($source, array(), array());
		$searchBackend->expects($this->once())->method('parseConstraint')->with($constraint, array('where' => array('AND')), array(), array());

		$sql = array();
		$parameters = array();
		$searchBackend->_callRef('parseSource', $query, $sql, $parameters);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseDynamicOperandTreatsPropertyValueCorrectly() {
		$mockPropertyValue = $this->getMock('F3\PHPCR\Query\QOM\PropertyValueInterface');
		$mockPropertyValue->expects($this->once())->method('getPropertyName')->will($this->returnValue('flow3:propname'));
		$mockPropertyValue->expects($this->once())->method('getSelectorName')->will($this->returnValue('_nodes'));
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('splitName', 'resolveOperator'));
		$searchBackend->expects($this->once())->method('splitName')->with('flow3:propname')->will($this->returnValue(array('name' => 'propname', 'namespaceURI' => 'flow3.org/ns')));
		$searchBackend->expects($this->once())->method('resolveOperator')->with('equals')->will($this->returnValue('='));

		$operator = 'equals';
		$sql = array();
		$parameters = array();
		$searchBackend->_callRef('parseDynamicOperand', $mockPropertyValue, $operator, $sql, $parameters);
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
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('splitName', 'resolveOperator'));
		$searchBackend->expects($this->once())->method('splitName')->with('flow3:propname')->will($this->returnValue(array('name' => 'propname', 'namespaceURI' => 'flow3.org/ns')));
		$searchBackend->expects($this->once())->method('resolveOperator')->with('equals')->will($this->returnValue('='));

		$operator = 'equals';
		$sql = array();
		$parameters = array();
		$searchBackend->_callRef('parseDynamicOperand', $mockLowerCase, $operator, $sql, $parameters);
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
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('splitName', 'resolveOperator'));
		$searchBackend->expects($this->once())->method('splitName')->with('flow3:propname')->will($this->returnValue(array('name' => 'propname', 'namespaceURI' => 'flow3.org/ns')));
		$searchBackend->expects($this->once())->method('resolveOperator')->with('equals')->will($this->returnValue('='));

		$operator = 'equals';
		$sql = array();
		$parameters = array();
		$searchBackend->_callRef('parseDynamicOperand', $mockUpperCase, $operator, $sql, $parameters);
		$this->assertEquals(current($sql['where']), '("_nodesproperties0"."name" = ? AND "_nodesproperties0"."namespace" = ? AND UPPER("_nodesproperties0"."value") = ?) ');
		$this->assertEquals($parameters, array('propname', 'flow3.org/ns'));
	}

	/**
	 * Provides test data for resolveOperatorResolvesCorrectly()
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function jcrOperators() {
		return array(
			array(\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO, '='),
			array(\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN, '<'),
			array(\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO, '<='),
			array(\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN, '>'),
			array(\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO, '>='),
			array(\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE, 'LIKE'),
		);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @dataProvider jcrOperators
	 */
	public function resolveOperatorResolvesCorrectly($jcrConstant, $sqlEquivalent) {
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('dummy'));
		$this->assertEquals($searchBackend->_call('resolveOperator', $jcrConstant), $sqlEquivalent);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function limitIsIgnoredIfNotSet() {
		$mockPDOStatement = $this->getMock('PDOStatement');
		$mockPDO = $this->getMock('PdoInterface');
		$mockPDO->expects($this->once())->method('prepare')->with('SELECT DISTINCT  FROM  WHERE ')->will($this->returnValue($mockPDOStatement));
		$query = $this->getMock('F3\TYPO3CR\Query\QOM\QueryObjectModel', array(), array(), '', NULL);
		$query->expects($this->any())->method('getLimit')->will($this->returnValue(NULL));
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('parseSource'));
		$searchBackend->_set('databaseHandle', $mockPDO);
		$searchBackend->expects($this->once())->method('parseSource');

		$searchBackend->findNodeIdentifiers($query);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function limitAndOffsetAreUsedIfSet() {
		$mockPDOStatement = $this->getMock('PDOStatement');
		$mockPDO = $this->getMock('PdoInterface');
		$mockPDO->expects($this->once())->method('prepare')->with('SELECT DISTINCT  FROM  WHERE  LIMIT 12 OFFSET 2')->will($this->returnValue($mockPDOStatement));
		$query = $this->getMock('F3\TYPO3CR\Query\QOM\QueryObjectModel', array(), array(), '', NULL);
		$query->expects($this->any())->method('getOffset')->will($this->returnValue(2));
		$query->expects($this->any())->method('getLimit')->will($this->returnValue(12));
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('parseSource'));
		$searchBackend->_set('databaseHandle', $mockPDO);
		$searchBackend->expects($this->once())->method('parseSource');

		$searchBackend->findNodeIdentifiers($query);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseSourceCallsParseOrderingsIfNeeded() {
		$source = $this->getMock('F3\PHPCR\Query\QOM\SelectorInterface');
		$source->expects($this->once())->method('getSelectorName')->will($this->returnValue('sel'));
		$source->expects($this->once())->method('getNodeTypeName')->will($this->returnValue('nt:base'));
		$query = $this->getMock('F3\TYPO3CR\Query\QOM\QueryObjectModel', array(), array(), '', FALSE);
		$query->expects($this->any())->method('getOrderings')->will($this->returnValue(array($this->getMock('F3\PHPCR\Query\QOM\OrderingInterface'))));
		$query->expects($this->once())->method('getSource')->will($this->returnValue($source));
		$query->expects($this->once())->method('getConstraint')->will($this->returnValue(NULL));

		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('splitName', 'parseOrderings'));
		$searchBackend->expects($this->once())->method('splitName')->with('nt:base')->will($this->returnValue(array('name' => 'base', 'namespaceURI' => 'jcr.invalid')));
		$searchBackend->expects($this->once())->method('parseOrderings')->will($this->returnValue(array('orderings' => 'foo')));

		$sql = array();
		$parameters = array();
		$searchBackend->_callRef('parseSource', $query, $sql, $parameters);

		$this->assertEquals('foo', $sql['orderings']);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseOrderingsReturnsExpectedResult() {
		$sql = array('orderings' => array());
		$expectedSQL = array(
			'orderings' => array(
				'"_orderingtable0"."foo" ASC', '"_orderingtable1"."bar" DESC'
			),
			'tables' => array(
				'LEFT JOIN (SELECT "parent", "value" AS "foo" FROM "index_properties" WHERE "name" = \'foo\') AS "_orderingtable0" ON "_orderingtable0"."parent" = "_nodeproperties"."parent"',
				'LEFT JOIN (SELECT "parent", "value" AS "bar" FROM "index_properties" WHERE "name" = \'bar\') AS "_orderingtable1" ON "_orderingtable1"."parent" = "_nodeproperties"."parent"'
			)
		);

		$fooOperand = $this->getMock('F3\PHPCR\Query\QOM\PropertyValueInterface');
		$fooOperand->expects($this->any())->method('getPropertyName')->will($this->returnValue('foo'));
		$fooOperand->expects($this->any())->method('getSelectorName')->will($this->returnValue(''));
		$fooOrdering = $this->getMock('F3\PHPCR\Query\QOM\OrderingInterface');
		$fooOrdering->expects($this->any())->method('getOperand')->will($this->returnValue($fooOperand));
		$fooOrdering->expects($this->any())->method('getOrder')->will($this->returnValue(\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING));
		$barOperand = $this->getMock('F3\PHPCR\Query\QOM\PropertyValueInterface');
		$barOperand->expects($this->any())->method('getPropertyName')->will($this->returnValue('bar'));
		$barOperand->expects($this->any())->method('getSelectorName')->will($this->returnValue(''));
		$barOrdering = $this->getMock('F3\PHPCR\Query\QOM\OrderingInterface');
		$barOrdering->expects($this->any())->method('getOperand')->will($this->returnValue($barOperand));
		$barOrdering->expects($this->any())->method('getOrder')->will($this->returnValue(\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING));
		$orderings = array($fooOrdering, $barOrdering);

		$mockPDO = $this->getMock('PdoInterface');
		$mockPDO->expects($this->at(0))->method('quote')->with('foo')->will($this->returnValue('\'foo\''));
		$mockPDO->expects($this->at(1))->method('quote')->with('bar')->will($this->returnValue('\'bar\''));
		$searchBackend = $this->getMock($this->buildAccessibleProxy('F3\TYPO3CR\Storage\Search\Pdo'), array('dummy'));
		$searchBackend->_set('databaseHandle', $mockPDO);
		$sql = $searchBackend->_call('parseOrderings', $orderings, $sql);

		$this->assertEquals($expectedSQL['tables'], $sql['tables']);
		$this->assertEquals($expectedSQL['orderings'], $sql['orderings']);
	}
}

?>
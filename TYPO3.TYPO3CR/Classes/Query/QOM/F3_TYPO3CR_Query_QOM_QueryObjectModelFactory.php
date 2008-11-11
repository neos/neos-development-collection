<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Query::QOM;

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
 * @subpackage Query
 * @version $Id$
 */

/**
 * The Query Object Model Factory
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class QueryObjectModelFactory implements F3::PHPCR::Query::QOM::QueryObjectModelFactoryInterface {

	/**
	 * @var F3::FLOW3::Object::FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var F3::TYPO3CR::Storage::BackendInterface
	 */
	protected $storageBackend;

	/**
	 * Constructs the Component Factory
	 *
	 * @param F3::PHPCR:SessionInterface $session
	 * @param F3::FLOW3::Object::FactoryInterface $objectFactory
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3::PHPCR::SessionInterface $session, F3::FLOW3::Object::FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Creates a query with one or more selectors.
	 * If source is a selector, that selector is the default selector of the query. Otherwise the query does not have a default selector.
	 *
	 * @param mixed $source the Selector or the node-tuple Source; non-null
	 * @param F3::PHPCR::Query::QOM::ConstraintInterface $constraint the constraint, or null if none
	 * @param array $orderings zero or more orderings; null is equivalent to a zero-length array
	 * @param array $columns the columns; null is equivalent to a zero-length array
	 * @return F3::PHPCR::Query::QOM::QueryObjectModelInterface the query; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createQuery(F3::PHPCR::Query::QOM::SourceInterface $selectorOrSource, $constraint, array $orderings, array $columns) {
		$query =  $this->objectFactory->create('F3::PHPCR::Query::QOM::QueryObjectModelInterface', $selectorOrSource, $constraint, $orderings, $columns);
		$query->setSession($this->session);
		return $query;
	}

	/**
	 * Selects a subset of the nodes in the repository based on node type.
	 *
	 * @param string $nodeTypeName the name of the required node type; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::SelectorInterface the selector; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function selector($nodeTypeName, $selectorName = '') {
		return $this->objectFactory->create('F3::PHPCR::Query::QOM::SelectorInterface', $nodeTypeName, $selectorName);
	}

	/**
	 * Performs a join between two node-tuple sources.
	 *
	 * @param F3::PHPCR::Query::QOM::SourceInterface $left the left node-tuple source; non-null
	 * @param F3::PHPCR::Query::QOM::SourceInterface $right the right node-tuple source; non-null
	 * @param integer $joinType either QueryObjectModelConstants.JOIN_TYPE_INNER, QueryObjectModelConstants.JOIN_TYPE_LEFT_OUTER, QueryObjectModelConstants.JOIN_TYPE_RIGHT_OUTER
	 * @param F3::PHPCR::Query::QOM::JoinConditionInterface $join Condition the join condition; non-null
	 * @return F3::PHPCR::Query::QOM::JoinInterface the join; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function join(F3::PHPCR::Query::QOM::SourceInterface $left, F3::PHPCR::Query::QOM::SourceInterface $right, $joinType, F3::PHPCR::Query::QOM::JoinConditionInterface $joinCondition) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058188);
	}

	/**
	 * Tests whether the value of a property in a first selector is equal to the value of a property in a second selector.
	 *
	 * @param string $selector1Name the name of the first selector; non-null
	 * @param string $property1Name the property name in the first selector; non-null
	 * @param string $selector2Name the name of the second selector; non-null
	 * @param string $property2Name the property name in the second selector; non-null
	 * @return F3::PHPCR::Query::QOM::EquiJoinConditionInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058189);
	}

	/**
	 * Tests whether a first selector's node is the same as a node identified by relative path from a second selector's node.
	 *
	 * @param string $selector1Name the name of the first selector; non-null
	 * @param string $selector2Name the name of the second selector; non-null
	 * @param string $selector2Path the path relative to the second selector; non-null
	 * @return F3::PHPCR::Query::QOM::SameNodeJoinConditionInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function sameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058190);
	}

	/**
	 * Tests whether a first selector's node is a child of a second selector's node.
	 *
	 * @param string $childSelectorName the name of the child selector; non-null
	 * @param string $parentSelectorName the name of the parent selector; non-null
	 * @return F3::PHPCR::Query::QOM::ChildNodeJoinConditionInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function childNodeJoinCondition($childSelectorName, $parentSelectorName) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058191);
	}

	/**
	 * Tests whether a first selector's node is a descendant of a second selector's node.
	 *
	 * @param string $descendantSelectorName the name of the descendant selector; non-null
	 * @param string $ancestorSelectorName the name of the ancestor selector; non-null
	 * @return F3::PHPCR::Query::QOM::DescendantNodeJoinConditionInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function descendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058192);
	}

	/**
	 * Performs a logical conjunction of two other constraints.
	 *
	 * @param F3::PHPCR::Query::QOM::ConstraintInterface $constraint1 the first constraint; non-null
	 * @param F3::PHPCR::Query::QOM::ConstraintInterface $constraint2 the second constraint; non-null
	 * @return F3::PHPCR::Query::QOM::AndInterface the And constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function _and(F3::PHPCR::Query::QOM::ConstraintInterface $constraint1, F3::PHPCR::Query::QOM::ConstraintInterface $constraint2) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058193);
	}

	/**
	 * Performs a logical disjunction of two other constraints.
	 *
	 * @param F3::PHPCR::Query::QOM::ConstraintInterface $constraint1 the first constraint; non-null
	 * @param F3::PHPCR::Query::QOM::ConstraintInterface $constraint2 the second constraint; non-null
	 * @return F3::PHPCR::Query::QOM::OrInterface the Or constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function _or(F3::PHPCR::Query::QOM::ConstraintInterface $constraint1, F3::PHPCR::Query::QOM::ConstraintInterface $constraint2) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058194);
	}

	/**
	 * Performs a logical negation of another constraint.
	 *
	 * @param F3::PHPCR::Query::QOM::ConstraintInterface $constraint the constraint to be negated; non-null
	 * @return F3::PHPCR::Query::QOM::NotInterface the Not constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function not(F3::PHPCR::Query::QOM::ConstraintInterface $constraint) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058212);
	}

	/**
	 * Filters node-tuples based on the outcome of a binary operation.
	 *
	 * @param F3::PHPCR::Query::QOM::DynamicOperandInterface $operand1 the first operand; non-null
	 * @param integer $operator the operator; either QueryObjectModelConstants.OPERATOR_EQUAL_TO, QueryObjectModelConstants.OPERATOR_NOT_EQUAL_TO, QueryObjectModelConstants.OPERATOR_LESS_THAN, QueryObjectModelConstants.OPERATOR_LESS_THAN_OR_EQUAL_TO, QueryObjectModelConstants.OPERATOR_GREATER_THAN, QueryObjectModelConstants.OPERATOR_GREATER_THAN_OR_EQUAL_TO, or QueryObjectModelConstants.OPERATOR_LIKE
	 * @param F3::PHPCR::Query::QOM::StaticOperandInterface $operand2 the second operand; non-null
	 * @return F3::PHPCR::Query::QOM::ComparisonInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function comparison(F3::PHPCR::Query::QOM::DynamicOperandInterface $operand1, $operator, F3::PHPCR::Query::QOM::StaticOperandInterface $operand2) {
		return $this->objectFactory->create('F3::PHPCR::Query::QOM::ComparisonInterface', $operand1, $operator, $operand2);
	}

	/**
	 * Tests the existence of a property in the specified or default selector.
	 *
	 * @param string $propertyName the property name; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::PropertyExistenceInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function propertyExistence($propertyName, $selectorName = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058196);
	}

	/**
	 * Performs a full-text search against the specified or default selector.
	 *
	 * @param string $propertyName the property name, or null to search all full-text indexed properties of the node (or node subtree, in some implementations);
	 * @param string $fullTextSearchExpression the full-text search expression; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::FullTextSearchInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function fullTextSearch($propertyName, $fullTextSearchExpression, $selectorName = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058197);
	}

	/**
	 * Tests whether a node in the specified or default selector is reachable by a specified absolute path.
	 *
	 * @param string $selectorName the selector name; non-null
	 * @param string $path an absolute path; non-null
	 * @return F3::PHPCR::Query::QOM::SameNodeInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function sameNode($path, $selectorName = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058198);
	}

	/**
	 * Tests whether a node in the specified or default selector is a child of a node reachable by a specified absolute path.
	 *
	 * @param string $path an absolute path; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::ChildNodeInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function childNode($path, $selectorName = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058199);
	}

	/**
	 * Tests whether a node in the specified or default selector is a descendant of a node reachable by a specified absolute path.
	 *
	 * @param string $path an absolute path; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::DescendantNodeInterface the constraint; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function descendantNode($path, $selectorName = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058200);
	}

	/**
	 * Evaluates to the value (or values, if multi-valued) of a property in the specified or default selector.
	 *
	 * @param string $propertyName the property name; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::PropertyValueInterface the operand; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function propertyValue($propertyName, $selectorName = '') {
		return $this->objectFactory->create('F3::PHPCR::Query::QOM::PropertyValueInterface', $propertyName, $selectorName);
	}

	/**
	 * Evaluates to the length (or lengths, if multi-valued) of a property.
	 *
	 * @param F3::PHPCR::Query::QOM::PropertyValueInterface $propertyValue the property value for which to compute the length; non-null
	 * @return F3::PHPCR::Query::QOM::LengthInterface the operand; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function length(F3::PHPCR::Query::QOM::PropertyValueInterface $propertyValue) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058202);
	}

	/**
	 * Evaluates to a NAME value equal to the prefix-qualified name of a node in the specified or default selector.
	 *
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::NodeNameInterface the operand; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function nodeName($selectorName = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058203);
	}

	/**
	 * Evaluates to a NAME value equal to the local (unprefixed) name of a node in the specified or default selector.
	 *
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::NodeLocalNameInterface the operand; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function nodeLocalName($selectorName = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058204);
	}

	/**
	 * Evaluates to a DOUBLE value equal to the full-text search score of a node in the specified or default selector.
	 *
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::FullTextSearchScoreInterface the operand; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function fullTextSearchScore($selectorName = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058205);
	}

	/**
	 * Evaluates to the lower-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param F3::PHPCR::Query::QOM::DynamicOperandInterface $operand the operand whose value is converted to a lower-case string; non-null
	 * @return F3::PHPCR::Query::QOM::LowerCaseInterface the operand; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function lowerCase(F3::PHPCR::Query::QOM::DynamicOperandInterface $operand) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058206);
	}

	/**
	 * Evaluates to the upper-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param F3::PHPCR::Query::QOM::DynamicOperandInterface $operand the operand whose value is converted to a upper-case string; non-null
	 * @return F3::PHPCR::Query::QOM::UpperCaseInterface the operand; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function upperCase(F3::PHPCR::Query::QOM::DynamicOperandInterface $operand) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058207);
	}

	/**
	 * Evaluates to the value of a bind variable.
	 *
	 * @param string $bindVariableName the bind variable name; non-null
	 * @return F3::PHPCR::Query::QOM::BindVariableValueInterface the operand; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function bindVariable($bindVariableName) {
		return $this->objectFactory->create('F3::PHPCR::Query::QOM::BindVariableValueInterface', $bindVariableName);
	}

	/**
	 * Evaluates to a literal value.
	 *
	 * The query is invalid if no value is bound to $literalValue.
	 *
	 * @param F3::PHPCR::ValueInterface $literalValue the value
	 * @return F3::PHPCR::ValueInterface the operand; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if a particular validity test is possible on this method, the implemention chooses to perform that test (and not leave it until later) on createQuery, and the parameters given fail that test
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function literal(F3::PHPCR::ValueInterface $literalValue) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224520629);
	}

	/**
	 * Orders by the value of the specified operand, in ascending order.
	 *
	 * The query is invalid if $operand does not evaluate to a scalar value.
	 *
	 * @param F3::PHPCR::Query::QOM::DynamicOperandInterface $operand the operand by which to order; non-null
	 * @return F3::PHPCR::Query::QOM::OrderingInterface the ordering
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function ascending(F3::PHPCR::Query::QOM::DynamicOperandInterface $operand) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058209);
	}

	/**
	 * Orders by the value of the specified operand, in descending order.
	 *
	 * The query is invalid if $operand does not evaluate to a scalar value.
	 *
	 * @param F3::PHPCR::Query::QOM::DynamicOperandInterface $operand the operand by which to order; non-null
	 * @return F3::PHPCR::Query::QOM::OrderingInterface the ordering
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query is invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function descending(F3::PHPCR::Query::QOM::DynamicOperandInterface $operand) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058210);
	}

	/**
	 * Identifies a property in the specified or default selector to include in
	 * the tabular view of query results.
	 * The column name is the property name if not given.
	 *
	 * The query is invalid if:
	 * $selectorName is not the name of a selector in the query, or
	 * $propertyName is specified but it is not a syntactically valid JCR name, or
	 * $propertyName is specified but does not evaluate to a scalar value, or
	 * $propertyName is specified but $columnName is omitted, or
	 * $propertyName is omitted but $columnName is specified, or
	 * the columns in the tabular view are not uniquely named, whether those
	 * column names are specified by $columnName (if $propertyName is specified)
	 * or generated as described above (if $propertyName is omitted).
	 *
	 * If $propertyName is specified but, for a node-tuple, the selector node
	 * does not have a property named $propertyName, the query is valid and the
	 * column has null value.
	 *
	 * @param string $propertyName the property name, or null to include a column for each single-value non-residual property of the selector's node type
	 * @param string $columnName the column name; must be null if propertyName is null
	 * @param string $selectorName the selector name; non-null
	 * @return F3::PHPCR::Query::QOM::ColumnInterface the column; non-null
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query has no default selector or is otherwise invalid
	 * @throws F3::PHPCR::RepositoryException if the operation otherwise fails
	 */
	public function column($propertyName, $columnName = NULL, $selectorName = NULL) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1217058211);
	}

}
?>
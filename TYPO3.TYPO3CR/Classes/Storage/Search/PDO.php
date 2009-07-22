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
 * A storage indexing/search backend using PDO and SQL
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class PDO extends \F3\TYPO3CR\Storage\AbstractSearch {

	/**
	 * @var \PDO
	 */
	protected $databaseHandle;

	/**
	 * @var string
	 */
	protected $PDODriver;

	/**
	 * @var string
	 */
	protected $dataSourceName;

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * Sets the DSN to use
	 *
	 * @param string $DSN The DSN to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setDataSourceName($DSN) {
		$this->dataSourceName = $DSN;
	}

	/**
	 * Sets the username to use
	 *
	 * @param string $username The username to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * Sets the password to use
	 *
	 * @param $password The password to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * Connect to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function connect() {
		try {
			$splitdsn = explode(':', $this->dataSourceName, 2);
			$this->PDODriver = $splitdsn[0];

			if ($this->PDODriver === 'sqlite') {
				if (!file_exists($splitdsn[1])) {
					throw new \F3\TYPO3CR\StorageException('The configured SQLite database file (' . $splitdsn[1] . ') does not exist.', 1236677428); //'
				}
			}

			$this->databaseHandle = new \PDO($this->dataSourceName, $this->username, $this->password);
			$this->databaseHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			if ($this->PDODriver === 'mysql') {
				$this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI\';');
			}
		} catch (\PDOException $e) {
			throw new \F3\TYPO3CR\StorageException('Could not connect to DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage(), 1236677438); //'
		}
	}

	/**
	 * Adds the given node to the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 */
	public function addNode(\F3\PHPCR\NodeInterface $node) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "index_properties" ("parent", "name", "namespace", "type", "value") VALUES (?, ?, ?, ?, ?)');

		foreach ($node->getProperties() as $property) {
			$splitPropertyName = $this->splitName($property->getName());
			if ($property->isMultiple()) {
				foreach ($property->getValues() as $value) {
					$statementHandle->execute(array(
						$node->getIdentifier(),
						$splitPropertyName['name'],
						$splitPropertyName['namespaceURI'],
						$property->getType(),
						$value->getString()
					));
				}
			} else {
				$statementHandle->execute(array(
					$node->getIdentifier(),
					$splitPropertyName['name'],
					$splitPropertyName['namespaceURI'],
					$property->getType(),
					$property->getValue()->getString()
				));
			}
		}
	}

	/**
	 * Updates the given node in the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 */
	public function updateNode(\F3\PHPCR\NodeInterface $node) {
		$this->deleteNode($node);
		$this->addNode($node);
	}

	/**
	 * Deletes the given node from the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 */
	public function deleteNode(\F3\PHPCR\NodeInterface $node) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "index_properties" WHERE "parent" = ?');
		$statementHandle->execute(array($node->getIdentifier()));
	}

	/**
	 * Returns an array with node identifiers matching the query. The array
	 * will be like this:
	 * array(
	 *  array('selectorA' => '12345', 'selectorB' => '67890')
	 *  array('selectorA' => '54321', 'selectorB' => '09876')
	 * )
	 *
	 * @param \F3\PHPCR\Query\QOM\QueryObjectModelInterface $query
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findNodeIdentifiers(\F3\PHPCR\Query\QOM\QueryObjectModelInterface $query) {
		$sql = array('fields' => array(), 'tables' => array(), 'where' => array(), 'orderings' => array());
		$parameters = array();

		$this->parseSource($query, $sql, $parameters);

		$sqlString = 'SELECT DISTINCT ' . implode(', ', $sql['fields']) . ' FROM ' . implode(' ', $sql['tables']);
		$sqlString .= ' WHERE ' . implode(' ', $sql['where']);
		if (count($sql['orderings'])) {
			$sqlString .= 'ORDER BY ' . implode(', ', $sql['orderings']);
		}
		if ($query->getLimit() !== NULL) {
			$sqlString .= ' LIMIT ' . $query->getLimit() . ' OFFSET '. $query->getOffset();
		}

		$statementHandle = $this->databaseHandle->prepare($sqlString);
		$statementHandle->execute($parameters);
		$result = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * Transforms a Query Source into SQL and parameter arrays
	 *
	 * @param \F3\TYPO3CR\Query\QOM\QueryObjectModel $query
	 * @param array &$sql
	 * @param array &$parameters
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseSource(\F3\TYPO3CR\Query\QOM\QueryObjectModel $query, array &$sql, array &$parameters) {
		$source = $query->getSource();
		if ($source instanceof \F3\PHPCR\Query\QOM\SelectorInterface) {
			$selectorName = $source->getSelectorName();
			$splitNodeTypeName = $this->splitName($source->getNodeTypeName());
			$parameters[] = $splitNodeTypeName['name'];
			$parameters[] = $splitNodeTypeName['namespaceURI'];
			$sql['fields'][] = '"' . $selectorName . '"."identifier" AS "' . $selectorName . '"';
			if ($query->getConstraint() === NULL) {
				$sql['tables'][] = '"nodes" AS "' . $selectorName . '"';
				$sql['where'][] = '("' . $selectorName . '"."nodetype"=? AND "' . $selectorName . '"."nodetypenamespace"=?)';
			} else {
				$sql['tables'][] = '"nodes" AS "' . $selectorName . '" INNER JOIN "index_properties" AS "' . $selectorName . 'properties" ON "' . $selectorName . '"."identifier" = "' . $selectorName . 'properties"."parent"';
				$sql['where'][] = '("' . $selectorName . '"."nodetype"=? AND "' . $selectorName . '"."nodetypenamespace"=?) AND ';
				$this->parseConstraint($query->getConstraint(), $sql, $parameters, $query->getBoundVariableValues());
			}
			if ($query->getOrderings() !== NULL) {
				$sql['orderings'] = $this->parseOrderings($query->getOrderings());
			}
		} elseif ($source instanceof \F3\PHPCR\Query\QOM\JoinInterface) {
			$this->parseJoin($source, $sql, $parameters);
			if ($query->getConstraint() !== NULL) {
				$sql['where'][] = 'AND';
				$this->parseConstraint($query->getConstraint(), $sql, $parameters, $query->getBoundVariableValues());
			}
		}
	}

	/**
	 * Transforms an array with Orderings into SQL-like order parts
	 *
	 * @param array $orderings
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseOrderings(array $orderings) {
		$sqlifiedOrderings = array();
		foreach ($orderings as $ordering) {
			if ($ordering->getOperand() instanceof \F3\PHPCR\Query\QOM\PropertyValueInterface) {
				switch ($ordering->getOrder()) {
					case \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING:
						$order = 'ASC';
						break;
					case \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING:
						$order = 'DESC';
						break;
					default:
						throw new \F3\PHPCR\RepositoryException('Illegal order requested.', 1248264221);
				}

				$sqlifiedOrderings[] = $ordering->getOperand()->getPropertyName() . ' ' . $order;
			}
		}
		return $sqlifiedOrderings;
	}

	/**
	 * Transforms a Join into SQL and parameter arrays
	 *
	 * @param \F3\PHPCR\Query\QOM\JoinInterface $join
	 * @param array &$sql
	 * @param array &$parameters
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseJoin(\F3\PHPCR\Query\QOM\JoinInterface $join, array &$sql, array &$parameters) {
		$leftSelectorName = $join->getLeft()->getSelectorName();
		$splitLeftNodeTypeName = $this->splitName($join->getLeft()->getNodeTypeName());
		$rightSelectorName = $join->getRight()->getSelectorName();
		$splitRightNodeTypeName = $this->splitName($join->getRight()->getNodeTypeName());

		$sql['fields'][] = '"' . $leftSelectorName . '"."identifier"';
		$sql['tables'][] = '"nodes" AS "' . $leftSelectorName . '" INNER JOIN "nodes" AS "' . $rightSelectorName . '" ON';
		if ($join->getJoinCondition() instanceof \F3\PHPCR\Query\QOM\ChildNodeJoinConditionInterface) {
			$sql['tables'][] = '"' . $leftSelectorName . '"."identifier" = "' . $rightSelectorName . '"."parent"';
		}
		$sql['tables'][] = 'INNER JOIN "index_properties" AS "' . $leftSelectorName . 'properties" ON "' . $leftSelectorName . '"."identifier" = "' . $leftSelectorName . 'properties"."parent"';
		$sql['tables'][] = 'INNER JOIN "index_properties" AS "' . $rightSelectorName . 'properties" ON "' . $rightSelectorName . '"."identifier" = "' . $rightSelectorName . 'properties"."parent"';

		$sql['where'][] = '("' . $leftSelectorName . '"."nodetype"=? AND "' . $leftSelectorName . '"."nodetypenamespace"=? AND "' . $rightSelectorName . '"."nodetype"=? AND "' . $rightSelectorName . '"."nodetypenamespace"=?)';
		$parameters[] = $splitLeftNodeTypeName['name'];
		$parameters[] = $splitLeftNodeTypeName['namespaceURI'];
		$parameters[] = $splitRightNodeTypeName['name'];
		$parameters[] = $splitRightNodeTypeName['namespaceURI'];
	}

	/**
	 * Transforms a constraint into SQL and parameter arrays
	 *
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint
	 * @param array &$sql
	 * @param array &$parameters
	 * @param array $boundVariableValues
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseConstraint(\F3\PHPCR\Query\QOM\ConstraintInterface $constraint = NULL, array &$sql, array &$parameters, array $boundVariableValues) {
		if ($constraint instanceof \F3\PHPCR\Query\QOM\AndInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ' AND ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\PHPCR\Query\QOM\OrInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ' OR ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\PHPCR\Query\QOM\NotInterface) {
			$sql['where'][] = '(NOT ';
			$this->parseConstraint($constraint->getConstraint(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\PHPCR\Query\QOM\ComparisonInterface) {
			$this->parseComparison($constraint, $sql, $parameters, $boundVariableValues);
		}
	}

	/**
	 * Parse a Comparison into SQL and parameter arrays.
	 *
	 * @param \F3\PHPCR\Query\QOM\ComparisonInterface $comparison The comparison to parse
	 * @param array &$sql SQL query parts to add to
	 * @param array &$parameters Parameters to bind to the SQL
	 * @param array $boundVariableValues The bound variables in the query and their values
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseComparison(\F3\PHPCR\Query\QOM\ComparisonInterface $comparison, array &$sql, array &$parameters, array $boundVariableValues) {
		$this->parseDynamicOperand($comparison->getOperand1(), $comparison->getOperator(), $sql, $parameters);

		if ($comparison->getOperand2() instanceof \F3\PHPCR\Query\QOM\BindVariableValueInterface) {
			$parameters[] = $boundVariableValues[$comparison->getOperand2()->getBindVariableName()];
		} elseif ($comparison->getOperand2() instanceof \F3\PHPCR\Query\QOM\LiteralInterface) {
			$parameters[] = $comparison->getOperand2()->getLiteralValue();
		}
	}

	/**
	 * Parse a DynamicOperand into SQL and parameter arrays.
	 *
	 * @param \F3\PHPCR\Query\QOM\DynamicOperandInterface $operand
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @param array $boundVariableValues
	 * @param array &$parameters
	 * @param string $valueFunction an aoptional SQL function to apply to the operand value
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseDynamicOperand(\F3\PHPCR\Query\QOM\DynamicOperandInterface $operand, $operator, array &$sql, array &$parameters, $valueFunction = NULL) {
		if ($operand instanceof \F3\PHPCR\Query\QOM\LowerCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'LOWER');
		} elseif ($operand instanceof \F3\PHPCR\Query\QOM\UpperCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'UPPER');
		} elseif ($operand instanceof \F3\PHPCR\Query\QOM\PropertyValueInterface) {
			$selectorName = $operand->getSelectorName();
			$operator = $this->resolveOperator($operator);

			$constraintSQL = '("' . $selectorName . 'properties' . count($parameters) . '"."name" = ? AND "' . $selectorName . 'properties' . count($parameters) . '"."namespace" = ? AND ';
			if ($valueFunction === NULL) {
				$constraintSQL .= '"' . $selectorName . 'properties' . count($parameters) . '"."value" ' . $operator . ' ?';
			} else {
				$constraintSQL .= '' . $valueFunction . '("' . $selectorName . 'properties' . count($parameters) . '"."value") ' . $operator . ' ?';
			}
			$constraintSQL .= ') ';

			$sql['where'][] = $constraintSQL;
			$sql['tables'][] = 'INNER JOIN "index_properties" AS "' . $selectorName . 'properties' . count($parameters) . '" ON "' . $selectorName . '"."identifier" = "' . $selectorName . 'properties' . count($parameters) . '"."parent"';
			$splitPropertyName = $this->splitName($operand->getPropertyName());
			$parameters[] = $splitPropertyName['name'];
			$parameters[] = $splitPropertyName['namespaceURI'];
		}
	}

	/**
	 * Returns the SQL operator for the given JCR operator type.
	 *
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @return string an SQL operator
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function resolveOperator($operator) {
		switch ($operator) {
			case \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO:
				$operator = '=';
				break;
			case \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_NOT_EQUAL_TO:
				$operator = '!=';
				break;
			case \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN:
				$operator = '<';
				break;
			case \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO:
				$operator = '<=';
				break;
			case \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN:
				$operator = '>';
				break;
			case \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO:
				$operator = '>=';
				break;
			case \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE:
				$operator = 'LIKE';
				break;
			default:
				throw new \F3\PHPCR\RepositoryException('Unsupported operator encountered.', 1242816073);
		}

		return $operator;
	}

}

?>

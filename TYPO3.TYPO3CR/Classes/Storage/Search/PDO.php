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
 * @subpackage Storage
 * @version $Id$
 */

/**
 * A storage indexing/search backend using PDO and SQL
 *
 * @package TYPO3CR
 * @subpackage Storage
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
	 * is expected to be like this:
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
		$sql = array();
		$parameters = array();

		$this->parseSource($query, $sql, $parameters);

		$sqlString = 'SELECT DISTINCT ' . implode(',', $sql['fields']) . ' FROM ' . implode(' ', $sql['tables']);
		$sqlString .= ' WHERE ' . implode(' ', $sql['where']);

		$statementHandle = $this->databaseHandle->prepare($sqlString);
		$statementHandle->execute($parameters);
		$result = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * Given a selector name this returns a string usable as an alias in SQL.
	 *
	 * @param string $name The selector name
	 * @return string The alias for use in SQL
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getAliasFromSelectorName($name) {
		return $name;
	}

	protected function parseSource(\F3\PHPCR\Query\QueryInterface $query, array &$sql, array &$parameters) {
		$source = $query->getSource();
		if ($source instanceof \F3\PHPCR\Query\QOM\SelectorInterface && $query->getConstraint() === NULL) {
			$selectorName = $source->getSelectorName();
			$selectorAlias = $this->getAliasFromSelectorName($selectorName);
			$splitNodeTypeName = $this->splitName($source->getNodeTypeName());
			$parameters[] = $splitNodeTypeName['name'];
			$parameters[] = $splitNodeTypeName['namespaceURI'];
			$sql['fields'][] = '"' . $selectorAlias . '"."identifier" AS "' . $selectorAlias . '"';
			$sql['tables'][] = '"nodes" AS "' . $selectorAlias . '"';
			$sql['where'][] = '("' . $selectorAlias . '"."nodetype"=? AND "' . $selectorAlias . '"."nodetypenamespace"=?)';
		} elseif ($source instanceof \F3\PHPCR\Query\QOM\SelectorInterface) {
			$selectorName = $source->getSelectorName();
			$selectorAlias = $this->getAliasFromSelectorName($selectorName);
			$splitNodeTypeName = $this->splitName($source->getNodeTypeName());
			$parameters[] = $splitNodeTypeName['name'];
			$parameters[] = $splitNodeTypeName['namespaceURI'];
			$sql['fields'][] = '"' . $selectorAlias . '"."identifier" AS "' . $selectorAlias . '"';
			$sql['tables'][] = '"nodes" AS "' . $selectorAlias . '" INNER JOIN "index_properties" AS "' . $selectorAlias . 'properties" ON "' . $selectorAlias . '"."identifier" = "' . $selectorAlias . 'properties"."parent"';
			$sql['where'][] = '("' . $selectorAlias . '"."nodetype"=? AND "' . $selectorAlias . '"."nodetypenamespace"=?) AND ';
			$this->parseConstraint($query->getConstraint(), $query->getBoundVariableValues(), $parameters, $sql);
		} elseif ($source instanceof \F3\PHPCR\Query\QOM\JoinInterface) {
			$this->parseJoin($source, $sql, $parameters);
			if ($query->getConstraint() !== NULL) {
				$sql['where'][] = 'AND';
				$this->parseConstraint($query->getConstraint(), $query->getBoundVariableValues(), $parameters, $sql);
			}
		}
	}

	/**
	 * @return string SQL representing the join.
	 */
	protected function parseJoin(\F3\PHPCR\Query\QOM\JoinInterface $join, array &$sql, array &$parameters) {
		$leftSelectorName = $join->getLeft()->getSelectorName();
		$leftSelectorAlias = $this->getAliasFromSelectorName($leftSelectorName);
		$splitLeftNodeTypeName = $this->splitName($join->getLeft()->getNodeTypeName());
		$rightSelectorName = $join->getRight()->getSelectorName();
		$rightSelectorAlias = $this->getAliasFromSelectorName($rightSelectorName);
		$splitRightNodeTypeName = $this->splitName($join->getRight()->getNodeTypeName());

		$sql['fields'][] = '"' . $leftSelectorAlias . '"."identifier"';
		$sql['tables'][] = '"nodes" AS "' . $leftSelectorAlias . '" INNER JOIN "nodes" AS "' . $rightSelectorAlias . '" ON';
		if ($join->getJoinCondition() instanceof \F3\PHPCR\Query\QOM\ChildNodeJoinConditionInterface) {
			$sql['tables'][] = '"' . $leftSelectorAlias . '"."identifier" = "' . $rightSelectorAlias . '"."parent"';
		}
		$sql['tables'][] = 'INNER JOIN "index_properties" AS "' . $leftSelectorAlias . 'properties" ON "' . $leftSelectorAlias . '"."identifier" = "' . $leftSelectorAlias . 'properties"."parent"';
		$sql['tables'][] = 'INNER JOIN "index_properties" AS "' . $rightSelectorAlias . 'properties" ON "' . $rightSelectorAlias . '"."identifier" = "' . $rightSelectorAlias . 'properties"."parent"';

		$sql['where'][] = '("' . $leftSelectorAlias . '"."nodetype"=? AND "' . $leftSelectorAlias . '"."nodetypenamespace"=? AND "' . $rightSelectorAlias . '"."nodetype"=? AND "' . $rightSelectorAlias . '"."nodetypenamespace"=?)';
		$parameters[] = $splitLeftNodeTypeName['name'];
		$parameters[] = $splitLeftNodeTypeName['namespaceURI'];
		$parameters[] = $splitRightNodeTypeName['name'];
		$parameters[] = $splitRightNodeTypeName['namespaceURI'];
	}

	/**
	 * Transforms a constraint into SQL
	 *
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint
	 * @param \Zend_Search_Lucene_Search_Query_Boolean $luceneQuery
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseConstraint(\F3\PHPCR\Query\QOM\ConstraintInterface $constraint = NULL, array $boundVariableValues, array &$parameters, array &$sql) {
		if ($constraint instanceof \F3\PHPCR\Query\QOM\AndInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $boundVariableValues, $parameters, $sql);
			$sql['where'][] = ' AND ';
			$this->parseConstraint($constraint->getConstraint2(), $boundVariableValues, $parameters, $sql);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\PHPCR\Query\QOM\OrInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $boundVariableValues, $parameters, $sql);
			$sql['where'][] = ' OR ';
			$this->parseConstraint($constraint->getConstraint2(), $boundVariableValues, $parameters, $sql);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\PHPCR\Query\QOM\NotInterface) {
			$sql['where'][] = '(NOT ';
			$this->parseConstraint($constraint->getConstraint(), $boundVariableValues, $parameters, $sql);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof \F3\PHPCR\Query\QOM\ComparisonInterface) {
			$this->parseComparison($constraint, $sql, $parameters, $boundVariableValues);
		}
	}

	/**
	 *
	 */
	protected function parseComparison(\F3\PHPCR\Query\QOM\ComparisonInterface $comparison, array &$sql, array &$parameters, array $boundVariableValues) {
		$this->parseDynamicOperand($comparison->getOperand1(), $sql, $parameters);

		if ($comparison->getOperand2() instanceof \F3\PHPCR\Query\QOM\BindVariableValueInterface) {
			$parameters[] = $boundVariableValues[$comparison->getOperand2()->getBindVariableName()];
		} elseif ($comparison->getOperand2() instanceof \F3\PHPCR\Query\QOM\LiteralInterface) {
			$parameters[] = $comparison->getOperand2()->getLiteralValue();
		}
	}

	/**
	 *
	 * @param \F3\PHPCR\Query\QOM\DynamicOperandInterface $operand
	 * @param array $boundVariableValues
	 * @param array &$parameters
	 * @param string $valueFunction an aoptional SQL function to apply to the operand value
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseDynamicOperand(\F3\PHPCR\Query\QOM\DynamicOperandInterface $operand, array &$sql, array &$parameters, $valueFunction = NULL) {
		if ($operand instanceof \F3\PHPCR\Query\QOM\LowerCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $sql, $parameters, 'LOWER');
		} elseif ($operand instanceof \F3\PHPCR\Query\QOM\UpperCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $sql, $parameters, 'UPPER');
		} elseif ($operand instanceof \F3\PHPCR\Query\QOM\PropertyValueInterface) {
			$selectorName = $operand->getSelectorName();
			$selectorAlias = $this->getAliasFromSelectorName($selectorName);
			$constraintSQL = '(';
			$operator = '=';
			if ($valueFunction === NULL) {
				$constraintSQL .= '"' . $selectorAlias . 'properties' . count($parameters) . '"."name" = ? AND "' . $selectorAlias . 'properties' . count($parameters) . '"."namespace" = ? AND "' . $selectorAlias . 'properties' . count($parameters) . '"."value" ' . $operator . ' ?';
			} else {
				$constraintSQL .= '"' . $selectorAlias . 'properties' . count($parameters) . '"."name" = ? AND "' . $selectorAlias . 'properties' . count($parameters) . '"."namespace" = ? AND ' . $valueFunction . '("' . $selectorAlias . 'properties' . count($parameters) . '"."value") ' . $operator . ' ?';
			}
			$constraintSQL .= ') ';
			$sql['where'][] = $constraintSQL;
			$sql['tables'][] = 'INNER JOIN "index_properties" AS "' . $selectorAlias . 'properties' . count($parameters) . '" ON "' . $selectorAlias . '"."identifier" = "' . $selectorAlias . 'properties' . count($parameters) . '"."parent"';
			$splitPropertyName = $this->splitName($operand->getPropertyName());
			$parameters[] = $splitPropertyName['name'];
			$parameters[] = $splitPropertyName['namespaceURI'];
		}
	}

	/**
	 * Splits the given name string into a namespace URI (using the namespaces table) and a name
	 *
	 * @param string $prefixedName the name in prefixed notation (':' between prefix if one exists and name, no ':' in string if there is no prefix)
	 * @return array (key "namespaceURI" for the namespace, "name" for the name)
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function splitName($prefixedName) {
		$split = explode(':', $prefixedName, 2);

		if (count($split) != 2) {
			return array('namespaceURI' => '', 'name' => $prefixedName);
		}

		$namespacePrefix = $split[0];
		$name = $split[1];

		return array('namespaceURI' => $this->namespaceRegistry->getURI($namespacePrefix), 'name' => $name);
	}


	/**
	 * Takes the given array of a namespace URI (key 'namespaceURI' in the array) and name (key 'name') and converts it to a prefixed name
	 *
	 * @param array $namespacedName key 'namespaceURI' for the namespace, 'name' for the local name
	 * @return string
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function prefixName($namespacedName) {
		if (! $namespacedName['namespaceURI']) {
			return $namespacedName['name'];
		}

		if ($this->namespaceRegistry) {
			return $this->namespaceRegistry->getPrefix($namespacedName['namespaceURI']) . ':' . $namespacedName['name'];
		} else {
				// Fall back to namespaces table when no namespace registry is available
			$statementHandle = $this->databaseHandle->prepare('SELECT "prefix" FROM "namespaces" WHERE "uri"=?');
			$statementHandle->execute(array($namespacedName['namespaceURI']));
			$namespaces = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);

			if (count($namespaces) != 1) {
					// TODO: throw exception instead of returning once namespace table is properly filled
				return $namespacedName['name'];
			}

			foreach ($namespaces as $namespace) {
				return $namespace['prefix'] . ':' . $namespacedName['name'];
			}
		}
	}
}

?>

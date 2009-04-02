<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\FLOW3\Persistence;

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
 * @subpackage FLOW3
 * @version $Id$
 */

/**
 * The Query classs used to run queries against the storage backend
 *
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Query implements \F3\FLOW3\Persistence\QueryInterface {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\TYPO3CR\FLOW3\Persistence\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \F3\PHPCR\SessionInterface
	 */
	protected $session;

	/**
	 * @var \F3\PHPCR\Query\QueryObjectModelFactoryInterface
	 */
	protected $QOMFactory;

	/**
	 * @var \F3\PHPCR\ValueFactoryInterface
	 */
	protected $valueFactory;

	/**
	 * @var \F3\FLOW3\Persistence\ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\PHPCR\Query\QOM\SourceInterface
	 */
	protected $source;

	/**
	 * @var \F3\PHPCR\Query\QOM\ConstraintInterface
	 */
	protected $constraint;

	/**
	 * an array of named variables and their values from the operators
	 * @var array
	 */
	protected $operands = array();

	/**
	 * Constructs a query object working on the given class name
	 *
	 * @param string $className
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($className) {
		$this->className = $className;
	}

	/**
	 * Injects the FLOW3 object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param \F3\TYPO3CR\FLOW3\Persistence\DataMapper $dataMapper
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectDataMapper(\F3\TYPO3CR\FLOW3\Persistence\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the persistence manager, used to fetch the CR session
	 *
	 * @param \F3\FLOW3\Persistence\ManagerInterface $persistenceManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
		$session = $this->persistenceManager->getBackend()->getSession();
		$this->QOMFactory = $session->getWorkspace()->getQueryManager()->getQOMFactory();
		$this->valueFactory = $session->getValueFactory();
	}

	/**
	 * Executes the query against TYPO3CR and returns the result
	 *
	 * @return \F3\PHPCR\Query\QueryResultInterface The query result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function execute() {
		if ($this->source === NULL) {
			$this->source = $this->QOMFactory->selector('flow3:' . str_replace('\\', '_', $this->className), '_node');
		}

		$query = $this->QOMFactory->createQuery(
			$this->source,
			$this->constraint,
			array(),
			array()
		);
		foreach ($this->operands as $name => $value) {
			$query->bindValue($name, $this->valueFactory->createValue($value));
		}
		$result = $query->execute();

		return $this->dataMapper->map($result->getNodes());
	}

	/**
	 * The constraint used to limit the result set
	 *
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 */
	public function matching($constraint) {
		$this->constraint = $constraint;
		return $this;
	}

	/**
	 * Performs a logical conjunction of the two given constraints
	 *
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint1
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint2
	 * @return \F3\PHPCR\Query\QOM\AndInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function logicalAnd($constraint1, $constraint2) {
		return $this->QOMFactory->_and(
			$constraint1,
			$constraint2
		);
	}

	/**
	 * Performs a logical disjunction of the two given constraints
	 *
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint1
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint2
	 * @return \F3\PHPCR\Query\QOM\AndInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function logicalOr($constraint1, $constraint2) {
		return $this->QOMFactory->_or(
			$constraint1,
			$constraint2
		);
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint
	 * @return \F3\PHPCR\Query\QOM\AndInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function logicalNot($constraint) {
		return $this->QOMFactory->not($constraint);
	}

	/**
	 * Matches against the internal identifier.
	 *
	 * @param string $uuid A UUID
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 */
	public function withUUID($uuid) {
		$this->operands['jcr:uuid'] = $uuid;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue('jcr:uuid', '_node'),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_EQUAL_TO,
			$this->QOMFactory->bindVariable('jcr:uuid', '_node')
		);
	}

	/**
	 * Adds an equality criterion used for matching objects against the query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		if (is_object($operand) && !($operand instanceof \DateTime)) {
			$operand = $this->persistenceManager->getBackend()->getUUIDByObject($operand);
				// we look for nodes with (done)
				// - a subnode named flow3:$propertyName being NODETYPE_OBJECTPROXY
				// - that subnode having a property flow3:target
				// - that has a value of $operand
				// OR (open, needed?)
				// - a subnode named flow3:$propertyName
				// - that subnode having a property jcr:uuid
				// - that has a value of $operand
			$left = $this->QOMFactory->selector('flow3:' . str_replace('\\', '_', $this->className), '_node');
			$right = $this->QOMFactory->selector('flow3:objectProxy', '_proxy');
			$joinCondition = $this->QOMFactory->childNodeJoinCondition('_proxy', '_node');

			$this->source = $this->QOMFactory->join(
				$left,
				$right,
				\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JOIN_TYPE_INNER,
				$joinCondition
			);

			$comparison = $this->QOMFactory->comparison(
				$this->QOMFactory->propertyValue('flow3:target', '_proxy'),
				\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_EQUAL_TO,
				$this->QOMFactory->bindVariable('flow3:target')
			);

			$this->operands['flow3:target'] = $operand;
		} else {
			if ($caseSensitive) {
				$comparison = $this->QOMFactory->comparison(
					$this->QOMFactory->propertyValue('flow3:' . $propertyName, '_node'),
					\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_EQUAL_TO,
					$this->QOMFactory->bindVariable('flow3:' . $propertyName)
				);
			} else {
				$comparison = $this->QOMFactory->comparison(
					$this->QOMFactory->lowerCase(
						$this->QOMFactory->propertyValue('flow3:' . $propertyName, '_node')
					),
					\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_EQUAL_TO,
					$this->QOMFactory->bindVariable('flow3:' . $propertyName)
				);
			}

			if ($caseSensitive) {
				$this->operands['flow3:' . $propertyName] = $operand;
			} else {
				$this->operands['flow3:' . $propertyName] = strtolower($operand);
			}
		}

		return $comparison;
	}

	/**
	 * Adds a like criterion used for matching objects against the query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function like($propertyName, $operand) {
		$this->operands['flow3:' . $propertyName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue('flow3:' . $propertyName, '_node'),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_LIKE,
			$this->QOMFactory->bindVariable('flow3:' . $propertyName)
		);
	}

	/**
	 * Adds a "less than" criterion used for matching objects against the query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lessThan($propertyName, $operand) {
		$this->operands['flow3:' . $propertyName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue('flow3:' . $propertyName, '_node'),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_LESS_THAN,
			$this->QOMFactory->bindVariable('flow3:' . $propertyName)
		);
	}

	/**
	 * Adds a "less than or equal" criterion used for matching objects against the query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lessThanOrEqual($propertyName, $operand) {
		$this->operands['flow3:' . $propertyName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue('flow3:' . $propertyName, '_node'),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable('flow3:' . $propertyName)
		);
	}

	/**
	 * Adds a "greater than" criterion used for matching objects against the query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function greaterThan($propertyName, $operand) {
		$this->operands['flow3:' . $propertyName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue('flow3:' . $propertyName, '_node'),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_GREATER_THAN,
			$this->QOMFactory->bindVariable('flow3:' . $propertyName)
		);
	}

	/**
	 * Adds a "greater than or equal" criterion used for matching objects against the query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		$this->operands['flow3:' . $propertyName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue('flow3:' . $propertyName, '_node'),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable('flow3:' . $propertyName)
		);
	}

}
?>
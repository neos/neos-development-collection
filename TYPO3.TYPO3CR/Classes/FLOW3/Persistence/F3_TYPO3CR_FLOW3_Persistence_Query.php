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
	 * @param \F3\FLOW3\Persistence\Manager $persistenceManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\Manager $persistenceManager) {
		$session = $persistenceManager->getBackend()->getSession();
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
		$query = $this->QOMFactory->createQuery($this->QOMFactory->selector('flow3:' . str_replace('\\', '_', $this->className)), $this->constraint, array(), array());
		foreach ($this->operands as $name => $value) {
			$valueObject = $this->valueFactory->createValue($value);
			$query->bindValue($name, $valueObject);
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
	 * Adds an equality criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function equals($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_EQUAL_TO,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a like criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function like($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_LIKE,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "less than" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lessThan($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_LESS_THAN,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "less than or equal" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lessThanOrEqual($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "greater than" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function greaterThan($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_GREATER_THAN,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "greater than or equal" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\PHPCR\Query\QOM\ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function greaterThanOrEqual($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			\F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable($property)
		);
	}

}
?>
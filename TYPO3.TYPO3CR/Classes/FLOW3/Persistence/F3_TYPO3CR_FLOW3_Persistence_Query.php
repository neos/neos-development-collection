<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::FLOW3::Persistence;

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
 * @subpackage FLOW3
 * @version $Id$
 */

/**
 * The Query classs used to run queries against the storage backend
 *
 * @package TYPO3CR
 * @subpackage FLOW3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Query implements F3::FLOW3::Persistence::QueryInterface {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var F3::FLOW3::Component::FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * @var F3::TYPO3CR::FLOW3::Persistence::DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var F3::PHPCR::SessionInterface
	 */
	protected $session;

	/**
	 * @var F3::PHPCR::Query::QueryObjectModelFactoryInterface
	 */
	protected $QOMFactory;

	/**
	 * @var F3::PHPCR::ValueFactoryInterface
	 */
	protected $valueFactory;

	/**
	 * @var F3::PHPCR::Query::QOM::ConstraintInterface
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
	 * Injects the FLOW3 component factory
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectComponentFactory(F3::FLOW3::Component::FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param F3::TYPO3CR::FLOW3::Persistence::DataMapper $dataMapper
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectDataMapper(F3::TYPO3CR::FLOW3::Persistence::DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the Content Repository used to persist data
	 *
	 * @param F3::PHPCR::RepositoryInterface $repository
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectContentRepository(F3::PHPCR::RepositoryInterface $repository) {
		$this->session = $repository->login();
		$this->QOMFactory = $this->session->getWorkspace()->getQueryManager()->getQOMFactory();
		$this->valueFactory = $this->session->getValueFactory();
	}

	/**
	 * Executes the query against TYPO3CR and returns the result
	 *
	 * @return F3::PHPCR::Query::QueryResultInterface The query result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function execute() {
		$query = $this->QOMFactory->createQuery($this->QOMFactory->selector('flow3:' . $this->className), $this->constraint, array(), array());
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
	 * @param F3::PHPCR::Query::QOM::ConstraintInterface $constraint
	 * @return F3::FLOW3::Persistence::QueryInterface
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
	 * @return F3::PHPCR::Query::QOM::ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function equals($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3::PHPCR::Query::QOM::QueryObjectModelConstantsInterface::OPERATOR_EQUAL_TO,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a like criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::PHPCR::Query::QOM::ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function like($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3::PHPCR::Query::QOM::QueryObjectModelConstantsInterface::OPERATOR_LIKE,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "less than" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::PHPCR::Query::QOM::ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lessThan($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3::PHPCR::Query::QOM::QueryObjectModelConstantsInterface::OPERATOR_LESS_THAN,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "less than or equal" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::PHPCR::Query::QOM::ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lessThanOrEqual($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3::PHPCR::Query::QOM::QueryObjectModelConstantsInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "greater than" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::PHPCR::Query::QOM::ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function greaterThan($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3::PHPCR::Query::QOM::QueryObjectModelConstantsInterface::OPERATOR_GREATER_THAN,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "greater than or equal" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::PHPCR::Query::QOM::ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function greaterThanOrEqual($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3::PHPCR::Query::QOM::QueryObjectModelConstantsInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable($property)
		);
	}

}
?>
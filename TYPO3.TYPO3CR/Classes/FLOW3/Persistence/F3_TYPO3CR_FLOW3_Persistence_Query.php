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
class F3_TYPO3CR_FLOW3_Persistence_Query implements F3_FLOW3_Persistence_QueryInterface {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * @var F3_TYPO3CR_FLOW3_Persistence_DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var F3_PHPCR_SessionInterface
	 */
	protected $session;

	/**
	 * @var F3_PHPCR_Query_QueryObjectModelFactoryInterface
	 */
	protected $QOMFactory;

	/**
	 * @var F3_PHPCR_ValueFactoryInterface
	 */
	protected $valueFactory;

	/**
	 * @var F3_PHPCR_Query_QOM_ConstraintInterface
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
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @required
	 */
	public function injectComponentFactory(F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param F3_TYPO3CR_FLOW3_Persistence_DataMapper $dataMapper
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectDataMapper(F3_TYPO3CR_FLOW3_Persistence_DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the Content Repository used to persist data
	 *
	 * @param F3_PHPCR_RepositoryInterface $repository
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @required
	 */
	public function injectContentRepository(F3_PHPCR_RepositoryInterface $repository) {
		$this->session = $repository->login();
		$this->QOMFactory = $this->session->getWorkspace()->getQueryManager()->getQOMFactory();
		$this->valueFactory = $this->session->getValueFactory();
	}

	/**
	 * Executes the query against TYPO3CR and returns the result
	 *
	 * @return F3_PHPCR_Query_QueryResultInterface The query result
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
	 * @param F3_PHPCR_Query_QOM_ConstraintInterface $constraint
	 * @return F3_FLOW3_Persistence_QueryInterface
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
	 * @return F3_PHPCR_Query_QOM_ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function equals($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3_PHPCR_Query_QOM_QueryObjectModelConstantsInterface::OPERATOR_EQUAL_TO,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a like criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3_PHPCR_Query_QOM_ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function like($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3_PHPCR_Query_QOM_QueryObjectModelConstantsInterface::OPERATOR_LIKE,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "less than" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3_PHPCR_Query_QOM_ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lessThan($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3_PHPCR_Query_QOM_QueryObjectModelConstantsInterface::OPERATOR_LESS_THAN,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "less than or equal" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3_PHPCR_Query_QOM_ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lessThanOrEqual($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3_PHPCR_Query_QOM_QueryObjectModelConstantsInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "greater than" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3_PHPCR_Query_QOM_ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function greaterThan($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3_PHPCR_Query_QOM_QueryObjectModelConstantsInterface::OPERATOR_GREATER_THAN,
			$this->QOMFactory->bindVariable($property)
		);
	}

	/**
	 * Adds a "greater than or equal" criterion used for matching objects against the query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3_PHPCR_Query_QOM_ComparisonInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function greaterThanOrEqual($property, $operand) {
		$this->operands[$property] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($property),
			F3_PHPCR_Query_QOM_QueryObjectModelConstantsInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable($property)
		);
	}

}
?>
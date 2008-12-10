<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query\QOM;

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
 * The JSR-283 QOM Comparison class
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Comparison implements \F3\PHPCR\Query\QOM\ComparisonInterface {

	/**
	 * @var \F3\PHPCR\Query\QOM\DynamicOperandInterface
	 */
	protected $operand1;

	/**
	 * @var integer
	 */
	protected $operator;

	/**
	 * @var \F3\PHPCR\Query\QOM\StaticOperandInterface
	 */
	protected $operand2;

	/**
	 * Constructs this Comparison instance
	 *
	 * @param \F3\PHPCR\Query\QOM\DynamicOperandInterface $operand1
	 * @param unknown_type $operator
	 * @param \F3\PHPCR\Query\QOM\StaticOperandInterface $operand2
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\PHPCR\Query\QOM\DynamicOperandInterface $operand1, $operator, \F3\PHPCR\Query\QOM\StaticOperandInterface $operand2) {
		$this->operand1 = $operand1;
		$this->operator = $operator;
		$this->operand2 = $operand2;
	}

	/**
	 * Fills an array with the names of all bound variables in the operand
	 *
	 * @param array &$boundVariableNames
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function collectBoundVariableNames(&$boundVariableNames) {
		$this->operand2->collectBoundVariablenames($boundVariableNames);
	}

	/**
	 *
	 * Gets the first operand.
	 *
	 * @return \F3\PHPCR\Query\QOM\DynamicOperandInterface the operand; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getOperand1() {
		return $this->operand1;
	}

	/**
	 * Gets the operator.
	 *
	 * @return integer one of \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface.OPERATOR_*
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getOperator() {
		return $this->operator;
	}

	/**
	 * Gets the second operand.
	 *
	 * @return \F3\PHPCR\Query\QOM\StaticOperandInterface the operand; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getOperand2() {
		return $this->operand2;
	}

}

?>
<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query\QOM;

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
 * @subpackage Query
 * @version $Id$
 */

/**
 * The JSR-283 QOM Comparison class
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * @param array &$boundVariables
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function collectBoundVariableNames(&$boundVariables) {
		$this->operand2->collectBoundVariablenames($boundVariables);
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
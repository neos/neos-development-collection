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
 * Performs a logical disjunction of two other constraints.
 *
 * To satisfy the Or constraint, the node-tuple must either:
 *  satisfy constraint1 but not constraint2, or
 *  satisfy constraint2 but not constraint1, or
 *  satisfy both constraint1 and constraint2.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class LogicalOr implements \F3\PHPCR\Query\QOM\OrInterface {

	/**
	 * @var \F3\PHPCR\Query\QOM\ConstraintInterface
	 */
	protected $constraint1;

	/**
	 * @var \F3\PHPCR\Query\QOM\ConstraintInterface
	 */
	protected $constraint2;

	/**
	 *
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint1
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint2
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\PHPCR\Query\QOM\ConstraintInterface $constraint1, \F3\PHPCR\Query\QOM\ConstraintInterface $constraint2) {
		$this->constraint1 = $constraint1;
		$this->constraint2 = $constraint2;
	}

	/**
	 * Fills an array with the names of all bound variables in the constraints
	 *
	 * @param array &$boundVariables
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function collectBoundVariableNames(&$boundVariables) {
		$this->constraint1->collectBoundVariablenames($boundVariables);
		$this->constraint2->collectBoundVariablenames($boundVariables);
	}

	/**
	 * Gets the first constraint.
	 *
	 * @return \F3\PHPCR\Query\QOM\ConstraintInterface the constraint; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getConstraint1() {
		return $this->constraint1;
	}

	/**
	 * Gets the second constraint.
	 *
	 * @return \F3\PHPCR\Query\QOM\ConstraintInterface the constraint; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getConstraint2() {
		return $this->constraint2;
	}

}
?>
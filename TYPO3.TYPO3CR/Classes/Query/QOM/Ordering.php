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
 * An operand to a binary operation specified by a Comparison.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Ordering implements \F3\PHPCR\Query\QOM\OrderingInterface {

	/**
	 * Construct an Ordering instance.
	 *
	 * @param \F3\PHPCR\Query\QOM\DynamicOperandInterface $operand
	 * @param string $order either QueryObjectModelConstants.JCR_ORDER_ASCENDING or QueryObjectModelConstants.JCR_ORDER_DESCENDING
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\PHPCR\Query\QOM\DynamicOperandInterface $operand, $order) {
		if ($order !== \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING
			&& $order !== \F3\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING) {
				throw new \F3\PHPCR\RepositoryException('Illegal order requested.', 1248260222);
			}
		$this->operand = $operand;
		$this->order = $order;
	}

	/**
	 * The operand by which to order.
	 *
	 * @return \F3\PHPCR\Query\QOM\DynamicOperandInterface the operand; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getOperand() {
		return $this->operand;
	}

	/**
	 * Gets the order.
	 *
	 * @return string either QueryObjectModelConstants.JCR_ORDER_ASCENDING or QueryObjectModelConstants.JCR_ORDER_DESCENDING
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getOrder() {
		return $this->order;
	}

}

?>
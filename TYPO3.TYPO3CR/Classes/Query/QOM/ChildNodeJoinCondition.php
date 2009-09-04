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
 * Tests whether the childSelector node is a child of the parentSelector node. A
 * node-tuple satisfies the constraint only if:
 *  childSelectorNode.getParent().isSame(parentSelectorNode)
 * would return true, where childSelectorNode is the node for childSelector and
 * parentSelectorNode is the node for parentSelector.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ChildNodeJoinCondition implements \F3\PHPCR\Query\QOM\ChildNodeJoinConditionInterface {

	/**
	 * @var string
	 */
	protected $childSelectorName;

	/**
	 * @var string
	 */
	protected $parentSelectorName;

	/**
	 * Constructs this ChildNodeJoinCondition instance
	 *
	 * @param \F3\PHPCR\Query\QOM\DynamicOperandInterface $operand1
	 * @param \F3\PHPCR\Query\QOM\StaticOperandInterface $operand2
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($childSelectorName, $parentSelectorName) {
		$this->childSelectorName = $childSelectorName;
		$this->parentSelectorName = $parentSelectorName;
	}

	/**
	 * Gets the name of the child selector.
	 *
	 * @return string the selector name; non-null
	 * @api
	 */
	public function getChildSelectorName() {
		return $this->childSelectorName;
	}

	/**
	 * Gets the name of the parent selector.
	 *
	 * @return string the selector name; non-null
	 * @api
	 */
	public function getParentSelectorName() {
		return $this->parentSelectorName;
	}


}

?>
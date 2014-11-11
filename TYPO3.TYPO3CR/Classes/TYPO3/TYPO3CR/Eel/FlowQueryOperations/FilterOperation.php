<?php
namespace TYPO3\TYPO3CR\Eel\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * This filter implementation contains specific behavior for use on TYPO3CR
 * nodes. It will not evaluate any elements that are not instances of the
 * `NodeInterface`.
 *
 * The implementation changes the behavior of the `instanceof` operator to
 * work on node types instead of PHP object types, so that::
 *
 * 	[instanceof TYPO3.Neos.NodeTypes:Page]
 *
 * will in fact use `isOfType()` on the `NodeType` of context elements to
 * filter. This filter allow also to filter the current context by a given
 * node. Anything else remains unchanged.
 */
class FilterOperation extends \TYPO3\Eel\FlowQuery\Operations\Object\FilterOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var integer
	 */
	static protected $priority = 100;

	/**
	 * {@inheritdoc}
	 *
	 * @param array (or array-like object) $context onto which this operation should be applied
	 * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
	 */
	public function canEvaluate($context) {
		return (isset($context[0]) && ($context[0] instanceof NodeInterface));
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery
	 * @param array $arguments
	 * @return void
	 */
	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		if (!isset($arguments[0]) || empty($arguments[0])) {
			return;
		}

		if ($arguments[0] instanceof NodeInterface) {
			$filteredContext = array();
			$context = $flowQuery->getContext();
			foreach ($context as $element) {
				if ($element === $arguments[0]) {
					$filteredContext[] = $element;
					break;
				}
			}
			$flowQuery->setContext($filteredContext);
		} else {
			parent::evaluate($flowQuery, $arguments);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param object $element
	 * @param string $propertyNameFilter
	 * @return boolean TRUE if the property name filter matches
	 */
	protected function matchesPropertyNameFilter($element, $propertyNameFilter) {
		return ($element->getName() === $propertyNameFilter);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param object $element
	 * @param string $identifier
	 * @return boolean
	 */
	protected function matchesIdentifierFilter($element, $identifier) {
		return (strtolower($element->getIdentifier()) === strtolower($identifier));
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param object $element
	 * @param string $propertyPath
	 * @return mixed
	 */
	protected function getPropertyPath($element, $propertyPath) {
		if ($propertyPath[0] === '_') {
			return \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($element, substr($propertyPath, 1));
		} else {
			return $element->getProperty($propertyPath);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $value
	 * @param string $operator
	 * @param mixed $operand
	 * @return boolean
	 */
	protected function evaluateOperator($value, $operator, $operand) {
		if ($operator === 'instanceof' && $value instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) {
			if ($this->operandIsSimpleType($operand)) {
				return $this->handleSimpleTypeOperand($operand, $value);
			} elseif ($operand === 'TYPO3\TYPO3CR\Domain\Model\NodeInterface' || $operand === 'TYPO3\TYPO3CR\Domain\Model\Node') {
				return TRUE;
			} else {
				$isOfType = $value->getNodeType()->isOfType($operand);
				return $operand[0] === '!' ? $isOfType === FALSE : $isOfType;
			}
		}
		return parent::evaluateOperator($value, $operator, $operand);
	}
}
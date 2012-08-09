<?php
namespace TYPO3\TYPO3\TypoScript\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Filter nodes
 */
class FilterOperation extends \TYPO3\Eel\FlowQuery\Operations\Object\FilterOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var integer
	 */
	static protected $priority = 100;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 * @FLOW3\Inject
	 */
	protected $contentTypeManager;

	/**
	 * {@inheritdoc}
	 *
	 * @param array (or array-like object) $context onto which this operation should be applied
	 * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
	 */
	public function canEvaluate($context) {
		return (isset($context[0]) && ($context[0] instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface));
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
	 * @param string $propertyPath
	 * @return mixed
	 */
	protected function getPropertyPath($element, $propertyPath) {
		if ($propertyPath[0] === '_') {
			return \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($element, substr($propertyPath, 1));
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
		if ($operator === 'instanceof') {
			if ($this->operandIsSimpleType($operand)) {
				return $this->handleSimpleTypeOperand($operand, $value);
			} elseif ($operand === 'TYPO3\TYPO3CR\Domain\Model\NodeInterface') {
				return TRUE;
			} else {
				return $this->contentTypeManager->getContentType($value->getContentType())->isOfType($operand);
			}
		} else {
			return parent::evaluateOperator($value, $operator, $operand);
		}
	}
}
?>
<?php
namespace TYPO3\TYPO3\TypoScript\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Access properties of a TYPO3CR Node. If prefixed with _, internal properties
 * like start time, end time, hidden are accessed.
 */
class PropertyOperation extends \TYPO3\Eel\FlowQuery\Operations\AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'property';

	/**
	 * {@inheritdoc}
	 *
	 * @var integer
	 */
	static protected $priority = 100;

	/**
	 * {@inheritdoc}
	 *
	 * @var boolean
	 */
	static protected $final = TRUE;

	/**
	 * {@inheritdoc}
	 *
	 * We can only handle TYPO3CR Nodes.
	 *
	 * @param mixed $context
	 * @return boolean
	 */
	public function canEvaluate($context) {
		return (isset($context[0]) && ($context[0] instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface));
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return mixed|null if the operation is final, the return value
	 */
	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		if (!isset($arguments[0]) || empty($arguments[0])) {
			throw new \TYPO3\Eel\FlowQuery\FlowQueryException('property() does not support returning all attributes yet', 1332492263);
		} else {
			$context = $flowQuery->getContext();
			$propertyPath = $arguments[0];

			if (!isset($context[0])) {
				return NULL;
			}

			$element = $context[0];
			if ($propertyPath[0] === '_') {
				return \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($element, substr($propertyPath, 1));
			} else {
				return $element->getProperty($propertyPath);
			}
		}
	}
}
?>
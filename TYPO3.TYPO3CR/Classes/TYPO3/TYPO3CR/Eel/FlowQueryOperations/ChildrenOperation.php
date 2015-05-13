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

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * "children" operation working on TYPO3CR nodes. It iterates over all
 * context elements and returns all child nodes or only those matching
 * the filter expression specified as optional argument.
 */
class ChildrenOperation extends AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'children';

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
		return isset($context[0]) && ($context[0] instanceof NodeInterface);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return void
	 */
	public function evaluate(FlowQuery $flowQuery, array $arguments) {
		$output = array();
		$outputNodePaths = array();
		if (isset($arguments[0]) && !empty($arguments[0])) {
			$parsedFilter = \TYPO3\Eel\FlowQuery\FizzleParser::parseFilterGroup($arguments[0]);
			if ($this->earlyOptimizationOfFilters($flowQuery, $parsedFilter)) {
				return;
			}
		}

		/** @var NodeInterface $contextNode */
		foreach ($flowQuery->getContext() as $contextNode) {
			/** @var NodeInterface $childNode */
			foreach ($contextNode->getChildNodes() as $childNode) {
				if (!isset($outputNodePaths[$childNode->getPath()])) {
					$output[] = $childNode;
					$outputNodePaths[$childNode->getPath()] = TRUE;
				}
			}
		}
		$flowQuery->setContext($output);

		if (isset($arguments[0]) && !empty($arguments[0])) {
			$flowQuery->pushOperation('filter', $arguments);
		}
	}

	/**
	 * Optimize for typical use cases, filter by node name and filter
	 * by NodeType (instanceof). These cases are now optimized and will
	 * only load the Nodes that match the filter.
	 *
	 * @param FlowQuery $flowQuery
	 * @param array $parsedFilter
	 * @return boolean
	 */
	protected function earlyOptimizationOfFilters(FlowQuery $flowQuery, array $parsedFilter) {
		$filter = $parsedFilter['Filters'][0];

		if (isset($filter['PropertyNameFilter'])) {
			if (isset($filter['AttributeFilters'])) {
				foreach ($filter['AttributeFilters'] as $attributeFilter) {
					$flowQuery->pushOperation('filter', array($attributeFilter['text']));
				}
			}
			$flowQuery->pushOperation('find', array($parsedFilter['Filters'][0]['PropertyNameFilter']));
			return TRUE;
		}

		if (isset($filter['AttributeFilters']) && $filter['AttributeFilters'][0]['Operator'] === 'instanceof' && $filter['AttributeFilters'][0]['Identifier'] === NULL) {
			$output = array();
			$outputNodePaths = array();
			/** @var NodeInterface $contextNode */
			foreach ($flowQuery->getContext() as $contextNode) {
				/** @var NodeInterface $childNode */
				foreach ($contextNode->getChildNodes($filter['AttributeFilters'][0]['Operand']) as $childNode) {
					if (!isset($outputNodePaths[$childNode->getPath()])) {
						$output[] = $childNode;
						$outputNodePaths[$childNode->getPath()] = TRUE;
					}
				}
			}
			$flowQuery->setContext($output);

			if (count($filter['AttributeFilters']) > 1) {
				array_shift($filter['AttributeFilters']);
				foreach ($filter['AttributeFilters'] as $attributeFilter) {
					$flowQuery->pushOperation('filter', array($attributeFilter['text']));
				}
			}
			return TRUE;
		}

		return FALSE;
	}
}

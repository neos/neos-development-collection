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
	 * only load the nodes that match the filters.
	 *
	 * @param FlowQuery $flowQuery
	 * @param array $parsedFilter
	 * @return boolean
	 */
	protected function earlyOptimizationOfFilters(FlowQuery $flowQuery, array $parsedFilter) {
		$optimized = FALSE;
		$output = array();
		$outputNodePaths = array();
		foreach ($parsedFilter['Filters'] as $filter) {
			$instanceOfFilters = array();
			$attributeFilters = array();
			if (isset($filter['AttributeFilters'])) {
				foreach ($filter['AttributeFilters'] as $attributeFilter) {
					if ($attributeFilter['Operator'] === 'instanceof' && $attributeFilter['Identifier'] === NULL) {
						$instanceOfFilters[] = $attributeFilter;
					} else {
						$attributeFilters[] = $attributeFilter;
					}
				}
			}

			// Only apply optimization if there's a property name filter or a instanceof filter or another filter already did optimization
			if ((isset($filter['PropertyNameFilter']) || isset($filter['PathFilter'])) || count($instanceOfFilters) > 0 || $optimized === TRUE) {
				$optimized = TRUE;
				$filteredOutput = array();
				$filteredOutputNodePaths = array();
				// Optimize property name filter if present
				if (isset($filter['PropertyNameFilter']) || isset($filter['PathFilter'])) {
					$nodePath = isset($filter['PropertyNameFilter']) ? $filter['PropertyNameFilter'] : $filter['PathFilter'];
					/** @var NodeInterface $contextNode */
					foreach ($flowQuery->getContext() as $contextNode) {
						$childNode = $contextNode->getNode($nodePath);
						if ($childNode !== NULL && !isset($filteredOutputNodePaths[$childNode->getPath()])) {
							$filteredOutput[] = $childNode;
							$filteredOutputNodePaths[$childNode->getPath()] = TRUE;
						}
					}
				} elseif (count($instanceOfFilters) > 0) {
					// Optimize node type filter if present
					$allowedNodeTypes = array_map(function($instanceOfFilter) {
						return $instanceOfFilter['Operand'];
					}, $instanceOfFilters);
					/** @var NodeInterface $contextNode */
					foreach ($flowQuery->getContext() as $contextNode) {
						/** @var NodeInterface $childNode */
						foreach ($contextNode->getChildNodes(implode($allowedNodeTypes, ',')) as $childNode) {
							if (!isset($filteredOutputNodePaths[$childNode->getPath()])) {
								$filteredOutput[] = $childNode;
								$filteredOutputNodePaths[$childNode->getPath()] = TRUE;
							}
						}
					}
				}

				// Apply attribute filters if present
				if (isset($filter['AttributeFilters'])) {
					$attributeFilters = array_reduce($filter['AttributeFilters'], function($filters, $attributeFilter) {
						return $filters . $attributeFilter['text'];
					});
					$filteredFlowQuery = new FlowQuery($filteredOutput);
					$filteredFlowQuery->pushOperation('filter', array($attributeFilters));
					$filteredOutput = $filteredFlowQuery->get();
				}

				// Add filtered nodes to output
				foreach ($filteredOutput as $filteredNode) {
					if (!isset($outputNodePaths[$filteredNode->getPath()])) {
						$output[] = $filteredNode;
					}
				}
			}
		}

		if ($optimized === TRUE) {
			$flowQuery->setContext($output);
		}

		return $optimized;
	}

}
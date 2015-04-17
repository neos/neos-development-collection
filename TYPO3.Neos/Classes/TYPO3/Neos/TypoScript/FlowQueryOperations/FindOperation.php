<?php
namespace TYPO3\Neos\TypoScript\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
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
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * "find" operation working on TYPO3CR nodes. This operation allows for retrieval
 * of nodes specified by a path. The current context node is also used as a context
 * for evaluating relative paths.
 */
class FindOperation extends AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'find';

	/**
	 * {@inheritdoc}
	 *
	 * @var integer
	 */
	static protected $priority = 100;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * {@inheritdoc}
	 *
	 * @param array (or array-like object) $context onto which this operation should be applied
	 * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
	 */
	public function canEvaluate($context) {
		if (count($context) === 0) {
			return TRUE;
		}

		foreach ($context as $contextNode) {
			if (!$contextNode instanceof NodeInterface) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return void
	 */
	public function evaluate(FlowQuery $flowQuery, array $arguments) {
		$context = $flowQuery->getContext();
		if (!isset($context[0]) || empty($arguments[0])) {
			return;
		}

		$result = array();
		$selectorAndFilter = $arguments[0];

		try {
			$parsedFilter = \TYPO3\Eel\FlowQuery\FizzleParser::parseFilterGroup($selectorAndFilter);
		} catch (\Exception $e) {}

		if ($selectorAndFilter[0] === '#') {
			if (!preg_match(\TYPO3\Flow\Validation\Validator\UuidValidator::PATTERN_MATCH_UUID, substr($selectorAndFilter, 1))) {
				throw new \TYPO3\Eel\FlowQuery\FlowQueryException('find() requires a valid identifier', 1332492263);
			}
			/** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $contextNode */
			foreach ($context as $contextNode) {
				array_push($result, $contextNode->getContext()->getNodeByIdentifier(substr($selectorAndFilter, 1)));
			}
		} elseif (isset($parsedFilter['Filters'][0]['AttributeFilters']) && $parsedFilter['Filters'][0]['AttributeFilters'][0]['Operator'] === 'instanceof') {
			$nodeTypes = array();
			foreach ($parsedFilter['Filters'] as $filter) {
				if (isset($filter['AttributeFilters']) && $filter['AttributeFilters'][0]['Operator'] === 'instanceof') {
					$nodeTypes[] = $filter['AttributeFilters'][0]['Operand'];
				}
			}
			/** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $contextNode */
			foreach ($context as $contextNode) {
				$result = array_merge($result, $this->nodeDataRepository->findByParentAndNodeTypeInContext($contextNode->getPath(), implode(',', $nodeTypes), $contextNode->getContext(), TRUE));
			}
		} else {
			/** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $contextNode */
			foreach ($context as $contextNode) {
				$node = $contextNode->getNode($selectorAndFilter);
				if ($node !== NULL) {
					array_push($result, $node);
				}
			}
		}

		$flowQuery->setContext(array_unique($result));
	}
}

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
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * "find" operation working on TYPO3CR nodes. This operation allows for retrieval
 * of nodes specified by a path, identifier or node type (recursive).
 *
 * Example (node name):
 *
 * 	q(node).find('main')
 *
 * Example (relative path):
 *
 * 	q(node).find('main/text1')
 *
 * Example (absolute path):
 *
 * 	q(node).find('/sites/my-site/home')
 *
 * Example (identifier):
 *
 * 	q(node).find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')
 *
 * Example (node type):
 *
 * 	q(node).find('[instanceof TYPO3.Neos.NodeTypes:Text]')
 *
 * Example (multiple node types):
 *
 * 	q(node).find('[instanceof TYPO3.Neos.NodeTypes:Text],[instanceof TYPO3.Neos.NodeTypes:Image]')
 *
 * Example (node type with filter):
 *
 * 	q(node).find('[instanceof TYPO3.Neos.NodeTypes:Text][text*="Neos"]')
 *
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

		$parsedFilter = NULL;
		$parsedFilter = \TYPO3\Eel\FlowQuery\FizzleParser::parseFilterGroup($selectorAndFilter);
		if (isset($parsedFilter['Filters']) && $this->hasOnlyInstanceOfFilters($parsedFilter['Filters'])) {
			$nodeTypes = array();
			foreach ($parsedFilter['Filters'] as $filter) {
				$nodeTypes[] = $filter['AttributeFilters'][0]['Operand'];
			}
			/** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $contextNode */
			foreach ($context as $contextNode) {
				$result = array_merge($result, $this->nodeDataRepository->findByParentAndNodeTypeInContext($contextNode->getPath(), implode(',', $nodeTypes), $contextNode->getContext(), TRUE));
			}
		} else {
			foreach ($parsedFilter['Filters'] as $filter) {
				$filterResults = array();
				$generatedNodes = FALSE;
				if (isset($filter['IdentifierFilter'])) {
					if (!preg_match(\TYPO3\Flow\Validation\Validator\UuidValidator::PATTERN_MATCH_UUID, $filter['IdentifierFilter'])) {
						throw new \TYPO3\Eel\FlowQuery\FlowQueryException('find() requires a valid identifier', 1332492263);
					}
					/** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $contextNode */
					foreach ($context as $contextNode) {
						$filterResults = array($contextNode->getContext()->getNodeByIdentifier($filter['IdentifierFilter']));
					}
					$generatedNodes = TRUE;
				} elseif (isset($filter['IdentifierFilter'])) {
					if (!preg_match(\TYPO3\Flow\Validation\Validator\UuidValidator::PATTERN_MATCH_UUID, $filter['IdentifierFilter'])) {
						throw new \TYPO3\Eel\FlowQuery\FlowQueryException('find() requires a valid identifier', 1332492263);
					}
					/** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $contextNode */
					foreach ($context as $contextNode) {
						$filterResults = array($contextNode->getContext()->getNodeByIdentifier($filter['IdentifierFilter']));
					}
					$generatedNodes = TRUE;
				} elseif (isset($filter['PropertyNameFilter']) || isset($filter['PathFilter'])) {
					$nodePath = isset($filter['PropertyNameFilter']) ? $filter['PropertyNameFilter'] : $filter['PathFilter'];
					foreach ($context as $contextNode) {
						$node = $contextNode->getNode($nodePath);
						if ($node !== NULL) {
							array_push($filterResults, $node);
						}
					}
					$generatedNodes = TRUE;
				}

				if (isset($filter['AttributeFilters']) && $filter['AttributeFilters'][0]['Operator'] === 'instanceof') {
					foreach ($context as $contextNode) {
						$filterResults = array_merge($filterResults, $this->nodeDataRepository->findByParentAndNodeTypeInContext($contextNode->getPath(), $filter['AttributeFilters'][0]['Operand'], $contextNode->getContext(), TRUE));
					}
					unset($filter['AttributeFilters'][0]);
					$generatedNodes = TRUE;
				}
				if (isset($filter['AttributeFilters']) && count($filter['AttributeFilters']) > 0) {
					if (!$generatedNodes) {
						throw new \TYPO3\Eel\FlowQuery\FlowQueryException('find() needs an identifier, path or instanceof filter for the first filter part', 1436884196);
					}
					$filterQuery = new FlowQuery($filterResults);
					foreach ($filter['AttributeFilters'] as $attributeFilter) {
						$filterQuery->pushOperation('filter', array($attributeFilter['text']));
					}
					$filterResults = $filterQuery->get();
				}
				$result = array_merge($result, $filterResults);
			}
		}

		$flowQuery->setContext(array_unique($result));
	}

	/**
	 * Check if the parsed filters only contain instanceof filters (e.g. "[instanceof Foo],[instanceof Bar]")
	 *
	 * @param array $filters
	 * @return boolean
	 */
	protected function hasOnlyInstanceOfFilters(array $filters) {
		foreach ($filters as $filter) {
			if (!isset($filter['AttributeFilters']) || count($filter['AttributeFilters']) !== 1 || $filter['AttributeFilters'][0]['Operator'] !== 'instanceof') {
				return FALSE;
			}
		}
		return TRUE;
	}
}

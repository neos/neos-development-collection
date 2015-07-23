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
 * "prevAll" operation working on TYPO3CR nodes. It iterates over all
 * context elements and returns each preceding sibling or only those matching
 * the filter expression specified as optional argument
 */
class PrevAllOperation extends AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'prevAll';

	/**
	 * {@inheritdoc}
	 *
	 * @var integer
	 */
	static protected $priority = 0;

	/**
	 * {@inheritdoc}
	 *
	 * @param array (or array-like object) $context onto which this operation should be applied
	 * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
	 */
	public function canEvaluate($context) {
		return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeInterface));
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
		foreach ($flowQuery->getContext() as $contextNode) {
			$prevNodes = $this->getPrevForNode($contextNode);
			if (is_array($prevNodes)) {
				foreach ($prevNodes as $prevNode) {
					if ($prevNode !== NULL && !isset($outputNodePaths[$prevNode->getPath()])) {
						$outputNodePaths[$prevNode->getPath()] = TRUE;
						$output[] = $prevNode;
					}
				}
			}
		}
		$flowQuery->setContext($output);

		if (isset($arguments[0]) && !empty($arguments[0])) {
			$flowQuery->pushOperation('filter', $arguments);
		}
	}

	/**
	 * @param NodeInterface $contextNode The node for which the preceding node should be found
	 * @return NodeInterface The preceding nodes of $contextNode or NULL
	 */
	protected function getPrevForNode(NodeInterface $contextNode) {
		$nodesInContext = $contextNode->getParent()->getChildNodes();
		$count = count($nodesInContext) - 1;

		for ($i = $count; $i > 0; $i--) {
			if ($nodesInContext[$i] === $contextNode) {
				unset($nodesInContext[$i]);
				return array_values($nodesInContext);
			} else {
				unset($nodesInContext[$i]);
			}
		}
		return NULL;
	}
}
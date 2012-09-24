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
 * Traverse all parent nodes
 */
class ParentsOperation extends \TYPO3\Eel\FlowQuery\Operations\AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'parents';

	/**
	 * {@inheritdoc}
	 *
	 * @var integer
	 */
	static protected $priority = 100;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * {@inheritdoc}
	 *
	 * @param array (or array-like object) $context onto which this operation should be applied
	 * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
	 */
	public function canEvaluate($context) {
		return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface));
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return mixed|null if the operation is final, the return value
	 */
	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments) {
		$output = array();
		$outputNodePaths = array();
		foreach ($flowQuery->getContext() as $contextNode) {
			$siteNode = $this->nodeRepository->getContext()->getCurrentSiteNode();
			while ($contextNode->getParent() !== $siteNode) {
				$contextNode = $contextNode->getParent();
				if (!isset($outputNodePaths[$contextNode->getPath()])) {
					$output[] = $contextNode;
					$outputNodePaths[$contextNode->getPath()] = TRUE;
				}
			}
		}

		$flowQuery->setContext($output);

		if (isset($arguments[0]) && !empty($arguments[0])) {
			$flowQuery->pushOperation('filter', $arguments);
		}
	}
}
?>
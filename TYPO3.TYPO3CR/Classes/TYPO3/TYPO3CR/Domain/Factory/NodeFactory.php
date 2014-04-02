<?php
namespace TYPO3\TYPO3CR\Domain\Factory;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * This factory creates nodes based on node data. Its main purpose is to
 * assure that nodes created for a certain node data container and context
 * are unique in memory.
 *
 * @Flow\Scope("singleton")
 */
class NodeFactory {

	/**
	 * @var array<\TYPO3\TYPO3CR\Domain\Model\Node>
	 */
	protected $nodes = array();

	/**
	 * Creates a node from the given NodeData container.
	 *
	 * If this factory has previously created a Node for the given $node and it's dimensions,
	 * it will return the same node again.
	 *
	 * @param NodeData $nodeData
	 * @param Context $context
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node
	 */
	public function createFromNodeData(NodeData $nodeData, Context $context) {
		$internalNodeIdentifier = $nodeData->getIdentifier() . $nodeData->getDimensionsHash() . spl_object_hash($context);

		if (!isset($this->nodes[$internalNodeIdentifier])) {
			$this->nodes[$internalNodeIdentifier] = new Node($nodeData, $context);
		}
		$node = $this->nodes[$internalNodeIdentifier];

		return $this->filterNodeByContext($node, $context);
	}

	/**
	 * Filter a node by the current context.
	 * Will either return the node or NULL if it is not permitted in current context.
	 *
	 * @param NodeInterface $node
	 * @param Context $context
	 * @return \TYPO3\TYPO3CR\Domain\Model\Node|NULL
	 */
	protected function filterNodeByContext(NodeInterface $node, Context $context) {
		if (!$context->isRemovedContentShown() && $node->isRemoved()) {
			return NULL;
		}
		if (!$context->isInvisibleContentShown() && !$node->isVisible()) {
			return NULL;
		}
		if (!$context->isInaccessibleContentShown() && !$node->isAccessible()) {
			return NULL;
		}
		return $node;
	}

	/**
	 * Reset the node instances (for testing)
	 *
	 * @return void
	 */
	public function reset() {
		$this->nodes = array();
	}

}
<?php
namespace TYPO3\Neos\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeServiceInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;
use TYPO3\TYPO3CR\Exception\NodeException;
use TYPO3\TYPO3CR\Utility;

/**
 * Centralizes common operations like moving and copying of Nodes with Neos specific additional handling.
 *
 * @Flow\Scope("singleton")
 */
class NodeOperations {

	/**
	 * @Flow\Inject
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var NodeServiceInterface
	 */
	protected $nodeService;

	/**
	 * @Flow\Inject
	 * @var ActionsOnNodeCreationService
	 */
	protected $actionsOnNodeCreationService;

	/**
	 * Helper method for creating a new node.
	 *
	 * @param NodeInterface $referenceNode
	 * @param array $nodeData
	 * @param string $position
	 * @param array $actionData
	 * @return NodeInterface
	 * @throws \InvalidArgumentException
	 */
	public function create(NodeInterface $referenceNode, array $nodeData, $position, array $actionData = []) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new \InvalidArgumentException('The position should be one of the following: "before", "into", "after".', 1347133640);
		}
		$nodeType = $this->nodeTypeManager->getNodeType($nodeData['nodeType']);

		if ($nodeType->isOfType('TYPO3.Neos:Document') && !isset($nodeData['properties']['uriPathSegment']) && isset($nodeData['properties']['title'])) {
			$nodeData['properties']['uriPathSegment'] = Utility::renderValidNodeName($nodeData['properties']['title']);
		}

		$proposedNodeName = isset($nodeData['nodeName']) ? $nodeData['nodeName'] : NULL;
		$nodeData['nodeName'] = $this->nodeService->generateUniqueNodeName($this->getDesignatedParentNode($referenceNode, $position)->getPath(), $proposedNodeName);

		if ($position === 'into') {
			$newNode = $referenceNode->createNode($nodeData['nodeName'], $nodeType);
		} else {
			$parentNode = $referenceNode->getParent();
			$newNode = $parentNode->createNode($nodeData['nodeName'], $nodeType);

			if ($position === 'before') {
				$newNode->moveBefore($referenceNode);
			} else {
				$newNode->moveAfter($referenceNode);
			}
		}

		if (isset($nodeData['properties']) && is_array($nodeData['properties'])) {
			foreach ($nodeData['properties'] as $propertyName => $propertyValue) {
				$newNode->setProperty($propertyName, $propertyValue);
			}
		}

		$this->actionsOnNodeCreationService->processActions($newNode, $actionData);

		return $newNode;
	}

	/**
	 * Move $node before, into or after $targetNode
	 *
	 * @param NodeInterface $node
	 * @param NodeInterface $targetNode
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return NodeInterface The same node given as first argument
	 * @throws NodeException
	 */
	public function move(NodeInterface $node, NodeInterface $targetNode, $position) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new NodeException('The position should be one of the following: "before", "into", "after".', 1296132542);
		}

		$designatedParentNode = $this->getDesignatedParentNode($targetNode, $position);
		// If we stay inside the same parent we basically just reorder, no rename needed or wanted.
		if ($designatedParentNode !== $node->getParent()) {
			$designatedNodePath = NodePaths::addNodePathSegment($designatedParentNode->getPath(), $node->getName());
			if ($this->nodeService->nodePathAvailableForNode($designatedNodePath, $node) === FALSE) {
				$nodeName = $this->nodeService->generateUniqueNodeName($designatedParentNode->getPath(), $node->getName());
				if ($nodeName !== $node->getName()) {
					// FIXME: This can be removed if $node->move* supports additionally changing the name of the node.
					$node->setName($nodeName);
				}
			}
		}

		switch ($position) {
			case 'before':
				$node->moveBefore($targetNode);
				break;
			case 'into':
				$node->moveInto($targetNode);
				break;
			case 'after':
				$node->moveAfter($targetNode);
		}

		return $node;
	}

	/**
	 * Copy $node before, into or after $targetNode
	 *
	 * @param NodeInterface $node the node to be copied
	 * @param NodeInterface $targetNode the target node to be copied "to", see $position
	 * @param string $position where the node should be added in relation to $targetNode (allowed: before, into, after)
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return NodeInterface The copied node
	 * @throws NodeException
	 */
	public function copy(NodeInterface $node, NodeInterface $targetNode, $position, $nodeName = NULL) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new NodeException('The position should be one of the following: "before", "into", "after".', 1346832303);
		}

		$nodeName = $this->nodeService->generateUniqueNodeName($this->getDesignatedParentNode($targetNode, $position)->getPath(), (!empty($nodeName) ? $nodeName : NULL));

		switch ($position) {
			case 'before':
				$copiedNode = $node->copyBefore($targetNode, $nodeName);
				break;
			case 'after':
				$copiedNode = $node->copyAfter($targetNode, $nodeName);
				break;
			case 'into':
			default:
				$copiedNode = $node->copyInto($targetNode, $nodeName);
		}

		return $copiedNode;
	}

	/**
	 * @param NodeInterface $targetNode
	 * @param string $position
	 * @return NodeInterface
	 */
	protected function getDesignatedParentNode(NodeInterface $targetNode, $position) {
		$referenceNode = $targetNode;
		if (in_array($position, array('before', 'after'))) {
			$referenceNode = $targetNode->getParent();
		}

		return $referenceNode;
	}
}
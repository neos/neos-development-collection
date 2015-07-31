<?php
namespace TYPO3\TYPO3CR\Domain\Service;

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
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Exception\NodeException;
use TYPO3\TYPO3CR\Exception\NodeExistsException;

/**
 * Provide method to manage node
 *
 * @Flow\Scope("singleton")
 * @api
 */
class NodeService {

	/**
	 * @Flow\Inject
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var ContextFactory
	 */
	protected $contextFactory;

	/**
	 * Sets default node property values on the given node.
	 *
	 * @param NodeInterface $node
	 * @param NodeType $targetNodeType
	 * @return void
	 */
	public function setDefaultValues(NodeInterface $node, NodeType $targetNodeType = NULL) {
		$nodeType = $targetNodeType ?: $node->getNodeType();
		foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $defaultValue) {
			if (trim($node->getProperty($propertyName)) === '') {
				$node->setProperty($propertyName, $defaultValue);
			}
		}
	}

	/**
	 * Create missing child nodes based on target node type configuration
	 *
	 * @param NodeInterface $node
	 * @param NodeType $targetNodeType
	 * @return void
	 */
	public function createChildNodes(NodeInterface $node, NodeType $targetNodeType = NULL) {
		$nodeType = $targetNodeType ?: $node->getNodeType();
		foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeType) {
			try {
				$node->createNode($childNodeName, $childNodeType);
			} catch (NodeExistsException $exception) {
				// If you have a node that has been marked as removed, but is needed again
				// the old node is recovered
				$childNodePath = $node->getPath() . '/' . $childNodeName;
				$contextProperties = $node->getContext()->getProperties();
				$contextProperties['removedContentShown'] = TRUE;
				$context = $this->contextFactory->create($contextProperties);
				$childNode = $context->getNode($childNodePath);
				if ($childNode->isRemoved()) {
					$childNode->setRemoved(FALSE);
				}
			}
		}
	}

	/**
	 * Removes all auto created child nodes that existed in the previous nodeType.
	 *
	 * @param NodeInterface $node
	 * @param NodeType $oldNodeType
	 * @return void
	 */
	public function cleanUpAutoCreatedChildNodes(NodeInterface $node, NodeType $oldNodeType) {
		$newNodeType = $node->getNodeType();
		$autoCreatedChildNodesForNewNodeType = $newNodeType->getAutoCreatedChildNodes();
		$autoCreatedChildNodesForOldNodeType = $oldNodeType->getAutoCreatedChildNodes();
		$removedChildNodesFromOldNodeType = array_diff(
			array_keys($autoCreatedChildNodesForOldNodeType),
			array_keys($autoCreatedChildNodesForNewNodeType)
		);
		/** @var NodeInterface $childNode */
		foreach ($node->getChildNodes() as $childNode) {
			if (in_array($childNode->getName(), $removedChildNodesFromOldNodeType)) {
				$childNode->remove();
			}
		}
	}

	/**
	 * Remove all properties not configured in the current Node Type.
	 * This will not do anything on Nodes marked as removed as those could be queued up for deletion
	 * which contradicts updates (that would be necessary to remove the properties).
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public function cleanUpProperties(NodeInterface $node) {
		if ($node->isRemoved() === FALSE) {
			$nodeData = $node->getNodeData();
			$nodeTypeProperties = $node->getNodeType()->getProperties();
			foreach ($node->getProperties() as $name => $value) {
				if (!isset($nodeTypeProperties[$name])) {
					$nodeData->removeProperty($name);
				}
			}
		}
	}

	/**
	 * @param NodeInterface $node
	 * @param NodeType $nodeType
	 * @return boolean
	 */
	public function isNodeOfType(NodeInterface $node, NodeType $nodeType) {
		if ($node->getNodeType()->getName() === $nodeType->getName()) {
			return TRUE;
		}
		$subNodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeType->getName());
		return isset($subNodeTypes[$node->getNodeType()->getName()]);
	}

	/**
	 * Checks if the given node path exists in any possible context already.
	 *
	 * @param string $nodePath
	 * @return boolean
	 */
	public function nodePathExistsInAnyContext($nodePath) {
		return $this->nodeDataRepository->pathExists($nodePath);
	}

	/**
	 * Checks if the given node path can be used for the given node.
	 *
	 * @param string $nodePath
	 * @param NodeInterface $node
	 * @return boolean
	 */
	public function nodePathAvailableForNode($nodePath, NodeInterface $node) {
		/** @var NodeData $existingNodeData */
		$existingNodeDataObjects = $this->nodeDataRepository->findByPathWithoutReduce($nodePath, $node->getWorkspace(), TRUE);
		foreach ($existingNodeDataObjects as $existingNodeData) {
			if ($existingNodeData->getMovedTo() !== NULL && $existingNodeData->getMovedTo() === $node->getNodeData()) {
				return TRUE;
			}
		}
		return !$this->nodePathExistsInAnyContext($nodePath);
	}

}

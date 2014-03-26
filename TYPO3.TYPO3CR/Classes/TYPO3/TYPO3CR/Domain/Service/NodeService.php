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
	 * Set default node property base on the target node type configuration
	 *
	 * @param NodeInterface $node
	 * @param NodeType $targetNodeType
	 * @return void
	 */
	public function setDefaultValues(NodeInterface $node, NodeType $targetNodeType = NULL) {
		$nodeType = $targetNodeType ?: $node->getNodeType();
		foreach ($nodeType->getProperties() as $propertyName => $propertySettings) {
			if (isset($propertySettings['defaultValue']) && trim($node->getProperty($propertyName)) === '') {
				$node->setProperty($propertyName, $propertySettings['defaultValue']);
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

			}
		}
	}

	/**
	 * Remove all property not configured in the current Node Type
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public function cleanUpProperties(NodeInterface $node) {
		$nodeTypeProperties = $node->getNodeType()->getProperties();
		foreach ($node->getProperties() as $name => $value) {
			if (!isset($nodeTypeProperties[$name])) {
				try {
					$this->systemLogger->log(sprintf('Remove property "%s" from: %s', $name, (string)$node), LOG_DEBUG, NULL, 'TYPO3CR');
					$node->removeProperty($name);
				} catch (NodeException $exception) {

				}
			}
		}
	}

	/**
	 * Remove all child nodes not configured in the current Node Type
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public function cleanUpChildNodes(NodeInterface $node) {
		$documentNodeType = $this->nodeTypeManager->getNodeType('TYPO3.Neos:Document');
		$nodeChildNodes = $node->getNodeType()->getAutoCreatedChildNodes();
		foreach ($node->getChildNodes() as $childNode) {
			/** @var NodeInterface $childNode */
			if (!$this->isNodeOfType($childNode, $documentNodeType) && !isset($nodeChildNodes[$childNode->getName()])) {
				$this->systemLogger->log(sprintf('Remove child node "%s" from: %s', (string)$childNode, (string)$node), LOG_DEBUG, NULL, 'TYPO3CR');
				$childNode->remove();
			}
		}
	}

	/**
	 * Clean up Node properties and child nodes
	 *
	 * Remove all properties or child nodes not declared on the current Node Type
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public function cleanUpNodePropertiesAndChildNodes(NodeInterface $node) {
		if ($node->getNodeType()->getName() === 'unstructured') {
			return;
		}
		$this->cleanUpProperties($node);
		$this->cleanUpChildNodes($node);
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

}

<?php
namespace TYPO3\Neos\TypoScript\Helper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Neos\Domain\Exception;

/**
 * Eel helper for TYPO3CR Nodes
 */
class NodeHelper implements ProtectedContextAwareInterface {

	/**
	 * Check if the given node is already a collection, find collection by nodePath otherwise, throw exception
	 * if no content collection could be found
	 *
	 * @param NodeInterface $node
	 * @param string $nodePath
	 * @return NodeInterface
	 * @throws Exception
	 */
	public function nearestContentCollection(NodeInterface $node, $nodePath) {
		$contentCollectionType = 'TYPO3.Neos:ContentCollection';
		if ($node->getNodeType()->isOfType($contentCollectionType)) {
			return $node;
		} else {
			if ((string)$nodePath === '') {
				throw new Exception(sprintf('No content collection of type %s could be found in the current node and no node path was provided. You might want to configure the nodePath property with a relative path to the content collection.', $contentCollectionType), 1409300545);
			}
			$subNode = $node->getNode($nodePath);
			if ($subNode !== NULL && $subNode->getNodeType()->isOfType($contentCollectionType)) {
				return $subNode;
			} else {
				throw new Exception(sprintf('No content collection of type %s could be found in the current node (%s) or at the path "%s". You might want to adjust your node type configuration and create the missing child node through the "flow node:repair --node-type %s" command.', $contentCollectionType, $node->getPath(), $nodePath, (string)$node->getNodeType()), 1389352984);
			}
		}
	}

	/**
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}

}
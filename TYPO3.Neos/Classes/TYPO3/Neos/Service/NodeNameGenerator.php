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

/**
 * A NodeNameGenerator to generate unique node names
 *
 * @Flow\Scope("singleton")
 */
class NodeNameGenerator {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeService
	 */
	protected $nodeService;

	/**
	 * Generate a node name, optionally based on a suggested "ideal" name
	 *
	 * @param NodeInterface $parentNode
	 * @param string $idealNodeName Can be any string, doesn't need to be a valid node name.
	 * @return string
	 */
	public function generateUniqueNodeName(NodeInterface $parentNode, $idealNodeName = NULL) {
		$possibleNodeName = $this->generatePossibleNodeName($idealNodeName);
		$parentPath = $parentNode->getPath();
		if ($parentPath !== '/') {
			$parentPath .= '/';
		}

		while ($this->nodeService->nodePathExistsInAnyContext($parentPath . $possibleNodeName)) {
			$possibleNodeName = $this->generatePossibleNodeName();
		}

		return $possibleNodeName;
	}

	/**
	 * @param string $idealNodeName
	 * @return string
	 */
	protected function generatePossibleNodeName($idealNodeName = NULL) {
		if ($idealNodeName !== NULL) {
			$possibleNodeName = \TYPO3\TYPO3CR\Utility::renderValidNodeName($idealNodeName);
		} else {
			$possibleNodeName = uniqid('node-');
		}

		return $possibleNodeName;
	}
}
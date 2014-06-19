<?php
namespace TYPO3\Neos\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * Can resolve the target for a given node.
 *
 * @Flow\Scope("singleton")
 */
class NodeShortcutResolver {

	/**
	 * @var NodeInterface
	 */
	protected $node;

	/**
	 * Resolves a shortcut node to the target node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return NodeInterface
	 */
	public function resolveShortcutTarget(NodeInterface $node) {
		$infiniteLoopPrevention = 0;
		while ($node->getNodeType()->isOfType('TYPO3.Neos:Shortcut') && $infiniteLoopPrevention < 50) {
			$infiniteLoopPrevention++;
			switch ($node->getProperty('targetMode')) {
				case 'selectedNode':
					$node = $node->getProperty('targetNode');
					if (!$node instanceof NodeInterface) {
						return NULL;
					}
					break;
				case 'parentNode':
					$node = $node->getParent();
					break;
				case 'firstChildNode':
				default:
					$childNodes = $node->getChildNodes('TYPO3.Neos:Document');
					if ($childNodes !== array()) {
						$node = current($childNodes);
					} else {
						return NULL;
					}
			}
		}

		return $node;
	}
}
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

use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Neos\Service\LinkingService;
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
	 * @Flow\Inject
	 * @var LinkingService
	 */
	protected $linkingService;

	/**
	 * Resolves a shortcut node to the target. The return value can be
	 *
	 * * a NodeInterface instance if the target is a node or a node:// URI
	 * * a string (in case the target is a plain text URI or an asset:// URI)
	 * * NULL in case the shortcut cannot be resolved
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return NodeInterface|string|NULL
	 */
	public function resolveShortcutTarget(NodeInterface $node) {
		$infiniteLoopPrevention = 0;
		while ($node->getNodeType()->isOfType('TYPO3.Neos:Shortcut') && $infiniteLoopPrevention < 50) {
			$infiniteLoopPrevention++;
			switch ($node->getProperty('targetMode')) {
				case 'selectedTarget':
					$target = $node->getProperty('target');
					if ($this->linkingService->hasSupportedScheme($target)) {
						$targetObject = $this->linkingService->convertUriToObject($target, $node);
						if ($targetObject instanceof NodeInterface) {
							$node = $targetObject;
						} elseif ($targetObject instanceof AssetInterface) {
							return $this->linkingService->resolveAssetUri($target);
						}
					} else {
						return $target;
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
<?php
namespace TYPO3\Neos\Routing\Cache;

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
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * This service flushes Route caches triggered by node changes.
 *
 * @Flow\Scope("singleton")
 */
class RouteCacheFlusher {

	/**
	 * @Flow\Inject
	 * @var RouterCachingService
	 */
	protected $routeCachingService;

	/**
	 * @var array
	 */
	protected $tagsToFlush = array();

	/**
	 * Schedules flushing of the routing cache entries for the given $node
	 * Note that child nodes are flushed automatically because they are tagged with all parents.
	 *
	 * @param NodeInterface $node The node which has changed in some way
	 * @return void
	 */
	public function registerNodeChange(NodeInterface $node) {
		if (in_array($node->getIdentifier(), $this->tagsToFlush)) {
			return;
		}
		if (!$node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			return;
		}
		$this->tagsToFlush[] = $node->getIdentifier();
	}

	/**
	 * Flush caches according to the previously registered node changes.
	 *
	 * @return void
	 */
	public function commit() {
		foreach ($this->tagsToFlush as $tag) {
			$this->routeCachingService->flushCachesByTag($tag);
		}
		$this->tagsToFlush = array();
	}

	/**
	 * @return void
	 */
	public function shutdownObject() {
		$this->commit();
	}
}

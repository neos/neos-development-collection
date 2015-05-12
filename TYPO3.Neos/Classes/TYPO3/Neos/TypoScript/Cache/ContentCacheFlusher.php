<?php
namespace TYPO3\Neos\TypoScript\Cache;

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
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TypoScript\Core\Cache\ContentCache;

/**
 * This service flushes TypoScript content caches triggered by node changes.
 *
 * The method registerNodeChange() is triggered by a signal which is configured in the Package class of the TYPO3.Neos
 * package (this package). Information on changed nodes is collected by this method and the respective TypoScript content
 * cache entries are flushed in one operation during Flow's shutdown procedure.
 *
 * @Flow\Scope("singleton")
 */
class ContentCacheFlusher {

	/**
	 * @Flow\Inject
	 * @var ContentCache
	 */
	protected $contentCache;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $tagsToFlush = array();

	/**
	 * Register a node change for a later cache flush. This method is triggered by a signal sent via TYPO3CR's Node
	 * model or the Neos Publishing Service.
	 *
	 * @param NodeInterface $node The node which has changed in some way
	 * @return void
	 */
	public function registerNodeChange(NodeInterface $node) {
		$this->tagsToFlush[ContentCache::TAG_EVERYTHING] = 'which were tagged with "Everything".';

		$nodeTypesToFlush = $this->getAllImplementedNodeTypes($node->getNodeType());
		foreach ($nodeTypesToFlush as $nodeType) {
			$nodeTypeName = $nodeType->getName();
			$this->tagsToFlush['NodeType_' . $nodeTypeName] = sprintf('which were tagged with "NodeType_%s" because node "%s" has changed and was of type "%s".', $nodeTypeName, $node->getPath(), $node->getNodeType()->getName());
		}

		$this->tagsToFlush['Node_' . $node->getIdentifier()] = sprintf('which were tagged with "Node_%s" because node "%s" has changed.', $node->getIdentifier(), $node->getPath());

		while ($node->getDepth() > 1) {
			$node = $node->getParent();
			// Workaround for issue #56566 in TYPO3.TYPO3CR
			if ($node === NULL) {
				break;
			}
			$this->tagsToFlush['DescendantOf_' . $node->getIdentifier()] = sprintf('which were tagged with "DescendantOf_%s" because node "%s" has changed.', $node->getIdentifier(), $node->getPath());
		}
	}

	/**
	 * Flush caches according to the previously registered node changes.
	 *
	 * @return void
	 */
	public function shutdownObject() {
		if ($this->tagsToFlush !== array()) {
			foreach ($this->tagsToFlush as $tag => $logMessage) {
				$affectedEntries = $this->contentCache->flushByTag($tag);
				if ($affectedEntries > 0) {
					$this->systemLogger->log(sprintf('Content cache: Removed %s entries %s', $affectedEntries, $logMessage), LOG_DEBUG);
				}
			}
		}
	}

	/**
	 * @param NodeType $nodeType
	 * @return array<NodeType>
	 */
	protected function getAllImplementedNodeTypes(NodeType $nodeType) {
		$types = array($nodeType);
		foreach ($nodeType->getDeclaredSuperTypes() as $superType) {
			$types = array_merge($types, $this->getAllImplementedNodeTypes($superType));
		}
		return $types;
	}
}

<?php
namespace TYPO3\Neos\Routing\Cache;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * This service flushes Route caches triggered by node changes.
 *
 * @Flow\Scope("singleton")
 */
class RouteCacheFlusher
{
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
     * Schedules flushing of the routing cache entry for the given $nodeData
     * Note: This is not done recursively because the nodePathChanged signal is triggered for any affected node data instance
     *
     * @param NodeData $nodeData The affected node data instance
     * @return void
     */
    public function registerNodePathChange(NodeData $nodeData)
    {
        if (in_array($nodeData->getIdentifier(), $this->tagsToFlush)) {
            return;
        }
        if (!$nodeData->getNodeType()->isOfType('TYPO3.Neos:Document')) {
            return;
        }
        $this->tagsToFlush[] = $nodeData->getIdentifier();
    }

    /**
     * Schedules recursive flushing of the routing cache entries for the given $node
     *
     * @param NodeInterface $node The node which has changed in some way
     * @return void
     */
    public function registerNodeChange(NodeInterface $node)
    {
        $this->registerNodePathChange($node->getNodeData());
        /** @var NodeInterface $childNode */
        foreach ($node->getChildNodes('TYPO3.Neos:Document') as $childNode) {
            $this->registerNodeChange($childNode);
        }
    }

    /**
     * Flush caches according to the previously registered node changes.
     *
     * @return void
     */
    public function commit()
    {
        foreach ($this->tagsToFlush as $tag) {
            $this->routeCachingService->flushCachesByTag($tag);
        }
        $this->tagsToFlush = array();
    }

    /**
     * @return void
     */
    public function shutdownObject()
    {
        $this->commit();
    }
}

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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

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
     * Schedules flushing of the routing cache entries for the given $node
     * Note that child nodes are flushed automatically because they are tagged with all parents.
     *
     * @param NodeInterface $node The node which has changed in some way
     * @return void
     */
    public function registerNodeChange(NodeInterface $node)
    {
        if (in_array($node->getIdentifier(), $this->tagsToFlush)) {
            return;
        }
        if (!$node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
            return;
        }
        $this->tagsToFlush[] = $node->getIdentifier();
    }

    /**
     * Schedules flushing of the all routing cache entries of the workspace whose base workspace has changed.
     * In most cases $workspace will be a user's personal workspace. Flushing the respective cache entries guards
     * against mismatches for nodes which exist in the old and the new base workspace but have different node
     * identifiers and the same URI path (segment).
     *
     * @param Workspace $workspace
     * @param Workspace|null $oldBaseWorkspace
     * @param Workspace|null $newBaseWorkspace
     * @return void
     */
    public function registerBaseWorkspaceChange(Workspace $workspace, Workspace $oldBaseWorkspace = null, Workspace $newBaseWorkspace = null)
    {
        if (!in_array($workspace->getName(), $this->tagsToFlush)) {
            $this->tagsToFlush[] = $workspace->getName();
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

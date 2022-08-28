<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Routing\Cache;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\RouterCachingService;

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
     * @var array<int,string>
     */
    protected $tagsToFlush = [];

    /**
     * Schedules flushing of the routing cache entries for the given $node
     * Note that child nodes are flushed automatically because they are tagged with all parents.
     *
     * @param Node $node The node which has changed in some way
     * @return void
     */
    public function registerNodeChange(Node $node)
    {
        $identifier = (string)$node->nodeAggregateIdentifier;
        if (in_array($identifier, $this->tagsToFlush)) {
            return;
        }
        if (!$node->nodeType->isOfType('Neos.Neos:Document')) {
            return;
        }
        $this->tagsToFlush[] = $identifier;
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
    public function registerBaseWorkspaceChange(
        Workspace $workspace,
        Workspace $oldBaseWorkspace = null,
        Workspace $newBaseWorkspace = null
    ) {
        $identifier = (string)$workspace->workspaceName;
        if (!in_array($identifier, $this->tagsToFlush)) {
            $this->tagsToFlush[] = $identifier;
        }
    }

    /**
     * Flush caches according to the previously registered node changes.
     *
     * @return void
     */
    public function commit()
    {
        $this->routeCachingService->flushCachesByTags($this->tagsToFlush);
        $this->tagsToFlush = [];
    }

    /**
     * @return void
     */
    public function shutdownObject()
    {
        $this->commit();
    }
}

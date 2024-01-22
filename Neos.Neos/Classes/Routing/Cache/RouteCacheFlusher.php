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
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;

/**
 * This service flushes Route caches triggered by node changes.
 *
 * @Flow\Scope("singleton")
 */
class RouteCacheFlusher
{
    use NodeTypeWithFallbackProvider;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

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
        $identifier = $node->nodeAggregateId->value;
        if (in_array($identifier, $this->tagsToFlush)) {
            return;
        }
        if (!$this->getNodeType($node)->isOfType(NodeTypeNameFactory::NAME_DOCUMENT)) {
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
        $identifier = $workspace->workspaceName->value;
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

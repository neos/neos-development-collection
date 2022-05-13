<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Service\Infrastructure;

use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\Flow\Annotations as Flow;

/**
 * A central place to enable / disable all caches in the read side
 *
 * @Flow\Scope("singleton")
 */
class ReadSideMemoryCacheManager
{
    /**
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    public function __construct(ContentGraphInterface $contentGraph, WorkspaceFinder $workspaceFinder)
    {
        $this->contentGraph = $contentGraph;
        $this->workspaceFinder = $workspaceFinder;
    }

    public function enableCache(): void
    {
        $this->contentGraph->enableCache();
        $this->workspaceFinder->enableCache();
    }

    public function disableCache(): void
    {
        $this->contentGraph->disableCache();
        $this->workspaceFinder->disableCache();
    }
}

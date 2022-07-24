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

/**
 * A central place to enable / disable all caches in the read side. Called inside each CommandHandler.
 *
 * @internal
 */
class ReadSideMemoryCacheManager
{
    public function __construct(
        private readonly ContentGraphInterface $contentGraph,
        private readonly WorkspaceFinder $workspaceFinder
    )
    {
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

<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Legacy\ObjectFactories;
/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\Content\ProjectionIntegrityViolationDetectionRunner;
use Neos\ContentRepository\Projection\Content\ProjectionIntegrityViolationDetectorInterface;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamFinder;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamProjector;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Projection\Workspace\WorkspaceProjector;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class ProjectionObjectFactory
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient,
        private readonly VariableFrontend $contentStreamProcessedEventsCache,
        private readonly VariableFrontend $workspaceProcessedEventsCache,
        private readonly ProjectionIntegrityViolationDetectorInterface $projectionIntegrityViolationDetector
    )
    {
    }

    public function buildWorkspaceFinder(): WorkspaceFinder
    {
        return new WorkspaceFinder($this->dbalClient);
    }

    public function buildWorkspaceProjector(): WorkspaceProjector
    {
        return new WorkspaceProjector($this->dbalClient, $this->workspaceProcessedEventsCache);
    }

    public function buildContentStreamProjector(): ContentStreamProjector
    {
        return new ContentStreamProjector($this->dbalClient, $this->contentStreamProcessedEventsCache);
    }

    public function buildContentStreamFinder(): ContentStreamFinder
    {
        return new ContentStreamFinder($this->dbalClient);
    }

    public function buildProjectionIntegrityViolationDetectionRunner(): ProjectionIntegrityViolationDetectionRunner
    {
        return new ProjectionIntegrityViolationDetectionRunner($this->projectionIntegrityViolationDetector);
    }
}

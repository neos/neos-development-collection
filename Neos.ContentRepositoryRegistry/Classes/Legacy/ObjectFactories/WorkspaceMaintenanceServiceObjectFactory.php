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

use Neos\ContentRepository\Feature\ContentStreamCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamFinder;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Service\ContentStreamPruner;
use Neos\ContentRepository\Service\WorkspaceMaintenanceService;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class WorkspaceMaintenanceServiceObjectFactory
{
    public function __construct(
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly WorkspaceCommandHandler $workspaceCommandHandler,
        private readonly ContentStreamFinder $contentStreamFinder,
        private readonly ContentStreamCommandHandler $contentStreamCommandHandler,
        private readonly DbalClientInterface $dbalClient
    ) {}

    public function buildWorkspaceMaintenanceService()
    {
        return new WorkspaceMaintenanceService(
            $this->workspaceFinder,
            $this->workspaceCommandHandler,
            $this->dbalClient
        );
    }


    public function buildContentStreamPruner()
    {
        return new ContentStreamPruner(
            $this->contentStreamFinder,
            $this->contentStreamCommandHandler,
            $this->dbalClient
        );
    }
}

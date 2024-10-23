<?php

declare(strict_types=1);

namespace Neos\Neos\Command;

use Neos\ContentRepository\Core\Service\ContentStreamPrunerFactory;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Service\ProjectionReplayServiceFactory;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\Domain\Service\WorkspaceService;

class CrCommandController extends CommandController
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly WorkspaceService $workspaceService,
        private readonly ProjectionReplayServiceFactory $projectionServiceFactory,
    ) {
        parent::__construct();
    }

    /**
     * This will completely prune the data of the specified content repository.
     *
     * @param string $contentRepository Name of the content repository where the data should be pruned from.
     * @param bool $force Prune the cr without confirmation. This cannot be reverted!
     * @return void
     */
    public function pruneCommand(string $contentRepository = 'default', bool $force = false): void
    {
        if (!$force && !$this->output->askConfirmation(sprintf('> This will prune your content repository "%s". Are you sure to proceed? (y/n) ', $contentRepository), false)) {
            $this->outputLine('<comment>Abort.</comment>');
            return;
        }

        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        $contentStreamPruner = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new ContentStreamPrunerFactory()
        );

        $workspaceMaintenanceService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new WorkspaceMaintenanceServiceFactory()
        );

        $projectionService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            $this->projectionServiceFactory
        );

        // remove the workspace metadata and role assignments for this cr
        $this->workspaceService->pruneRoleAssignments($contentRepositoryId);
        $this->workspaceService->pruneWorkspaceMetadata($contentRepositoryId);

        // reset the events table
        $contentStreamPruner->pruneAll();
        $workspaceMaintenanceService->pruneAll();

        // reset the projections state
        $projectionService->resetAllProjections();

        $this->outputLine('<success>Done.</success>');
    }
}

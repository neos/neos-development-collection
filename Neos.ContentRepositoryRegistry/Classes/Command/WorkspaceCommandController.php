<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

class WorkspaceCommandController extends CommandController
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Rebase all outdated content streams
     */
    public function rebaseOutdatedCommand(string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryIdentifier = ContentRepositoryIdentifier::fromString($contentRepositoryIdentifier);
        $workspaceMaintenanceService = $this->contentRepositoryRegistry->getService($contentRepositoryIdentifier, new WorkspaceMaintenanceServiceFactory());
        $outdatedWorkspaces = $workspaceMaintenanceService->rebaseOutdatedWorkspaces();

        if (!count($outdatedWorkspaces)) {
            $this->outputLine('There are no outdated workspaces.');
        } else {
            foreach ($outdatedWorkspaces as $outdatedWorkspace) {
                $this->outputFormatted('Rebased workspace %s', [$outdatedWorkspace]);
            }
        }
    }
}

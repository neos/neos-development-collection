<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Service\WorkspaceMaintenanceService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

class WorkspaceCommandController extends CommandController
{
    #[Flow\Inject]
    protected WorkspaceMaintenanceService $workspaceMaintenanceService;

    /**
     * Rebase all outdated content streams
     */
    public function rebaseOutdatedCommand(): void
    {
        $outdatedWorkspaces = $this->workspaceMaintenanceService->rebaseOutdatedWorkspaces();

        if (!count($outdatedWorkspaces)) {
            $this->outputLine('There are no outdated workspaces.');
        } else {
            foreach ($outdatedWorkspaces as $outdatedWorkspace) {
                $this->outputFormatted('Rebased workspace %s', [$outdatedWorkspace]);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceModification\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;

/**
 * Change the title or description of a workspace
 *
 * @deprecated with 9.0.0-beta14 metadata should be assigned to workspaces outside the Content Repository core
 */
final readonly class RenameWorkspace implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName Name of the workspace to rename
     * @param WorkspaceTitle $workspaceTitle New title of the workspace
     * @param WorkspaceDescription $workspaceDescription New description of the workspace
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public WorkspaceTitle $workspaceTitle,
        public WorkspaceDescription $workspaceDescription,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName Name of the workspace to rename
     * @param WorkspaceTitle $workspaceTitle New title of the workspace
     * @param WorkspaceDescription $workspaceDescription New description of the workspace
     */
    public static function create(WorkspaceName $workspaceName, WorkspaceTitle $workspaceTitle, WorkspaceDescription $workspaceDescription): self
    {
        return new self($workspaceName, $workspaceTitle, $workspaceDescription);
    }
}

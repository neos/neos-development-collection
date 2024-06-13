<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceModification\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Delete a workspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class DeleteWorkspace implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName Name of the workspace to delete
     */
    private function __construct(
        public WorkspaceName $workspaceName,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName Name of the workspace to delete
     */
    public static function create(WorkspaceName $workspaceName): self
    {
        return new self($workspaceName);
    }
}

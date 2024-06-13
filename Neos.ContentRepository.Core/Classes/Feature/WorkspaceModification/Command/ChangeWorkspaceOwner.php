<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceModification\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Change workspace owner of a workspace, identified by $workspaceName.
 * Setting $newWorkspaceOwner to null, removes the current workspace owner.
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class ChangeWorkspaceOwner implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName Name of the workspace to change the owner for
     * @param string|null $newWorkspaceOwner The id of the new workspace owner or NULL to remove the owner
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public ?string $newWorkspaceOwner,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName Name of the workspace to change the owner for
     * @param string|null $newWorkspaceOwner The id of the new workspace owner or NULL to remove the owner
     */
    public static function create(WorkspaceName $workspaceName, ?string $newWorkspaceOwner): self
    {
        return new self($workspaceName, $newWorkspaceOwner);
    }
}

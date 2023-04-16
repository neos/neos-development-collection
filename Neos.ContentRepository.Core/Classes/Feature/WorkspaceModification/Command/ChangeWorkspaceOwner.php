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
final class ChangeWorkspaceOwner implements CommandInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly ?string $newWorkspaceOwner,
    ) {
    }
}

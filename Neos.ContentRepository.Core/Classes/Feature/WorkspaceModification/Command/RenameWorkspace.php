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
 * @api commands are the write-API of the ContentRepository
 */
final class RenameWorkspace implements CommandInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly WorkspaceTitle $workspaceTitle,
        public readonly WorkspaceDescription $workspaceDescription,
    ) {
    }
}

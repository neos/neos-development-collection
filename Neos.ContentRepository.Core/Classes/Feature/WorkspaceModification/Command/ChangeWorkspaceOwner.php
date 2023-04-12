<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceModification\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Change the title or description of a workspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final class ChangeWorkspaceOwner implements CommandInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly ?string $workspaceOwner,
    ) {
    }
}

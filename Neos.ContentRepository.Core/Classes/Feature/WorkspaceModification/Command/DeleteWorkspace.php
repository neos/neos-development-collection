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
final class DeleteWorkspace implements CommandInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
    ) {
    }
}

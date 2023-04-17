<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceModification\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * Changes the base workspace of a given workspace, identified by $workspaceName.
 *
 * @api commands are the write-API of the ContentRepository
 */
final class ChangeBaseWorkspace implements CommandInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly WorkspaceName $baseWorkspaceName,
        public readonly ContentStreamId $newContentStreamId,
    ) {
    }
}

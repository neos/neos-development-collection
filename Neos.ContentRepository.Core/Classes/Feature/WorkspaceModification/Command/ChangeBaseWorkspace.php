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
    /**
     * @param WorkspaceName $workspaceName Name of the affected workspace
     * @param WorkspaceName $baseWorkspaceName Name of the new base workspace
     * @param ContentStreamId $newContentStreamId The id of the new content stream id that will be assigned to the workspace
     */
    private function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly WorkspaceName $baseWorkspaceName,
        public readonly ContentStreamId $newContentStreamId,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName Name of the affected workspace
     * @param WorkspaceName $baseWorkspaceName Name of the new base workspace
     */
    public static function create(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName): self
    {
        return new self($workspaceName, $baseWorkspaceName, ContentStreamId::create());
    }
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The active record for reading and writing workspaces in-memory
 *
 * @internal
 */
final class InMemoryWorkspaceRecord
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ?WorkspaceName $baseWorkspaceName,
        public ContentStreamId $currentContentStreamId,
        public bool $isUpToDateWithBase,
        public bool $hasChanges,
    ) {
    }
}

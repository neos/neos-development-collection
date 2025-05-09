<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

final readonly class FlushWorkspaceRequest
{
    private function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public WorkspaceName $workspaceName,
    ) {
    }

    public static function create(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): self
    {
        return new self($contentRepositoryId, $workspaceName);
    }
}

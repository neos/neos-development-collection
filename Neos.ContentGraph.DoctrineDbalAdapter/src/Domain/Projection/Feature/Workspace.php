<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\Event\Version;

/**
 * The Workspace projection feature trait
 *
 * @internal
 */
trait Workspace
{
    private function createWorkspace(WorkspaceName $workspaceName, ?WorkspaceName $baseWorkspaceName, ContentStreamId $contentStreamId, Version $version): void
    {
        $this->dbal->insert($this->tableNames->workspace(), [
            'name' => $workspaceName->value,
            'baseWorkspaceName' => $baseWorkspaceName?->value,
            'currentContentStreamId' => $contentStreamId->value,
            'version' => $version->value,
        ]);
    }

    private function removeWorkspace(WorkspaceName $workspaceName): void
    {
        $this->dbal->delete(
            $this->tableNames->workspace(),
            ['name' => $workspaceName->value]
        );
    }

    private function updateBaseWorkspace(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName, ContentStreamId $newContentStreamId, Version $version): void
    {
        $this->dbal->update(
            $this->tableNames->workspace(),
            [
                'baseWorkspaceName' => $baseWorkspaceName->value,
                'currentContentStreamId' => $newContentStreamId->value,
                'version' => $version->value,
            ],
            ['name' => $workspaceName->value]
        );
    }

    private function updateWorkspaceContentStreamId(
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        Version $version,
    ): void {
        $this->dbal->update($this->tableNames->workspace(), [
            'currentContentStreamId' => $contentStreamId->value,
            'version' => $version->value,
        ], [
            'name' => $workspaceName->value
        ]);
    }
}

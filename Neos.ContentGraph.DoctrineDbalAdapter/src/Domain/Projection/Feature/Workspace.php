<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The Workspace projection feature trait
 *
 * @internal
 */
trait Workspace
{
    private function createWorkspace(WorkspaceName $workspaceName, ?WorkspaceName $baseWorkspaceName, ContentStreamId $contentStreamId): void
    {
        $this->dbal->insert($this->tableNames->workspace(), [
            'name' => $workspaceName->value,
            'baseWorkspaceName' => $baseWorkspaceName?->value,
            'currentContentStreamId' => $contentStreamId->value,
            'status' => WorkspaceStatus::UP_TO_DATE->value
        ]);
    }

    private function removeWorkspace(WorkspaceName $workspaceName): void
    {
        $this->dbal->delete(
            $this->tableNames->workspace(),
            ['name' => $workspaceName->value]
        );
    }

    private function updateBaseWorkspace(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName, ContentStreamId $newContentStreamId): void
    {
        $this->dbal->update(
            $this->tableNames->workspace(),
            [
                'baseWorkspaceName' => $baseWorkspaceName->value,
                'currentContentStreamId' => $newContentStreamId->value,
            ],
            ['name' => $workspaceName->value]
        );
    }

    private function updateWorkspaceContentStreamId(
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
    ): void {
        $this->dbal->update($this->tableNames->workspace(), [
            'currentContentStreamId' => $contentStreamId->value,
        ], [
            'name' => $workspaceName->value
        ]);
    }

    private function markWorkspaceAsUpToDate(WorkspaceName $workspaceName): void
    {
        $this->dbal->executeStatement('
            UPDATE ' . $this->tableNames->workspace() . '
            SET status = :upToDate
            WHERE
                name = :workspaceName
        ', [
            'upToDate' => WorkspaceStatus::UP_TO_DATE->value,
            'workspaceName' => $workspaceName->value
        ]);
    }

    private function markDependentWorkspacesAsOutdated(WorkspaceName $baseWorkspaceName): void
    {
        $this->dbal->executeStatement('
            UPDATE ' . $this->tableNames->workspace() . '
            SET status = :outdated
            WHERE
                baseWorkspaceName = :baseWorkspaceName
        ', [
            'outdated' => WorkspaceStatus::OUTDATED->value,
            'baseWorkspaceName' => $baseWorkspaceName->value
        ]);
    }

    private function markWorkspaceAsOutdated(WorkspaceName $workspaceName): void
    {
        $this->dbal->executeStatement('
            UPDATE ' . $this->tableNames->workspace() . '
            SET
                status = :outdated
            WHERE
                name = :workspaceName
        ', [
            'outdated' => WorkspaceStatus::OUTDATED->value,
            'workspaceName' => $workspaceName->value
        ]);
    }

    private function markWorkspaceAsOutdatedConflict(WorkspaceName $workspaceName): void
    {
        $this->dbal->executeStatement('
            UPDATE ' . $this->tableNames->workspace() . '
            SET
                status = :outdatedConflict
            WHERE
                name = :workspaceName
        ', [
            'outdatedConflict' => WorkspaceStatus::OUTDATED_CONFLICT->value,
            'workspaceName' => $workspaceName->value
        ]);
    }
}

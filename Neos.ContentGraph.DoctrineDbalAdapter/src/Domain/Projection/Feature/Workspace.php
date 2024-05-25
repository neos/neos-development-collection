<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceStatus;
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
            'workspaceName' => $workspaceName->value,
            'baseWorkspaceName' => $baseWorkspaceName?->value,
            'currentContentStreamId' => $contentStreamId->value,
            'status' => WorkspaceStatus::UP_TO_DATE->value
        ]);
    }

    private function removeWorkspace(WorkspaceName $workspaceName): void
    {
        $this->dbal->delete(
            $this->tableNames->workspace(),
            ['workspaceName' => $workspaceName->value]
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
            ['workspaceName' => $workspaceName->value]
        );
    }

    private function updateWorkspaceContentStreamId(
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
    ): void {
        $this->dbal->update($this->tableNames->workspace(), [
            'currentContentStreamId' => $contentStreamId->value,
        ], [
            'workspaceName' => $workspaceName->value
        ]);
    }

    private function markWorkspaceAsUpToDate(WorkspaceName $workspaceName): void
    {
        $this->dbal->executeUpdate('
            UPDATE ' . $this->tableNames->workspace() . '
            SET status = :upToDate
            WHERE
                workspacename = :workspaceName
        ', [
            'upToDate' => WorkspaceStatus::UP_TO_DATE->value,
            'workspaceName' => $workspaceName->value
        ]);
    }

    private function markDependentWorkspacesAsOutdated(WorkspaceName $baseWorkspaceName): void
    {
        $this->dbal->executeUpdate('
            UPDATE ' . $this->tableNames->workspace() . '
            SET status = :outdated
            WHERE
                baseworkspacename = :baseWorkspaceName
        ', [
            'outdated' => WorkspaceStatus::OUTDATED->value,
            'baseWorkspaceName' => $baseWorkspaceName->value
        ]);
    }

    private function markWorkspaceAsOutdated(WorkspaceName $workspaceName): void
    {
        $this->dbal->executeUpdate('
            UPDATE ' . $this->tableNames->workspace() . '
            SET
                status = :outdated
            WHERE
                workspacename = :workspaceName
        ', [
            'outdated' => WorkspaceStatus::OUTDATED->value,
            'workspaceName' => $workspaceName->value
        ]);
    }

    private function markWorkspaceAsOutdatedConflict(WorkspaceName $workspaceName): void
    {
        $this->dbal->executeUpdate('
            UPDATE ' . $this->tableNames->workspace() . '
            SET
                status = :outdatedConflict
            WHERE
                workspacename = :workspaceName
        ', [
            'outdatedConflict' => WorkspaceStatus::OUTDATED_CONFLICT->value,
            'workspaceName' => $workspaceName->value
        ]);
    }
}

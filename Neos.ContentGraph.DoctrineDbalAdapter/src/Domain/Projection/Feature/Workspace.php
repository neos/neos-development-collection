<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceBaseWorkspaceWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
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
    abstract protected function getDatabaseConnection(): Connection;

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        $this->dbal->insert($this->tableNames->workspace(), [
            'workspaceName' => $event->workspaceName->value,
            'baseWorkspaceName' => $event->baseWorkspaceName->value,
            'currentContentStreamId' => $event->newContentStreamId->value,
            'status' => WorkspaceStatus::UP_TO_DATE->value
        ]);
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->dbal->insert($this->tableNames->workspace(), [
            'workspaceName' => $event->workspaceName->value,
            'currentContentStreamId' => $event->newContentStreamId->value,
            'status' => WorkspaceStatus::UP_TO_DATE->value
        ]);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
        $this->markWorkspaceAsOutdated($event->workspaceName);
        $this->markDependentWorkspacesAsOutdated($event->workspaceName);
    }

    private function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
        $this->markDependentWorkspacesAsOutdated($event->workspaceName);
    }

    private function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        // TODO: How do we test this method?
        // It's hard to design a BDD testcase that fails if this method is commented out...
        $this->updateContentStreamId(
            $event->newSourceContentStreamId,
            $event->sourceWorkspaceName
        );

        $this->markDependentWorkspacesAsOutdated($event->targetWorkspaceName);

        // NASTY: we need to set the source workspace name as non-outdated; as it has been made up-to-date again.
        $this->markWorkspaceAsUpToDate($event->sourceWorkspaceName);

        $this->markDependentWorkspacesAsOutdated($event->sourceWorkspaceName);
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        // TODO: How do we test this method?
        // It's hard to design a BDD testcase that fails if this method is commented out...
        $this->updateContentStreamId(
            $event->newSourceContentStreamId,
            $event->sourceWorkspaceName
        );

        $this->markDependentWorkspacesAsOutdated($event->targetWorkspaceName);

        // NASTY: we need to set the source workspace name as non-outdated; as it has been made up-to-date again.
        $this->markWorkspaceAsUpToDate($event->sourceWorkspaceName);

        $this->markDependentWorkspacesAsOutdated($event->sourceWorkspaceName);
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
        $this->markDependentWorkspacesAsOutdated($event->workspaceName);

        // When the rebase is successful, we can set the status of the workspace back to UP_TO_DATE.
        $this->markWorkspaceAsUpToDate($event->workspaceName);
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        $this->markWorkspaceAsOutdatedConflict($event->workspaceName);
    }

    private function whenWorkspaceWasRemoved(WorkspaceWasRemoved $event): void
    {
        $this->dbal->delete(
            $this->tableNames->workspace(),
            ['workspaceName' => $event->workspaceName->value]
        );
    }

    private function whenWorkspaceBaseWorkspaceWasChanged(WorkspaceBaseWorkspaceWasChanged $event): void
    {
        $this->dbal->update(
            $this->tableNames->workspace(),
            [
                'baseWorkspaceName' => $event->baseWorkspaceName->value,
                'currentContentStreamId' => $event->newContentStreamId->value,
            ],
            ['workspaceName' => $event->workspaceName->value]
        );
    }

    private function updateContentStreamId(
        ContentStreamId $contentStreamId,
        WorkspaceName $workspaceName,
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

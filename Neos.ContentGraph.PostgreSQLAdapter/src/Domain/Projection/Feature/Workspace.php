<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceBaseWorkspaceWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

trait Workspace
{
    // ### ----------- event dispatchers
    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->createWorkspace($event->workspaceName, null, $event->newContentStreamId);
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        $this->createWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        $this->updateWorkspaceContentStreamId($event->sourceWorkspaceName, $event->newSourceContentStreamId);
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceBaseWorkspaceWasChanged(WorkspaceBaseWorkspaceWasChanged $event): void
    {
        $this->updateBaseWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        // legacy handling:
        // before https://github.com/neos/neos-development-collection/pull/4965 this event was emitted and set the content stream status to `REBASE_ERROR`
        // instead of setting the error state on replay for old events we make it almost behave like if the rebase had failed today: reopen the workspaces content stream id
        // the candidateContentStreamId will be removed by the ContentStreamPruner
        $this->reopenContentStream($event->sourceContentStreamId);
    }

    private function whenWorkspaceWasRemoved(WorkspaceWasRemoved $event): void
    {
        $this->removeWorkspace($event->workspaceName);
    }

    // ### ----------- internal API

    private function createWorkspace(WorkspaceName $workspaceName, ?WorkspaceName $baseWorkspaceName, ContentStreamId $contentStreamId): void
    {
        $this->getDatabaseConnection()->insert(
            $this->getTableNames()->workspace(),
            [
                'name' => $workspaceName->value,
                'baseworkspacename' => $baseWorkspaceName?->value,
                'currentcontentstreamid' => $contentStreamId->value
            ]
        );
    }

    private function removeWorkspace(WorkspaceName $workspaceName): void
    {
        $this->getDatabaseConnection()->delete(
            $this->getTableNames()->workspace(),
            ['name' => $workspaceName->value]
        );
    }

    private function updateBaseWorkspace(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName, ContentStreamId $newContentStreamId): void
    {
        $this->getDatabaseConnection()->update(
            $this->getTableNames()->workspace(),
            [
                'baseworkspacename' => $baseWorkspaceName->value,
                'currentcontentstreamid' => $newContentStreamId->value,
            ],
            ['name' => $workspaceName->value]
        );
    }

    private function updateWorkspaceContentStreamId(
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
    ): void {
        $this->getDatabaseConnection()->update($this->getTableNames()->workspace(), [
            'currentcontentstreamid' => $contentStreamId->value,
        ], [
            'name' => $workspaceName->value
        ]);
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function getTableNames(): ContentGraphTableNames;
}

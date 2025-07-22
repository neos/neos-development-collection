<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
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

    protected abstract function getDatabaseConnection(): Connection;

    protected abstract function getTableNames(): ContentGraphTableNames;

}

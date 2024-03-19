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
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The CurrentWorkspaceContentStreamId projection feature trait
 *
 * Duplicated from the projection {@see \Neos\ContentRepository\Core\Projection\Workspace\WorkspaceProjection}
 * But we only require the workspace name to content stream id mapping.
 *
 * @internal
 */
trait CurrentWorkspaceContentStreamId
{
    abstract protected function getTableNamePrefix(): string;

    abstract protected function getDatabaseConnection(): Connection;

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        $this->getDatabaseConnection()->insert($this->getTableNamePrefix() . '_workspaces', [
            'workspaceName' => $event->workspaceName->value,
            'currentContentStreamId' => $event->newContentStreamId->value
        ]);
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->getDatabaseConnection()->insert($this->getTableNamePrefix() . '_workspaces', [
            'workspaceName' => $event->workspaceName->value,
            'currentContentStreamId' => $event->newContentStreamId->value
        ]);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
    }

    private function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
    }

    private function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        $this->updateContentStreamId($event->newSourceContentStreamId, $event->sourceWorkspaceName);
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        $this->updateContentStreamId($event->newSourceContentStreamId, $event->sourceWorkspaceName);
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
    }

    private function whenWorkspaceWasRemoved(WorkspaceWasRemoved $event): void
    {
        $this->getDatabaseConnection()->delete(
            $this->getTableNamePrefix() . '_workspaces',
            ['workspaceName' => $event->workspaceName->value]
        );
    }

    private function whenWorkspaceBaseWorkspaceWasChanged(WorkspaceBaseWorkspaceWasChanged $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
    }

    private function updateContentStreamId(
        ContentStreamId $contentStreamId,
        WorkspaceName $workspaceName,
    ): void {
        $this->getDatabaseConnection()->update($this->getTableNamePrefix() . '_workspaces', [
            'currentContentStreamId' => $contentStreamId->value,
        ], [
            'workspaceName' => $workspaceName->value
        ]);
    }
}

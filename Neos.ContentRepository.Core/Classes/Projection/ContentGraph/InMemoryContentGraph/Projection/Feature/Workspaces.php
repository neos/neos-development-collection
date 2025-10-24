<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\Feature;

use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceBaseWorkspaceWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;

/**
 * The Workspace projection feature trait
 *
 * @internal
 */
trait Workspaces
{
    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->workspaceRegistry->createWorkspace($event->workspaceName, null, $event->newContentStreamId);
        $this->graphStructure->initializeContentStream($event->newContentStreamId);
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        $this->workspaceRegistry->createWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId);
        $this->graphStructure->initializeContentStream($event->newContentStreamId);
    }

    private function whenWorkspaceBaseWorkspaceWasChanged(WorkspaceBaseWorkspaceWasChanged $event): void
    {
        $this->workspaceRegistry->updateBaseWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId);
    }
    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->workspaceRegistry->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        $this->workspaceRegistry->updateWorkspaceContentStreamId($event->sourceWorkspaceName, $event->newSourceContentStreamId);
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->workspaceRegistry->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceWasRemoved(WorkspaceWasRemoved $event): void
    {
        $contentStreamToBeRemoved = $this->workspaceRegistry->workspaces[$event->workspaceName->value]->currentContentStreamId;
        $this->contentStreamRegistry->removeContentStream($contentStreamToBeRemoved);
        $this->graphStructure->removeContentStream($contentStreamToBeRemoved);
        $this->workspaceRegistry->removeWorkspace($event->workspaceName);
    }
}

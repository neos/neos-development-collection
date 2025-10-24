<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository;

use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryWorkspaceRecord;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The In-Memory workspace registry
 *
 * To be used as a source of workspaces for read and write in-memory
 *
 * @internal
 */
final class InMemoryWorkspaceRegistry
{
    private static ?self $instance = null;

    /**
     * @param array<string,InMemoryWorkspaceRecord> $workspaces indexed by workspace name
     */
    private function __construct(
        public array $workspaces = []
    ) {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function reset(): void
    {
        $this->workspaces = [];
    }

    public function createWorkspace(WorkspaceName $workspaceName, ?WorkspaceName $baseWorkspaceName, ContentStreamId $contentStreamId): void
    {
        if (array_key_exists($workspaceName->value, $this->workspaces)) {
            throw new \Exception('Workspace ' . $workspaceName->value . ' already exists.', 1745102226);
        }

        $this->workspaces[$workspaceName->value] = new InMemoryWorkspaceRecord(
            $workspaceName,
            $baseWorkspaceName,
            $contentStreamId,
            false,
            false,
        );
    }

    public function updateBaseWorkspace(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName, ContentStreamId $newContentStreamId): void
    {
        $this->requireWorkspaceToExist($workspaceName);
        $this->workspaces[$workspaceName->value]->baseWorkspaceName = $baseWorkspaceName;
        $this->workspaces[$workspaceName->value]->currentContentStreamId = $newContentStreamId;
    }

    public function removeWorkspace(WorkspaceName $workspaceName): void
    {
        if (array_key_exists($workspaceName->value, $this->workspaces)) {
            unset($this->workspaces[$workspaceName->value]);
        }
    }

    public function updateWorkspaceContentStreamId(
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
    ): void {
        $this->requireWorkspaceToExist($workspaceName);
        $this->workspaces[$workspaceName->value]->currentContentStreamId = $contentStreamId;
    }

    private function requireWorkspaceToExist(WorkspaceName $workspaceName): void
    {
        if (!array_key_exists($workspaceName->value, $this->workspaces)) {
            throw new \Exception('Workspace with name ' . $workspaceName->value . ' does not exist.', 1745102331);
        }
    }
}

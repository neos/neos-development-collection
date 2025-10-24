<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph;

use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryContentGraphStructure;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryContentStreamRecord;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryWorkspaceRecord;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository\InMemoryContentGraph;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository\InMemoryContentStreamRegistry;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository\InMemoryWorkspaceRegistry;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository\NodeFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;

/**
 * @internal
 */
final readonly class InMemoryContentGraphReadModelAdapter implements ContentGraphReadModelInterface
{
    public function __construct(
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager,
        private InMemoryContentGraphStructure $graphStructure,
        private InMemoryWorkspaceRegistry $workspaceRegistry,
        private InMemoryContentStreamRegistry $contentStreamRegistry,
        private NodeFactory $nodeFactory,
    ) {
    }

    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     */
    public function getContentGraph(WorkspaceName $workspaceName): InMemoryContentGraph
    {
        $workspace = $this->workspaceRegistry->workspaces[$workspaceName->value] ?? null;
        if ($workspace === null) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }
        return new InMemoryContentGraph(
            $this->graphStructure,
            $this->nodeFactory,
            $this->contentRepositoryId,
            $this->nodeTypeManager,
            $workspaceName,
            $workspace->currentContentStreamId,
        );
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        $workspaceRecord = $this->workspaceRegistry->workspaces[$workspaceName->value] ?? null;

        return $workspaceRecord
            ? self::mapWorkspaceRecordToWorkspace($workspaceRecord)
            : null;
    }

    public function findWorkspaces(): Workspaces
    {
        return Workspaces::fromArray(array_map(
            fn (InMemoryWorkspaceRecord $workspaceRecord): Workspace
                => self::mapWorkspaceRecordToWorkspace($workspaceRecord),
            $this->workspaceRegistry->workspaces
        ));
    }

    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream
    {
        $contentStreamRecord = $this->contentStreamRegistry->contentStreams[$contentStreamId->value] ?? null;

        return $contentStreamRecord
            ? self::mapContentStreamRecordToContentStream($contentStreamRecord)
            : null;
    }

    public function countNodes(): int
    {
        return $this->graphStructure->totalNodeCount;
    }

    private static function mapWorkspaceRecordToWorkspace(InMemoryWorkspaceRecord $workspaceRecord): Workspace
    {
        return Workspace::create(
            $workspaceRecord->workspaceName,
            $workspaceRecord->baseWorkspaceName,
            $workspaceRecord->currentContentStreamId,
            $workspaceRecord->baseWorkspaceName === null || $workspaceRecord->isUpToDateWithBase
                ? WorkspaceStatus::UP_TO_DATE
                : WorkspaceStatus::OUTDATED,
            $workspaceRecord->baseWorkspaceName !== null && $workspaceRecord->hasChanges
        );
    }

    private static function mapContentStreamRecordToContentStream(InMemoryContentStreamRecord $contentStreamRecord): ContentStream
    {
        return ContentStream::create(
            $contentStreamRecord->id,
            $contentStreamRecord->sourceContentStreamId,
            $contentStreamRecord->version,
            $contentStreamRecord->isClosed,
        );
    }
}

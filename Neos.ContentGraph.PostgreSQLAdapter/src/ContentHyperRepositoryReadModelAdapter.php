<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\ContentRepositoryReadModel;
use Neos\ContentRepository\Core\ContentRepositoryReadModelAdapterInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreams;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;

/**
 * @internal only used within
 * @see ContentRepositoryReadModel
 */
final readonly class ContentHyperRepositoryReadModelAdapter implements ContentRepositoryReadModelAdapterInterface
{
    public function __construct(
        private Connection $dbal,
        private NodeFactory $nodeFactory,
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager,
        private string $tableNamePrefix,
    ) {
    }

    public function buildContentGraph(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphInterface
    {
        return new ContentHyperGraph($this->dbal, $this->nodeFactory, $this->contentRepositoryId, $this->nodeTypeManager, $this->tableNamePrefix, $workspaceName, $contentStreamId);
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        // TODO: Implement findWorkspaceByName() method.
        return null;
    }

    public function findWorkspaces(): Workspaces
    {
        // TODO: Implement getWorkspaces() method.
        return Workspaces::createEmpty();
    }

    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream
    {
        // TODO: Implement findContentStreamById() method.
        return null;
    }

    public function findContentStreams(): ContentStreams
    {
        // TODO: Implement getContentStreams() method.
        return ContentStreams::createEmpty();
    }

    public function findUnusedAndRemovedContentStreamIds(): iterable
    {
        // TODO: Implement getUnusedAndRemovedContentStreamIds() method.
        return [];
    }
}

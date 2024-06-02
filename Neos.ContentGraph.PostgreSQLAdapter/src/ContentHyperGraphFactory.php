<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\ContentGraphFactoryInterface;
use Neos\ContentRepository\Core\ContentGraphAdapter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreams;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;

/**
 * @internal only used within
 * @see ContentGraphAdapter
 */
final readonly class ContentHyperGraphFactory implements ContentGraphFactoryInterface
{
    public function __construct(
        private Connection $dbal,
        private NodeFactory $nodeFactory,
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager,
        private string $tableNamePrefix,
    ) {
    }

    public function buildForWorkspace(WorkspaceName $workspaceName): ContentGraphInterface
    {
        // FIXME: Should be part of this projection, this is forbidden
        $tableName = strtolower(sprintf(
            'cr_%s_p_%s',
            $this->contentRepositoryId->value,
            'Workspace'
        ));

        $row = $this->dbal->executeQuery(
            '
                SELECT * FROM ' . $tableName . '
                WHERE workspaceName = :workspaceName
            ',
            [
                'workspaceName' => $workspaceName->value,
            ]
        )->fetchAssociative();

        if ($row === false) {
            throw new ContentStreamDoesNotExistYet('The workspace "' . $workspaceName->value . '" does not exist.', 1714839710);
        }

        return $this->buildForWorkspaceAndContentStream($workspaceName, ContentStreamId::fromString($row['currentcontentstreamid']));
    }

    public function buildForWorkspaceAndContentStream(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphInterface
    {
        return new ContentHyperGraph($this->dbal, $this->nodeFactory, $this->contentRepositoryId, $this->nodeTypeManager, $this->tableNamePrefix, $workspaceName, $contentStreamId);
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        // TODO: Implement findWorkspaceByName() method.
        return null;
    }

    public function getWorkspaces(): Workspaces
    {
        // TODO: Implement getWorkspaces() method.
        return Workspaces::createEmpty();
    }

    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream
    {
        // TODO: Implement findContentStreamById() method.
        return null;
    }

    public function getContentStreams(): ContentStreams
    {
        // TODO: Implement getContentStreams() method.
        return ContentStreams::createEmpty();
    }

    public function getUnusedAndRemovedContentStreamIds(): iterable
    {
        // TODO: Implement getUnusedAndRemovedContentStreamIds() method.
        return [];
    }
}

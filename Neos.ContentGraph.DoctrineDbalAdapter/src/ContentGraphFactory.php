<?php

declare(strict_types=1);

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\ContentGraphFactoryInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreams;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\EventStore\Model\Event\Version;

/**
 * @internal only used inside the
 * @see ContentGraphAdapter
 */
final readonly class ContentGraphFactory implements ContentGraphFactoryInterface
{
    public function __construct(
        private Connection $dbal,
        private NodeFactory $nodeFactory,
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager,
        private ContentGraphTableNames $tableNames
    ) {
    }

    public function buildForWorkspace(WorkspaceName $workspaceName): ContentGraph
    {
        $workspace = $this->findWorkspaceByName($workspaceName);

        if ($workspace === null) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }
        return $this->buildForWorkspaceAndContentStream($workspace->workspaceName, $workspace->currentContentStreamId);
    }

    public function buildForWorkspaceAndContentStream(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraph
    {
        return new ContentGraph($this->dbal, $this->nodeFactory, $this->contentRepositoryId, $this->nodeTypeManager, $this->tableNames, $workspaceName, $contentStreamId);
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        $workspaceByNameStatement = <<<SQL
            SELECT
                name, baseWorkspaceName, currentContentStreamId, status
            FROM
                {$this->tableNames->workspace()}
            WHERE
                name = :workspaceName
            LIMIT 1
        SQL;
        try {
            $row = $this->dbal->fetchAssociative($workspaceByNameStatement, [
                'workspaceName' => $workspaceName->value,
            ]);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load workspace from database: %s', $e->getMessage()), 1716486077, $e);
        }
        if ($row === false) {
            return null;
        }
        return self::workspaceFromDatabaseRow($row);
    }

    public function getWorkspaces(): Workspaces
    {
        $workspacesStatement = <<<SQL
            SELECT
                name, baseWorkspaceName, currentContentStreamId, status
            FROM
                {$this->tableNames->workspace()}
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($workspacesStatement);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load workspaces from database: %s', $e->getMessage()), 1716902981, $e);
        }
        return Workspaces::fromArray(array_map(self::workspaceFromDatabaseRow(...), $rows));
    }

    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream
    {
        $contentStreamByIdStatement = <<<SQL
            SELECT
                id, sourceContentStreamId, status, version
            FROM
                {$this->tableNames->contentStream()}
            WHERE
                id = :contentStreamId
            LIMIT 1
        SQL;
        try {
            $row = $this->dbal->fetchAssociative($contentStreamByIdStatement, [
                'contentStreamId' => $contentStreamId->value,
            ]);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load content stream from database: %s', $e->getMessage()), 1716903166, $e);
        }
        if ($row === false) {
            return null;
        }
        return self::contentStreamFromDatabaseRow($row);
    }

    public function getContentStreams(): ContentStreams
    {
        $contentStreamsStatement = <<<SQL
            SELECT
                id, sourceContentStreamId, status, version
            FROM
                {$this->tableNames->contentStream()}
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($contentStreamsStatement);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load content streams from database: %s', $e->getMessage()), 1716903042, $e);
        }
        return ContentStreams::fromArray(array_map(self::contentStreamFromDatabaseRow(...), $rows));
    }

    public function getUnusedAndRemovedContentStreamIds(): iterable
    {
        $removedContentStreamIdsStatement = <<<SQL
            WITH RECURSIVE transitiveUsedContentStreams (id) AS (
                -- initial case: find all content streams currently in direct use by a workspace
                SELECT
                    id
                FROM
                    {$this->tableNames->contentStream()}
                WHERE
                    status = :inUseStatus
                    AND removed = false
                UNION
                    -- now, when a content stream is in use by a workspace, its source content stream is
                    -- also "transitively" in use.
                SELECT
                    sourceContentStreamId
                FROM
                    {$this->tableNames->contentStream()}
                    JOIN transitiveUsedContentStreams ON {$this->tableNames->contentStream()}.id = transitiveUsedContentStreams.id
                WHERE
                    {$this->tableNames->contentStream()}.sourceContentStreamId IS NOT NULL
            )
            -- now, we check for removed content streams which we do not need anymore transitively
            SELECT
                id
            FROM
                {$this->tableNames->contentStream()} AS cs
            WHERE
                removed = true
                AND NOT EXISTS (
                    SELECT 1
                    FROM transitiveUsedContentStreams
                    WHERE cs.id = transitiveUsedContentStreams.id
                )
        SQL;
        try {
            $contentStreamIds = $this->dbal->fetchFirstColumn($removedContentStreamIdsStatement, [
                'inUseStatus' => ContentStreamStatus::IN_USE_BY_WORKSPACE->value
            ]);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load unused and removed content stream ids from database: %s', $e->getMessage()), 1716914615, $e);
        }
        return array_map(ContentStreamId::fromString(...), $contentStreamIds);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function workspaceFromDatabaseRow(array $row): Workspace
    {
        return new Workspace(
            WorkspaceName::fromString($row['name']),
            isset($row['baseWorkspaceName']) ? WorkspaceName::fromString($row['baseWorkspaceName']) : null,
            ContentStreamId::fromString($row['currentContentStreamId']),
            WorkspaceStatus::from($row['status']),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function contentStreamFromDatabaseRow(array $row): ContentStream
    {
        return new ContentStream(
            ContentStreamId::fromString($row['id']),
            isset($row['sourceContentStreamId']) ? ContentStreamId::fromString($row['sourceContentStreamId']) : null,
            ContentStreamStatus::from($row['status']),
            Version::fromInteger((int)$row['version']),
        );
    }
}

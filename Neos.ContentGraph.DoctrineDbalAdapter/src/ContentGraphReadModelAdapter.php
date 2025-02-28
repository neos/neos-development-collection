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
use Doctrine\DBAL\Query\QueryBuilder;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\EventStore\Model\Event\Version;

/**
 * @internal
 */
final readonly class ContentGraphReadModelAdapter implements ContentGraphReadModelInterface
{
    public function __construct(
        private Connection $dbal,
        private NodeFactory $nodeFactory,
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager,
        private ContentGraphTableNames $tableNames
    ) {
    }

    public function getContentGraph(WorkspaceName $workspaceName): ContentGraph
    {
        $currentContentStreamIdStatement = <<<SQL
            SELECT
                currentContentStreamId
            FROM
                {$this->tableNames->workspace()}
            WHERE
                name = :workspaceName
            LIMIT 1
        SQL;
        try {
            $row = $this->dbal->fetchAssociative($currentContentStreamIdStatement, [
                'workspaceName' => $workspaceName->value,
            ]);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load current content stream id from database: %s', $e->getMessage()), 1716903166, $e);
        }
        if ($row === false) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }
        $currentContentStreamId = ContentStreamId::fromString($row['currentContentStreamId']);
        return new ContentGraph($this->dbal, $this->nodeFactory, $this->contentRepositoryId, $this->nodeTypeManager, $this->tableNames, $workspaceName, $currentContentStreamId);
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        $workspaceQuery = $this->getBasicWorkspaceQuery()
            ->where('ws.name = :workspaceName')
            ->setMaxResults(1)
            ->setParameter('workspaceName', $workspaceName->value);
        try {
            $row = $workspaceQuery->fetchAssociative();
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load workspace from database: %s', $e->getMessage()), 1716486077, $e);
        }
        if ($row === false) {
            return null;
        }
        return self::workspaceFromDatabaseRow($row);
    }

    public function findWorkspaces(): Workspaces
    {
        $workspacesQuery = $this->getBasicWorkspaceQuery();
        try {
            $rows = $workspacesQuery->fetchAllAssociative();
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to load workspaces from database: %s', $e->getMessage()), 1716902981, $e);
        }
        return Workspaces::fromArray(array_map(self::workspaceFromDatabaseRow(...), $rows));
    }

    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream
    {
        $contentStreamByIdStatement = <<<SQL
            SELECT
                id, sourceContentStreamId, version, closed
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

    public function countNodes(): int
    {
        $countNodesStatement = <<<SQL
            SELECT
                COUNT(*)
            FROM
                {$this->tableNames->node()}
        SQL;
        try {
            return (int)$this->dbal->fetchOne($countNodesStatement);
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to count rows in database: %s', $e->getMessage()), 1701444590, $e);
        }
    }

    private function getBasicWorkspaceQuery(): QueryBuilder
    {
        $queryBuilder = $this->dbal->createQueryBuilder();

        return $queryBuilder
            ->select('ws.name, ws.baseWorkspaceName, ws.currentContentStreamId, cs.hasChanges, cs.sourceContentStreamVersion = scs.version as upToDateWithBase, ws.version')
            ->from($this->tableNames->workspace(), 'ws')
            ->join('ws', $this->tableNames->contentStream(), 'cs', 'cs.id = ws.currentcontentstreamid')
            ->leftJoin('cs', $this->tableNames->contentStream(), 'scs', 'scs.id = cs.sourceContentStreamId');
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function workspaceFromDatabaseRow(array $row): Workspace
    {
        $baseWorkspaceName = $row['baseWorkspaceName'] !== null ? WorkspaceName::fromString($row['baseWorkspaceName']) : null;

        if ($baseWorkspaceName === null) {
            // no base workspace, a root is always up-to-date
            $status = WorkspaceStatus::UP_TO_DATE;
        } elseif ($row['upToDateWithBase'] === 1) {
            // base workspace didnt change
            $status = WorkspaceStatus::UP_TO_DATE;
        } else {
            // base content stream was removed or contains newer changes
            $status = WorkspaceStatus::OUTDATED;
        }

        return Workspace::create(
            WorkspaceName::fromString($row['name']),
            $baseWorkspaceName,
            ContentStreamId::fromString($row['currentContentStreamId']),
            $status,
            $baseWorkspaceName === null
                ? false
                : (bool)$row['hasChanges'],
            Version::fromInteger((int)$row['version']),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function contentStreamFromDatabaseRow(array $row): ContentStream
    {
        return ContentStream::create(
            ContentStreamId::fromString($row['id']),
            isset($row['sourceContentStreamId']) ? ContentStreamId::fromString($row['sourceContentStreamId']) : null,
            Version::fromInteger((int)$row['version']),
            (bool)$row['closed'],
        );
    }
}

<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
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
final readonly class ContentHyperGraphReadModelAdapter implements ContentGraphReadModelInterface
{

    private ContentGraphTableNames $tableNames;

    public function __construct(
        private Connection $dbal,
        private NodeFactory $nodeFactory,
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager
    ) {
        $this->tableNames = ContentGraphTableNames::create($this->contentRepositoryId);
    }

    public function getContentGraph(WorkspaceName $workspaceName): ContentGraphInterface
    {
        $contentStreamId = $this->findWorkspaceByName($workspaceName)?->currentContentStreamId;
        if ($contentStreamId === null) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }
        return new ContentHyperGraph(
            $this->dbal,
            $this->nodeFactory,
            $this->contentRepositoryId,
            $this->nodeTypeManager,
            $workspaceName,
            $contentStreamId
        );
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        $result = $this->dbal->executeQuery(
            <<<SQL
                select
                    ws.name,
                    ws.baseworkspacename,
                    ws.currentcontentstreamid,
                    cs.haschanges,
                    cs.sourcecontentstreamversion = scs.version as uptodatewithbase
                from {$this->tableNames->workspace()} ws
                    left join {$this->tableNames->contentStream()} cs
                        on cs.id = ws.currentcontentstreamid
                    left join {$this->tableNames->contentStream()} scs
                        on scs.id = cs.sourcecontentstreamid
                where ws.name = :workspace_name
                limit 1;
            SQL,
            [
                'workspace_name' => $workspaceName->value
            ]
        );
        $row = $result->fetchAssociative();
        if ($row === false) {
            return null;
        }
        return self::workspaceFromDatabaseRow($row);
    }

    public function findWorkspaces(): Workspaces
    {
        // TODO: Implement getWorkspaces() method.
        return Workspaces::createEmpty();
    }

    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream
    {
        $result = $this->dbal->executeQuery(
            <<<SQL
                select
                    cs.id,
                    cs.sourcecontentstreamid,
                    cs.version,
                    cs.isclosed
                from {$this->tableNames->contentStream()} cs
                where cs.id = :contentstream_id
                limit 1;
            SQL,
            [
                'contentstream_id' => $contentStreamId->value
            ]
        );
        $row = $result->fetchAssociative();
        if ($row === false) {
            return null;
        }
        return self::contentStreamFromDatabaseRow($row);
    }

    public function countNodes(): int
    {
        // TODO: Implement countNodes method.
        return 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function workspaceFromDatabaseRow(array $row): Workspace
    {
        $baseWorkspaceName = $row['baseworkspacename'] !== null ? WorkspaceName::fromString($row['baseworkspacename']) : null;

        if ($baseWorkspaceName === null) {
            // no base workspace, a root is always up-to-date
            $status = WorkspaceStatus::UP_TO_DATE;
        } elseif ($row['uptodatewithbase'] === 1) {
            // base workspace didnt change
            $status = WorkspaceStatus::UP_TO_DATE;
        } else {
            // base content stream was removed or contains newer changes
            $status = WorkspaceStatus::OUTDATED;
        }

        return Workspace::create(
            WorkspaceName::fromString($row['name']),
            $baseWorkspaceName,
            ContentStreamId::fromString($row['currentcontentstreamid']),
            $status,
            !($baseWorkspaceName === null) && (bool)$row['haschanges'],
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function contentStreamFromDatabaseRow(array $row): ContentStream
    {
        return ContentStream::create(
            ContentStreamId::fromString($row['id']),
            isset($row['sourcecontentstreamid']) ? ContentStreamId::fromString($row['sourceContentStreamId']) : null,
            Version::fromInteger((int)$row['version']),
            (bool)$row['isclosed'],
        );
    }

}

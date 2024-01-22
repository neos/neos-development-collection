<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\Workspace;

use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;

/**
 * Workspace Finder
 *
 * @api
 */
final class WorkspaceFinder implements ProjectionStateInterface
{
    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly WorkspaceRuntimeCache $workspaceRuntimeCache,
        private readonly string $tableName
    ) {
    }


    public function findOneByName(WorkspaceName $name): ?Workspace
    {
        $workspace = $this->workspaceRuntimeCache->getWorkspaceByName($name);
        if ($workspace !== null) {
            return $workspace;
        }

        $connection = $this->client->getConnection();
        $workspaceRow = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE workspaceName = :workspaceName
            ',
            [
                'workspaceName' => $name->value,
            ]
        )->fetchAssociative();

        if ($workspaceRow === false) {
            return null;
        }

        $workspace = $this->createWorkspaceFromDatabaseRow($workspaceRow);
        $this->workspaceRuntimeCache->setWorkspace($workspace);
        return $workspace;
    }

    public function findOneByCurrentContentStreamId(
        ContentStreamId $contentStreamId
    ): ?Workspace {
        $workspace = $this->workspaceRuntimeCache->getByCurrentContentStreamId($contentStreamId);
        if ($workspace !== null) {
            return $workspace;
        }

        $connection = $this->client->getConnection();
        $workspaceRow = $connection->executeQuery(
            '
            SELECT * FROM ' . $this->tableName . '
            WHERE currentContentStreamId = :currentContentStreamId
        ',
            [
                'currentContentStreamId' => $contentStreamId->value
            ]
        )->fetchAssociative();

        if ($workspaceRow === false) {
            return null;
        }

        $workspace = $this->createWorkspaceFromDatabaseRow($workspaceRow);
        $this->workspaceRuntimeCache->setWorkspace($workspace);
        return $workspace;
    }

    /**
     * @return array<string,Workspace>
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findByBaseWorkspace(WorkspaceName $baseWorkspace): array
    {
        $result = [];

        $connection = $this->client->getConnection();
        $workspaceRows = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE baseWorkspaceName = :workspaceName
            ',
            [
                'workspaceName' => $baseWorkspace->value,
            ]
        )->fetchAllAssociative();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = $this->createWorkspaceFromDatabaseRow($workspaceRow);
            $result[$similarlyNamedWorkspace->workspaceName->value] = $similarlyNamedWorkspace;
        }

        return $result;
    }

    public function findOneByWorkspaceOwner(string $owner): ?Workspace
    {
        $connection = $this->client->getConnection();
        $workspaceRow = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE workspaceOwner = :workspaceOwner
            ',
            [
                'workspaceOwner' => $owner
            ]
        )->fetchAssociative();

        if ($workspaceRow === false) {
            return null;
        }

        return $this->createWorkspaceFromDatabaseRow($workspaceRow);
    }

    public function findAll(): Workspaces
    {
        $result = [];

        $connection = $this->client->getConnection();
        $workspaceRows = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
            '
        )->fetchAllAssociative();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = $this->createWorkspaceFromDatabaseRow($workspaceRow);
            $result[$similarlyNamedWorkspace->workspaceName->value] = $similarlyNamedWorkspace;
        }

        return Workspaces::fromArray($result);
    }

    /**
     * @return array<string,Workspace>
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findOutdated(): array
    {
        $result = [];

        $connection = $this->client->getConnection();
        $workspaceRows = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . ' WHERE status = :outdated
            ',
            [
                'outdated' => WorkspaceStatus::OUTDATED->value
            ]
        )->fetchAllAssociative();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = $this->createWorkspaceFromDatabaseRow($workspaceRow);
            $result[$similarlyNamedWorkspace->workspaceName->value] = $similarlyNamedWorkspace;
        }

        return $result;
    }

    /**
     * @param array<string,string> $row
     */
    private function createWorkspaceFromDatabaseRow(array $row): Workspace
    {
        return new Workspace(
            WorkspaceName::fromString($row['workspacename']),
            !empty($row['baseworkspacename']) ? WorkspaceName::fromString($row['baseworkspacename']) : null,
            WorkspaceTitle::fromString($row['workspacetitle']),
            WorkspaceDescription::fromString($row['workspacedescription']),
            ContentStreamId::fromString($row['currentcontentstreamid']),
            WorkspaceStatus::from($row['status']),
            $row['workspaceowner']
        );
    }
}

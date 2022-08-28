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

namespace Neos\ContentRepository\Projection\Workspace;

use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\ProjectionStateInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;

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
                ':workspaceName' => (string)$name
            ]
        )->fetchAssociative();

        if ($workspaceRow === false) {
            return null;
        }

        $workspace = $this->createWorkspaceFromDatabaseRow($workspaceRow);
        $this->workspaceRuntimeCache->setWorkspace($workspace);
        return $workspace;
    }

    public function findOneByCurrentContentStreamIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier
    ): ?Workspace {
        $workspace = $this->workspaceRuntimeCache->getByCurrentContentStreamIdentifier($contentStreamIdentifier);
        if ($workspace !== null) {
            return $workspace;
        }

        $connection = $this->client->getConnection();
        $workspaceRow = $connection->executeQuery(
            '
            SELECT * FROM ' . $this->tableName . '
            WHERE currentContentStreamIdentifier = :currentContentStreamIdentifier
        ',
            [
                ':currentContentStreamIdentifier' => (string)$contentStreamIdentifier
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
     */
    public function findByPrefix(WorkspaceName $prefix): array
    {
        $result = [];

        $connection = $this->client->getConnection();
        $workspaceRows = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE workspaceName LIKE :workspaceNameLike
            ',
            [
                ':workspaceNameLike' => $prefix . '%'
            ]
        )->fetchAllAssociative();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = $this->createWorkspaceFromDatabaseRow($workspaceRow);
            $result[(string)$similarlyNamedWorkspace->workspaceName] = $similarlyNamedWorkspace;
        }

        return $result;
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
                ':workspaceName' => (string)$baseWorkspace
            ]
        )->fetchAllAssociative();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = $this->createWorkspaceFromDatabaseRow($workspaceRow);
            $result[(string)$similarlyNamedWorkspace->workspaceName] = $similarlyNamedWorkspace;
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
                ':workspaceOwner' => $owner
            ]
        )->fetchAssociative();

        if ($workspaceRow === false) {
            return null;
        }

        return $this->createWorkspaceFromDatabaseRow($workspaceRow);
    }

    /**
     * @return array<string,Workspace>
     */
    public function findAll(): array
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
            $result[(string)$similarlyNamedWorkspace->workspaceName] = $similarlyNamedWorkspace;
        }

        return $result;
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
            $result[(string)$similarlyNamedWorkspace->workspaceName] = $similarlyNamedWorkspace;
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
            !empty($row['workspacetitle']) ? WorkspaceTitle::fromString($row['workspacetitle']) : null,
            WorkspaceDescription::fromString($row['workspacedescription']),
            ContentStreamIdentifier::fromString($row['currentcontentstreamidentifier']),
            WorkspaceStatus::from($row['status']),
            $row['workspaceowner']
        );
    }
}

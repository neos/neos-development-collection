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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;

/**
 * The legacy Workspace Finder
 *
 * @deprecated with 9.0.0-beta14 please use {@see ContentRepository::getWorkspaces()} and {@see ContentRepository::findWorkspaceByName()} instead.
 * @internal
 */
final class WorkspaceFinder implements ProjectionStateInterface
{
    public function __construct(
        private readonly Connection $dbal,
        private readonly WorkspaceRuntimeCache $workspaceRuntimeCache,
        private readonly string $tableName
    ) {
    }

    /**
     * @deprecated with 9.0.0-beta14 please use {@see ContentRepository::findWorkspaceByName()} instead
     */
    public function findOneByName(WorkspaceName $name): ?Workspace
    {
        $workspace = $this->workspaceRuntimeCache->getWorkspaceByName($name);
        if ($workspace !== null) {
            return $workspace;
        }

        $workspaceRow = $this->dbal->executeQuery(
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

    /**
     * @deprecated with 9.0.0-beta14 discouraged. You should just operate on workspace names instead.
     * To still archive the functionality please use {@see ContentRepository::getWorkspaces()} instead and filter the result:
     *
     *     $this->contentRepository->getWorkspaces()->find(
     *         fn (Workspace $workspace) => $workspace->currentContentStreamId->equals($contentStreamId)
     *     )
     *
     */
    public function findOneByCurrentContentStreamId(
        ContentStreamId $contentStreamId
    ): ?Workspace {
        $workspace = $this->workspaceRuntimeCache->getByCurrentContentStreamId($contentStreamId);
        if ($workspace !== null) {
            return $workspace;
        }

        $workspaceRow = $this->dbal->executeQuery(
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
     * @deprecated with 9.0.0-beta14 please use {@see ContentRepository::getWorkspaces()} and {@see Workspaces::getBaseWorkspaces()} instead.
     * @return array<string,Workspace>
     * @throws DBALException
     */
    public function findByBaseWorkspace(WorkspaceName $baseWorkspace): array
    {
        $result = [];

        $workspaceRows = $this->dbal->executeQuery(
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

    /**
     * @deprecated with 9.0.0-beta14 owners/collaborators should be assigned to workspaces outside the Content Repository core
     */
    public function findOneByWorkspaceOwner(string $owner): ?Workspace
    {
        $workspaceRow = $this->dbal->executeQuery(
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

    /**
     * @deprecated with 9.0.0-beta14 please use {@see ContentRepository::getWorkspaces()} instead
     */
    public function findAll(): Workspaces
    {
        $result = [];

        $workspaceRows = $this->dbal->executeQuery(
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
     * @deprecated with 9.0.0-beta14 please use {@see ContentRepository::getWorkspaces()} instead and filter the result:
     *
     *     $this->contentRepository->getWorkspaces()->filter(
     *         fn (Workspace $workspace) => $workspace->status === WorkspaceStatus::OUTDATED
     *     )
     *
     * @return array<string,Workspace>
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     */
    public function findOutdated(): array
    {
        $result = [];

        $workspaceRows = $this->dbal->executeQuery(
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

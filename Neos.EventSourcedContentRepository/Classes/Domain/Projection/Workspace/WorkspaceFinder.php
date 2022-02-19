<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Projection\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\Flow\Annotations as Flow;

/**
 * Workspace Finder
 * @Flow\Scope("singleton")
 */
final class WorkspaceFinder
{

    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     * @var bool
     */
    private $cacheEnabled = true;

    /**
     * @var array
     */
    private $cachedWorkspacesByName = [];

    /**
     * @var array
     */
    private $cachedWorkspacesByContentStreamIdentifier = [];

    public function disableCache()
    {
        $this->cacheEnabled = false;
        $this->cachedWorkspacesByName = [];
        $this->cachedWorkspacesByContentStreamIdentifier = [];
    }

    public function enableCache()
    {
        $this->cacheEnabled = true;
        $this->cachedWorkspacesByName = [];
        $this->cachedWorkspacesByContentStreamIdentifier = [];
    }

    /**
     * @param WorkspaceName $name
     * @return Workspace|null
     */
    public function findOneByName(WorkspaceName $name): ?Workspace
    {
        if ($this->cacheEnabled === true && isset($this->cachedWorkspacesByName[(string)$name])) {
            return $this->cachedWorkspacesByName[(string)$name];
        }

        $connection = $this->client->getConnection();
        $workspaceRow = $connection->executeQuery(
            '
            SELECT * FROM neos_contentrepository_projection_workspace_v1
            WHERE workspaceName = :workspaceName
        ',
            [
                ':workspaceName' => (string)$name
            ]
        )->fetch();

        if ($workspaceRow === false) {
            return null;
        }

        $workspace = Workspace::fromDatabaseRow($workspaceRow);

        if ($this->cacheEnabled === true) {
            $this->cachedWorkspacesByName[(string)$name] = $workspace;
        }

        return $workspace;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return Workspace|null
     */
    public function findOneByCurrentContentStreamIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier
    ): ?Workspace {
        if ($this->cacheEnabled
            && isset($this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier])) {
            return $this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier];
        }

        $connection = $this->client->getConnection();
        $workspaceRow = $connection->executeQuery(
            '
            SELECT * FROM neos_contentrepository_projection_workspace_v1
            WHERE currentContentStreamIdentifier = :currentContentStreamIdentifier
        ',
            [
                ':currentContentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetch();

        if ($workspaceRow === false) {
            return null;
        }

        $workspace = Workspace::fromDatabaseRow($workspaceRow);

        if ($this->cacheEnabled === true) {
            $this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier] = $workspace;
        }

        return $workspace;
    }

    /**
     * @param WorkspaceName $prefix
     * @return array|Workspace[]
     * @throws \Neos\Flow\Persistence\Exception\InvalidQueryException
     */
    public function findByPrefix(WorkspaceName $prefix): array
    {
        $result = [];

        $connection = $this->client->getConnection();
        $workspaceRows = $connection->executeQuery(
            '
                SELECT * FROM neos_contentrepository_projection_workspace_v1
                WHERE workspaceName LIKE :workspaceNameLike
            ',
            [
                ':workspaceNameLike' => (string)$prefix . '%'
            ]
        )->fetchAll();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = Workspace::fromDatabaseRow($workspaceRow);
            /** @var Workspace $similarlyNamedWorkspace */
            $result[(string)$similarlyNamedWorkspace->getWorkspaceName()] = $similarlyNamedWorkspace;
        }

        return $result;
    }

    /**
     * @param WorkspaceName $baseWorkspace
     * @return array|Workspace[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findByBaseWorkspace(WorkspaceName $baseWorkspace): array
    {
        $result = [];

        $connection = $this->client->getConnection();
        $workspaceRows = $connection->executeQuery(
            '
                SELECT * FROM neos_contentrepository_projection_workspace_v1
                WHERE baseWorkspaceName = :workspaceName
            ',
            [
                ':workspaceName' => (string)$baseWorkspace
            ]
        )->fetchAll();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = Workspace::fromDatabaseRow($workspaceRow);
            /** @var Workspace $similarlyNamedWorkspace */
            $result[(string)$similarlyNamedWorkspace->getWorkspaceName()] = $similarlyNamedWorkspace;
        }

        return $result;
    }

    public function findOneByWorkspaceOwner(string $owner): ?Workspace
    {
        $connection = $this->client->getConnection();
        $workspaceRow = $connection->executeQuery(
            '
                SELECT * FROM neos_contentrepository_projection_workspace_v1
                WHERE workspaceOwner = :workspaceOwner
            ',
            [
                ':workspaceOwner' => $owner
            ]
        )->fetch();

        if ($workspaceRow === false) {
            return null;
        }

        return Workspace::fromDatabaseRow($workspaceRow);
    }

    /**
     * @return array|Workspace[]
     */
    public function findAll(): array
    {
        $result = [];

        $connection = $this->client->getConnection();
        $workspaceRows = $connection->executeQuery(
            '
                SELECT * FROM neos_contentrepository_projection_workspace_v1
            '
        )->fetchAll();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = Workspace::fromDatabaseRow($workspaceRow);
            /** @var Workspace $similarlyNamedWorkspace */
            $result[(string)$similarlyNamedWorkspace->getWorkspaceName()] = $similarlyNamedWorkspace;
        }

        return $result;
    }

    /**
     * @return Workspace[]
     */
    public function findOutdated(): array
    {
        $result = [];

        $connection = $this->client->getConnection();
        $workspaceRows = $connection->executeQuery(
            '
                SELECT * FROM neos_contentrepository_projection_workspace_v1 WHERE status = :outdated
            ',
            [
                'outdated' => Workspace::STATUS_OUTDATED
            ]
        )->fetchAll();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = Workspace::fromDatabaseRow($workspaceRow);
            /** @var Workspace $similarlyNamedWorkspace */
            $result[(string)$similarlyNamedWorkspace->getWorkspaceName()] = $similarlyNamedWorkspace;
        }

        return $result;
    }
}

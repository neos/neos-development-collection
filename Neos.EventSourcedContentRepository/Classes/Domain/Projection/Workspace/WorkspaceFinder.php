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

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineFinder;
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
     * @var array
     */
    private $cachedWorkspacesByName = [];

    /**
     * @var array
     */
    private $cachedWorkspacesByContentStreamIdentifier = [];

    /**
     * @param WorkspaceName $name
     * @return Workspace|null
     */
    public function findOneByName(WorkspaceName $name): ?Workspace
    {
        // TODO consider re-introducing runtime cache
        #if (!isset($this->cachedWorkspacesByName[(string)$name])) {

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
        $this->cachedWorkspacesByName[(string)$name] = $workspace;
        #}
        return $this->cachedWorkspacesByName[(string)$name];
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return Workspace|null
     */
    public function findOneByCurrentContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): ?Workspace
    {
        // TODO consider re-introducing runtime cache
        #if (!isset($this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier])) {
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
        $this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier] = $workspace;
        #}
        return $this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier];
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
                ':workspaceNameLike' => (string) $prefix . '%'
            ]
        )->fetchAll();

        foreach ($workspaceRows as $workspaceRow) {
            $similarlyNamedWorkspace = Workspace::fromDatabaseRow($workspaceRow);
            /** @var Workspace $similarlyNamedWorkspace */
            $result[(string) $similarlyNamedWorkspace->getWorkspaceName()] = $similarlyNamedWorkspace;
        }

        return $result;
    }



    // TODO consider re-introducing runtime cache
//    public function resetCache()
//    {
//        $this->cachedWorkspacesByName = [];
//        $this->cachedWorkspacesByContentStreamIdentifier = [];
//    }
}

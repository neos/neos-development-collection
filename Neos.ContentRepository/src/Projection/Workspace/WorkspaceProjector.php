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

use Doctrine\DBAL\Connection;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Infrastructure\Projection\AbstractProcessedEventsAwareProjector;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class WorkspaceProjector extends AbstractProcessedEventsAwareProjector
{
    private const TABLE_NAME = 'neos_contentrepository_projection_workspace_v1';

    private DbalClientInterface $databaseClient;

    public function __construct(
        DbalClientInterface $eventStorageDatabaseClient,
        VariableFrontend $processedEventsCache
    ) {
        $this->databaseClient = $eventStorageDatabaseClient;
        parent::__construct($eventStorageDatabaseClient, $processedEventsCache);
    }

    public function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        $this->getDatabaseConnection()->insert(self::TABLE_NAME, [
            'workspaceName' => $event->getWorkspaceName(),
            'baseWorkspaceName' => $event->getBaseWorkspaceName(),
            'workspaceTitle' => $event->getWorkspaceTitle(),
            'workspaceDescription' => $event->getWorkspaceDescription(),
            'workspaceOwner' => $event->getWorkspaceOwner(),
            'currentContentStreamIdentifier' => $event->getNewContentStreamIdentifier(),
            'status' => Workspace::STATUS_UP_TO_DATE
        ]);
    }

    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->getDatabaseConnection()->insert(self::TABLE_NAME, [
            'workspaceName' => $event->getWorkspaceName(),
            'workspaceTitle' => $event->getWorkspaceTitle(),
            'workspaceDescription' => $event->getWorkspaceDescription(),
            'currentContentStreamIdentifier' => $event->getNewContentStreamIdentifier(),
            'status' => Workspace::STATUS_UP_TO_DATE
        ]);
    }

    public function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->updateContentStreamIdentifier($event->getNewContentStreamIdentifier(), $event->getWorkspaceName());
        $this->markDependentWorkspacesAsOutdated($event->getWorkspaceName());
    }

    public function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        $this->updateContentStreamIdentifier($event->getNewContentStreamIdentifier(), $event->getWorkspaceName());
        $this->markDependentWorkspacesAsOutdated($event->getWorkspaceName());
    }

    public function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        // TODO: How do we test this method?
        // It's hard to design a BDD testcase failing if this method is commented out...
        $this->updateContentStreamIdentifier(
            $event->getNewSourceContentStreamIdentifier(),
            $event->getSourceWorkspaceName()
        );

        $this->markDependentWorkspacesAsOutdated($event->getTargetWorkspaceName());

        // NASTY: we need to set the source workspace name as non-outdated; as it has been made up-to-date again.
        $this->markWorkspaceAsUpToDate($event->getSourceWorkspaceName());

        $this->markDependentWorkspacesAsOutdated($event->getSourceWorkspaceName());
    }

    public function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        // TODO: How do we test this method?
        // It's hard to design a BDD testcase failing if this method is commented out...
        $this->updateContentStreamIdentifier(
            $event->getNewSourceContentStreamIdentifier(),
            $event->getSourceWorkspaceName()
        );

        $this->markDependentWorkspacesAsOutdated($event->getTargetWorkspaceName());

        // NASTY: we need to set the source workspace name as non-outdated; as it has been made up-to-date again.
        $this->markWorkspaceAsUpToDate($event->getSourceWorkspaceName());

        $this->markDependentWorkspacesAsOutdated($event->getSourceWorkspaceName());
    }

    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->updateContentStreamIdentifier($event->getNewContentStreamIdentifier(), $event->getWorkspaceName());
        $this->markDependentWorkspacesAsOutdated($event->getWorkspaceName());

        // When the rebase is successful, we can set the status of the workspace back to UP_TO_DATE.
        $this->markWorkspaceAsUpToDate($event->getWorkspaceName());
    }

    public function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        $this->markWorkspaceAsOutdatedConflict($event->getWorkspaceName());
    }

    private function updateContentStreamIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        WorkspaceName $workspaceName
    ): void {
        $this->getDatabaseConnection()->update(self::TABLE_NAME, [
            'currentContentStreamIdentifier' => $contentStreamIdentifier
        ], [
            'workspaceName' => $workspaceName
        ]);
    }

    private function markWorkspaceAsUpToDate(WorkspaceName $workspaceName): void
    {
        $this->getDatabaseConnection()->executeUpdate('
            UPDATE neos_contentrepository_projection_workspace_v1
            SET status = :upToDate
            WHERE
                workspacename = :workspaceName
        ', [
            'upToDate' => Workspace::STATUS_UP_TO_DATE,
            'workspaceName' => $workspaceName->jsonSerialize()
        ]);
    }

    private function markDependentWorkspacesAsOutdated(WorkspaceName $baseWorkspaceName): void
    {
        $this->getDatabaseConnection()->executeUpdate('
            UPDATE neos_contentrepository_projection_workspace_v1
            SET status = :outdated
            WHERE
                baseworkspacename = :baseWorkspaceName
        ', [
            'outdated' => Workspace::STATUS_OUTDATED,
            'baseWorkspaceName' => $baseWorkspaceName->jsonSerialize()
        ]);
    }

    private function markWorkspaceAsOutdatedConflict(WorkspaceName $workspaceName): void
    {
        $this->getDatabaseConnection()->executeUpdate('
            UPDATE neos_contentrepository_projection_workspace_v1
            SET
                status = :outdatedConflict,
                foo = bar
            WHERE
                workspacename = :workspaceName
        ', [
            'outdatedConflict' => Workspace::STATUS_OUTDATED_CONFLICT,
            'workspaceName' => $workspaceName->jsonSerialize()
        ]);
    }

    public function reset(): void
    {
        parent::reset();
        $this->getDatabaseConnection()->executeStatement('TRUNCATE ' . self::TABLE_NAME);
    }

    /**
     * @throws \Throwable
     */
    protected function transactional(\Closure $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->databaseClient->getConnection();
    }
}

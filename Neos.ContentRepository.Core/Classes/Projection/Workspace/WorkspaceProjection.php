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
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceBaseWorkspaceWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceOwnerWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRenamed;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @internal
 * @implements ProjectionInterface<WorkspaceFinder>
 */
class WorkspaceProjection implements ProjectionInterface, WithMarkStaleInterface
{
    /**
     * @var WorkspaceFinder|null Cache for the workspace finder returned by {@see getState()},
     * so that always the same instance is returned
     */
    private ?WorkspaceFinder $workspaceFinder = null;
    private DoctrineCheckpointStorage $checkpointStorage;
    private WorkspaceRuntimeCache $workspaceRuntimeCache;

    public function __construct(
        private readonly DbalClientInterface $dbalClient,
        private readonly string $tableName,
    ) {
        $this->checkpointStorage = new DoctrineCheckpointStorage(
            $this->dbalClient->getConnection(),
            $this->tableName . '_checkpoint',
            self::class
        );
        $this->workspaceRuntimeCache = new WorkspaceRuntimeCache();
    }

    public function setUp(): void
    {
        $this->setupTables();
        $this->checkpointStorage->setup();
    }

    private function setupTables(): void
    {
        $connection = $this->dbalClient->getConnection();
        $schemaManager = $connection->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }

        $schema = new Schema();
        $workspaceTable = $schema->createTable($this->tableName);
        $workspaceTable->addColumn('workspacename', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $workspaceTable->addColumn('baseworkspacename', Types::STRING)
            ->setLength(255)
            ->setNotnull(false);
        $workspaceTable->addColumn('workspacetitle', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $workspaceTable->addColumn('workspacedescription', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $workspaceTable->addColumn('workspaceowner', Types::STRING)
            ->setLength(255)
            ->setNotnull(false);
        $workspaceTable->addColumn('currentcontentstreamid', Types::STRING)
            ->setLength(40)
            ->setNotnull(false);
        $workspaceTable->addColumn('status', Types::STRING)
            ->setLength(50)
            ->setNotnull(false);

        $workspaceTable->setPrimaryKey(['workspacename']);

        $schemaDiff = (new Comparator())->compare($schemaManager->createSchema(), $schema);
        foreach ($schemaDiff->toSaveSql($connection->getDatabasePlatform()) as $statement) {
            $connection->executeStatement($statement);
        }
    }

    public function reset(): void
    {
        $this->getDatabaseConnection()->exec('TRUNCATE ' . $this->tableName);
        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
    }

    public function canHandle(EventInterface $event): bool
    {
        return in_array($event::class, [
            WorkspaceWasCreated::class,
            WorkspaceWasRenamed::class,
            RootWorkspaceWasCreated::class,
            WorkspaceWasDiscarded::class,
            WorkspaceWasPartiallyDiscarded::class,
            WorkspaceWasPartiallyPublished::class,
            WorkspaceWasPublished::class,
            WorkspaceWasRebased::class,
            WorkspaceRebaseFailed::class,
            WorkspaceWasRemoved::class,
            WorkspaceOwnerWasChanged::class,
            WorkspaceBaseWorkspaceWasChanged::class,
        ]);
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            WorkspaceWasCreated::class => $this->whenWorkspaceWasCreated($event),
            WorkspaceWasRenamed::class => $this->whenWorkspaceWasRenamed($event),
            RootWorkspaceWasCreated::class => $this->whenRootWorkspaceWasCreated($event),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($event),
            WorkspaceWasPartiallyDiscarded::class => $this->whenWorkspaceWasPartiallyDiscarded($event),
            WorkspaceWasPartiallyPublished::class => $this->whenWorkspaceWasPartiallyPublished($event),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($event),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($event),
            WorkspaceRebaseFailed::class => $this->whenWorkspaceRebaseFailed($event),
            WorkspaceWasRemoved::class => $this->whenWorkspaceWasRemoved($event),
            WorkspaceOwnerWasChanged::class => $this->whenWorkspaceOwnerWasChanged($event),
            WorkspaceBaseWorkspaceWasChanged::class => $this->whenWorkspaceBaseWorkspaceWasChanged($event),
            default => throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
        };
    }

    public function getCheckpointStorage(): CheckpointStorageInterface
    {
        return $this->checkpointStorage;
    }

    public function getState(): WorkspaceFinder
    {
        if (!$this->workspaceFinder) {
            $this->workspaceFinder = new WorkspaceFinder(
                $this->dbalClient,
                $this->workspaceRuntimeCache,
                $this->tableName
            );
        }
        return $this->workspaceFinder;
    }

    public function markStale(): void
    {
        $this->workspaceRuntimeCache->disableCache();
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        $this->getDatabaseConnection()->insert($this->tableName, [
            'workspaceName' => $event->workspaceName->value,
            'baseWorkspaceName' => $event->baseWorkspaceName->value,
            'workspaceTitle' => $event->workspaceTitle->value,
            'workspaceDescription' => $event->workspaceDescription->value,
            'workspaceOwner' => $event->workspaceOwner?->value,
            'currentContentStreamId' => $event->newContentStreamId->value,
            'status' => WorkspaceStatus::UP_TO_DATE->value
        ]);
    }

    private function whenWorkspaceWasRenamed(WorkspaceWasRenamed $event): void
    {
        $this->getDatabaseConnection()->update(
            $this->tableName,
            [
                'workspaceTitle' => $event->workspaceTitle->value,
                'workspaceDescription' => $event->workspaceDescription->value,
            ],
            ['workspaceName' => $event->workspaceName->value]
        );
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->getDatabaseConnection()->insert($this->tableName, [
            'workspaceName' => $event->workspaceName->value,
            'workspaceTitle' => $event->workspaceTitle->value,
            'workspaceDescription' => $event->workspaceDescription->value,
            'currentContentStreamId' => $event->newContentStreamId->value,
            'status' => WorkspaceStatus::UP_TO_DATE->value
        ]);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
        $this->markDependentWorkspacesAsOutdated($event->workspaceName);
    }

    private function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
        $this->markDependentWorkspacesAsOutdated($event->workspaceName);
    }

    private function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        // TODO: How do we test this method?
        // It's hard to design a BDD testcase failing if this method is commented out...
        $this->updateContentStreamId(
            $event->newSourceContentStreamId,
            $event->sourceWorkspaceName
        );

        $this->markDependentWorkspacesAsOutdated($event->targetWorkspaceName);

        // NASTY: we need to set the source workspace name as non-outdated; as it has been made up-to-date again.
        $this->markWorkspaceAsUpToDate($event->sourceWorkspaceName);

        $this->markDependentWorkspacesAsOutdated($event->sourceWorkspaceName);
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        // TODO: How do we test this method?
        // It's hard to design a BDD testcase failing if this method is commented out...
        $this->updateContentStreamId(
            $event->newSourceContentStreamId,
            $event->sourceWorkspaceName
        );

        $this->markDependentWorkspacesAsOutdated($event->targetWorkspaceName);

        // NASTY: we need to set the source workspace name as non-outdated; as it has been made up-to-date again.
        $this->markWorkspaceAsUpToDate($event->sourceWorkspaceName);

        $this->markDependentWorkspacesAsOutdated($event->sourceWorkspaceName);
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->updateContentStreamId($event->newContentStreamId, $event->workspaceName);
        $this->markDependentWorkspacesAsOutdated($event->workspaceName);

        // When the rebase is successful, we can set the status of the workspace back to UP_TO_DATE.
        $this->markWorkspaceAsUpToDate($event->workspaceName);
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        $this->markWorkspaceAsOutdatedConflict($event->workspaceName);
    }

    private function whenWorkspaceWasRemoved(WorkspaceWasRemoved $event): void
    {
        $this->getDatabaseConnection()->delete(
            $this->tableName,
            ['workspaceName' => $event->workspaceName->value]
        );
    }

    private function whenWorkspaceOwnerWasChanged(WorkspaceOwnerWasChanged $event): void
    {
        $this->getDatabaseConnection()->update(
            $this->tableName,
            ['workspaceOwner' => $event->newWorkspaceOwner],
            ['workspaceName' => $event->workspaceName->value]
        );
    }

    private function whenWorkspaceBaseWorkspaceWasChanged(WorkspaceBaseWorkspaceWasChanged $event): void
    {
        $this->getDatabaseConnection()->update(
            $this->tableName,
            [
                'baseWorkspaceName' => $event->baseWorkspaceName->value,
                'currentContentStreamId' => $event->newContentStreamId->value,
            ],
            ['workspaceName' => $event->workspaceName->value]
        );
    }

    private function updateContentStreamId(
        ContentStreamId $contentStreamId,
        WorkspaceName $workspaceName,
    ): void {
        $this->getDatabaseConnection()->update($this->tableName, [
            'currentContentStreamId' => $contentStreamId->value,
        ], [
            'workspaceName' => $workspaceName->value
        ]);
    }

    private function markWorkspaceAsUpToDate(WorkspaceName $workspaceName): void
    {
        $this->getDatabaseConnection()->executeUpdate('
            UPDATE ' . $this->tableName . '
            SET status = :upToDate
            WHERE
                workspacename = :workspaceName
        ', [
            'upToDate' => WorkspaceStatus::UP_TO_DATE->value,
            'workspaceName' => $workspaceName->value
        ]);
    }

    private function markDependentWorkspacesAsOutdated(WorkspaceName $baseWorkspaceName): void
    {
        $this->getDatabaseConnection()->executeUpdate('
            UPDATE ' . $this->tableName . '
            SET status = :outdated
            WHERE
                baseworkspacename = :baseWorkspaceName
        ', [
            'outdated' => WorkspaceStatus::OUTDATED->value,
            'baseWorkspaceName' => $baseWorkspaceName->value
        ]);
    }

    private function markWorkspaceAsOutdatedConflict(WorkspaceName $workspaceName): void
    {
        $this->getDatabaseConnection()->executeUpdate('
            UPDATE ' . $this->tableName . '
            SET
                status = :outdatedConflict
            WHERE
                workspacename = :workspaceName
        ', [
            'outdatedConflict' => WorkspaceStatus::OUTDATED_CONFLICT->value,
            'workspaceName' => $workspaceName->value
        ]);
    }

    private function getDatabaseConnection(): Connection
    {
        return $this->dbalClient->getConnection();
    }
}

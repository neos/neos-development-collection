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

namespace Neos\ContentRepository\Core\Projection\ContentStream;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Infrastructure\DbalCheckpointStorage;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;
use Neos\ContentRepository\Core\Projection\CheckpointStorageInterface;
use Neos\ContentRepository\Core\Projection\CheckpointStorageStatusType;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamState;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * See {@see ContentStreamFinder} for explanation.
 *
 * @internal
 * @implements ProjectionInterface<ContentStreamFinder>
 */
class ContentStreamProjection implements ProjectionInterface
{
    /**
     * @var ContentStreamFinder|null Cache for the content stream finder returned by {@see getState()},
     * so that always the same instance is returned
     */
    private ?ContentStreamFinder $contentStreamFinder = null;
    private DbalCheckpointStorage $checkpointStorage;

    public function __construct(
        private readonly Connection $dbal,
        private readonly string $tableName
    ) {
        $this->checkpointStorage = new DbalCheckpointStorage(
            $this->dbal,
            $this->tableName . '_checkpoint',
            self::class
        );
    }

    public function setUp(): void
    {
        $statements = $this->determineRequiredSqlStatements();
        // MIGRATIONS
        if ($this->dbal->getSchemaManager()->tablesExist([$this->tableName])) {
            // added 2023-04-01
            $statements[] = sprintf("UPDATE %s SET state='FORKED' WHERE state='REBASING'; ", $this->tableName);
        }
        foreach ($statements as $statement) {
            $this->dbal->executeStatement($statement);
        }
        $this->checkpointStorage->setUp();
    }

    public function status(): ProjectionStatus
    {
        $checkpointStorageStatus = $this->checkpointStorage->status();
        if ($checkpointStorageStatus->type === CheckpointStorageStatusType::ERROR) {
            return ProjectionStatus::error($checkpointStorageStatus->details);
        }
        if ($checkpointStorageStatus->type === CheckpointStorageStatusType::SETUP_REQUIRED) {
            return ProjectionStatus::setupRequired($checkpointStorageStatus->details);
        }
        try {
            $this->dbal->connect();
        } catch (\Throwable $e) {
            return ProjectionStatus::error(sprintf('Failed to connect to database: %s', $e->getMessage()));
        }
        try {
            $requiredSqlStatements = $this->determineRequiredSqlStatements();
        } catch (\Throwable $e) {
            return ProjectionStatus::error(sprintf('Failed to determine required SQL statements: %s', $e->getMessage()));
        }
        if ($requiredSqlStatements !== []) {
            return ProjectionStatus::setupRequired(sprintf('The following SQL statement%s required: %s', count($requiredSqlStatements) !== 1 ? 's are' : ' is', implode(chr(10), $requiredSqlStatements)));
        }
        return ProjectionStatus::ok();
    }

    /**
     * @return array<string>
     */
    private function determineRequiredSqlStatements(): array
    {
        $schemaManager = $this->dbal->createSchemaManager();
        $schema = DbalSchemaFactory::createSchemaWithTables($schemaManager, [
            (new Table($this->tableName, [
                DbalSchemaFactory::columnForContentStreamId('contentStreamId')->setNotnull(true),
                (new Column('version', Type::getType(Types::INTEGER)))->setNotnull(true),
                DbalSchemaFactory::columnForContentStreamId('sourceContentStreamId')->setNotnull(false),
                // Should become a DB ENUM (unclear how to configure with DBAL) or int (latter needs adaption to code)
                (new Column('state', Type::getType(Types::BINARY)))->setLength(20)->setNotnull(true),
                (new Column('removed', Type::getType(Types::BOOLEAN)))->setDefault(false)->setNotnull(false)
            ]))
        ]);

        return DbalSchemaDiff::determineRequiredSqlStatements($this->dbal, $schema);
    }

    public function reset(): void
    {
        $this->dbal->executeStatement('TRUNCATE table ' . $this->tableName);
        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
    }

    public function canHandle(EventInterface $event): bool
    {
        return in_array($event::class, [
                ContentStreamWasCreated::class,
                RootWorkspaceWasCreated::class,
                WorkspaceWasCreated::class,
                ContentStreamWasForked::class,
                WorkspaceWasDiscarded::class,
                WorkspaceWasPartiallyDiscarded::class,
                WorkspaceWasPartiallyPublished::class,
                WorkspaceWasPublished::class,
                WorkspaceWasRebased::class,
                WorkspaceRebaseFailed::class,
                ContentStreamWasClosed::class,
                ContentStreamWasReopened::class,
                ContentStreamWasRemoved::class,
                DimensionShineThroughWasAdded::class,
            ])
            || $event instanceof EmbedsContentStreamId;
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        if ($event instanceof EmbedsContentStreamId) {
            $this->updateContentStreamVersion($event, $eventEnvelope);
        }
        match ($event::class) {
            ContentStreamWasCreated::class => $this->whenContentStreamWasCreated($event, $eventEnvelope),
            RootWorkspaceWasCreated::class => $this->whenRootWorkspaceWasCreated($event),
            WorkspaceWasCreated::class => $this->whenWorkspaceWasCreated($event),
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($event, $eventEnvelope),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($event),
            WorkspaceWasPartiallyDiscarded::class => $this->whenWorkspaceWasPartiallyDiscarded($event),
            WorkspaceWasPartiallyPublished::class => $this->whenWorkspaceWasPartiallyPublished($event),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($event),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($event),
            WorkspaceRebaseFailed::class => $this->whenWorkspaceRebaseFailed($event),
            ContentStreamWasClosed::class => $this->whenContentStreamWasClosed($event, $eventEnvelope),
            ContentStreamWasReopened::class => $this->whenContentStreamWasReopened($event, $eventEnvelope),
            ContentStreamWasRemoved::class => $this->whenContentStreamWasRemoved($event, $eventEnvelope),
            DimensionShineThroughWasAdded::class => $this->whenDimensionShineThroughWasAdded($event, $eventEnvelope),
            default => $event instanceof EmbedsContentStreamId || throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
        };
    }

    public function getCheckpointStorage(): CheckpointStorageInterface
    {
        return $this->checkpointStorage;
    }

    public function getState(): ProjectionStateInterface
    {
        if (!$this->contentStreamFinder) {
            $this->contentStreamFinder = new ContentStreamFinder(
                $this->dbal,
                $this->tableName
            );
        }
        return $this->contentStreamFinder;
    }

    private function whenContentStreamWasCreated(ContentStreamWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->dbal->insert($this->tableName, [
            'contentStreamId' => $event->contentStreamId->value,
            'version' => self::extractVersion($eventEnvelope),
            'state' => ContentStreamState::STATE_CREATED->value,
        ]);
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        // the content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamId,
            ContentStreamState::STATE_IN_USE_BY_WORKSPACE,
        );
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        // the content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamId,
            ContentStreamState::STATE_IN_USE_BY_WORKSPACE,
        );
    }

    private function whenContentStreamWasForked(ContentStreamWasForked $event, EventEnvelope $eventEnvelope): void
    {
        $this->dbal->insert($this->tableName, [
            'contentStreamId' => $event->newContentStreamId->value,
            'version' => self::extractVersion($eventEnvelope),
            'sourceContentStreamId' => $event->sourceContentStreamId->value,
            'state' => ContentStreamState::STATE_FORKED->value
        ]);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamId,
            ContentStreamState::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousContentStreamId,
            ContentStreamState::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamId,
            ContentStreamState::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousContentStreamId,
            ContentStreamState::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newSourceContentStreamId,
            ContentStreamState::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousSourceContentStreamId,
            ContentStreamState::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newSourceContentStreamId,
            ContentStreamState::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousSourceContentStreamId,
            ContentStreamState::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamId,
            ContentStreamState::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousContentStreamId,
            ContentStreamState::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        $this->updateStateForContentStream(
            $event->candidateContentStreamId,
            ContentStreamState::STATE_REBASE_ERROR
        );
    }

    private function whenContentStreamWasClosed(ContentStreamWasClosed $event, EventEnvelope $eventEnvelope): void
    {
        $this->updateStateForContentStream(
            $event->contentStreamId,
            ContentStreamState::STATE_CLOSED,
        );
        $this->dbal->update($this->tableName, [
            'version' => self::extractVersion($eventEnvelope),
        ], [
            'contentStreamId' => $event->contentStreamId->value
        ]);
    }

    private function whenContentStreamWasReopened(ContentStreamWasReopened $event, EventEnvelope $eventEnvelope): void
    {
        $this->updateStateForContentStream(
            $event->contentStreamId,
            $event->previousState,
        );
        $this->dbal->update($this->tableName, [
            'version' => self::extractVersion($eventEnvelope),
        ], [
            'contentStreamId' => $event->contentStreamId->value
        ]);
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event, EventEnvelope $eventEnvelope): void
    {
        $this->dbal->update($this->tableName, [
            'removed' => true,
            'version' => self::extractVersion($eventEnvelope),
        ], [
            'contentStreamId' => $event->contentStreamId->value
        ]);
    }

    private function whenDimensionShineThroughWasAdded(DimensionShineThroughWasAdded $event, EventEnvelope $eventEnvelope): void
    {
        $this->dbal->update($this->tableName, [
            'version' => self::extractVersion($eventEnvelope),
        ], [
            'contentStreamId' => $event->contentStreamId->value
        ]);
    }

    private function updateStateForContentStream(ContentStreamId $contentStreamId, ContentStreamState $state): void
    {
        $this->dbal->update($this->tableName, [
            'state' => $state->value,
        ], [
            'contentStreamId' => $contentStreamId->value
        ]);
    }

    private function updateContentStreamVersion(
        EmbedsContentStreamId $eventInstance,
        EventEnvelope $eventEnvelope
    ): void {
        $this->dbal->update($this->tableName, [
            'version' => self::extractVersion($eventEnvelope),
        ], [
            'contentStreamId' => $eventInstance->getContentStreamId()->value
        ]);
    }


    private static function extractVersion(EventEnvelope $eventEnvelope): int
    {
        if (
            !str_starts_with(
                $eventEnvelope->streamName->value,
                ContentStreamEventStreamName::EVENT_STREAM_NAME_PREFIX
            )
        ) {
            throw new \RuntimeException(
                'Cannot extract version number, as it was projected on wrong stream "'
                . $eventEnvelope->streamName->value . '", but needs to start with '
                . ContentStreamEventStreamName::EVENT_STREAM_NAME_PREFIX
            );
        }
        return $eventEnvelope->version->value;
    }
}

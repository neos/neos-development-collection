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
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
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
    private DoctrineCheckpointStorage $checkpointStorage;

    public function __construct(
        private readonly DbalClientInterface $dbalClient,
        private readonly string $tableName
    ) {
        $this->checkpointStorage = new DoctrineCheckpointStorage(
            $this->dbalClient->getConnection(),
            $this->tableName . '_checkpoint',
            self::class
        );
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

        // MIGRATIONS
        $currentSchema = $schemaManager->createSchema();
        if ($currentSchema->hasTable($this->tableName)) {
            // added 2023-04-01
            $connection->executeStatement(sprintf("UPDATE %s SET state='FORKED' WHERE state='REBASING'; ", $this->tableName));
        }

        $schema = new Schema();
        $contentStreamTable = $schema->createTable($this->tableName);
        $contentStreamTable->addColumn('contentStreamId', Types::STRING)
            ->setLength(40)
            ->setNotnull(true);
        $contentStreamTable->addColumn('version', Types::INTEGER)
            ->setNotnull(true);
        $contentStreamTable->addColumn('sourceContentStreamId', Types::STRING)
            ->setLength(40)
            ->setNotnull(false);
        $contentStreamTable->addColumn('state', Types::STRING)
            ->setLength(20)
            ->setNotnull(true);
        $contentStreamTable->addColumn('removed', Types::BOOLEAN)
            ->setDefault(false)
            ->setNotnull(false);
        $schemaDiff = (new Comparator())->compare($schemaManager->createSchema(), $schema);
        foreach ($schemaDiff->toSaveSql($connection->getDatabasePlatform()) as $statement) {
            $connection->executeStatement($statement);
        }
    }

    public function reset(): void
    {
        $this->getDatabaseConnection()->executeStatement('TRUNCATE table ' . $this->tableName);
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
                ContentStreamWasRemoved::class,
            ])
            || $event instanceof EmbedsContentStreamAndNodeAggregateId;
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        if ($event instanceof EmbedsContentStreamAndNodeAggregateId) {
            $this->updateContentStreamVersion($event, $eventEnvelope);
            return;
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
            ContentStreamWasRemoved::class => $this->whenContentStreamWasRemoved($event, $eventEnvelope),
            default => throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
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
                $this->dbalClient,
                $this->tableName
            );
        }
        return $this->contentStreamFinder;
    }

    private function whenContentStreamWasCreated(ContentStreamWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->getDatabaseConnection()->insert($this->tableName, [
            'contentStreamId' => $event->contentStreamId->value,
            'version' => self::extractVersion($eventEnvelope),
            'state' => ContentStreamFinder::STATE_CREATED,
        ]);
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        // the content stream is in use now
        $this->getDatabaseConnection()->update($this->tableName, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamId' => $event->newContentStreamId->value
        ]);
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        // the content stream is in use now
        $this->getDatabaseConnection()->update($this->tableName, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamId' => $event->newContentStreamId->value
        ]);
    }

    private function whenContentStreamWasForked(ContentStreamWasForked $event, EventEnvelope $eventEnvelope): void
    {
        $this->getDatabaseConnection()->insert($this->tableName, [
            'contentStreamId' => $event->newContentStreamId->value,
            'version' => self::extractVersion($eventEnvelope),
            'sourceContentStreamId' => $event->sourceContentStreamId->value,
            'state' => ContentStreamFinder::STATE_FORKED,
        ]);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamId,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousContentStreamId,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamId,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousContentStreamId,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newSourceContentStreamId,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousSourceContentStreamId,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newSourceContentStreamId,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousSourceContentStreamId,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamId,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousContentStreamId,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        $this->updateStateForContentStream(
            $event->candidateContentStreamId,
            ContentStreamFinder::STATE_REBASE_ERROR
        );
    }

    private function whenContentStreamWasClosed(ContentStreamWasClosed $event): void
    {
        $this->updateStateForContentStream(
            $event->contentStreamId,
            ContentStreamFinder::STATE_CLOSED,
        );
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event, EventEnvelope $eventEnvelope): void
    {
        $this->getDatabaseConnection()->update($this->tableName, [
            'removed' => true,
            'version' => self::extractVersion($eventEnvelope),
        ], [
            'contentStreamId' => $event->contentStreamId->value
        ]);
    }

    private function updateStateForContentStream(ContentStreamId $contentStreamId, string $state): void
    {
        $this->getDatabaseConnection()->update($this->tableName, [
            'state' => $state,
        ], [
            'contentStreamId' => $contentStreamId->value
        ]);
    }

    private function getDatabaseConnection(): Connection
    {
        return $this->dbalClient->getConnection();
    }

    private function updateContentStreamVersion(
        EmbedsContentStreamAndNodeAggregateId $eventInstance,
        EventEnvelope $eventEnvelope
    ): void {
        $this->getDatabaseConnection()->update($this->tableName, [
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

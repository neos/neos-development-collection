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

namespace Neos\ContentRepository\Projection\ContentStream;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\ProjectionStateInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\EventStore\CatchUp\CatchUp;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\EventStreamInterface;

/**
 * @internal
 * @implements ProjectionInterface<ContentStreamFinder>
 */
class ContentStreamProjection implements ProjectionInterface
{
    /**
     * @var ContentStreamFinder|null Cache for the content stream finder returned by {@see getState()}, so that always the same instance is returned
     */
    private ?ContentStreamFinder $contentStreamFinder = null;
    private DoctrineCheckpointStorage $checkpointStorage;

    public function __construct(
        private readonly EventNormalizer $eventNormalizer,
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

        $schema = new Schema();
        $contentStreamTable = $schema->createTable($this->tableName);
        $contentStreamTable->addColumn('contentStreamIdentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $contentStreamTable->addColumn('version', Types::INTEGER)
            ->setNotnull(true);
        $contentStreamTable->addColumn('sourceContentStreamIdentifier', Types::STRING)
            ->setLength(255)
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
        $this->getDatabaseConnection()->exec('TRUNCATE ' . $this->tableName);
        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
    }

    public function canHandle(Event $event): bool
    {
        $eventClassName = $this->eventNormalizer->getEventClassName($event);

        return in_array($eventClassName, [
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
                ContentStreamWasRemoved::class,
            ])
            || is_subclass_of($eventClassName, EmbedsContentStreamAndNodeAggregateIdentifier::class);
    }

    public function catchUp(EventStreamInterface $eventStream, ContentRepository $contentRepository): void
    {
        $catchUp = CatchUp::create($this->apply(...), $this->checkpointStorage);
        $catchUp->run($eventStream);
    }

    private function apply(EventEnvelope $eventEnvelope): void
    {

        if (!$this->canHandle($eventEnvelope->event)) {
            return;
        }

        $eventInstance = $this->eventNormalizer->denormalize($eventEnvelope->event);

        if ($eventInstance instanceof ContentStreamWasCreated) {
            $this->whenContentStreamWasCreated($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof RootWorkspaceWasCreated) {
            $this->whenRootWorkspaceWasCreated($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof WorkspaceWasCreated) {
            $this->whenWorkspaceWasCreated($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof ContentStreamWasForked) {
            $this->whenContentStreamWasForked($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof WorkspaceWasDiscarded) {
            $this->whenWorkspaceWasDiscarded($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof WorkspaceWasPartiallyDiscarded) {
            $this->whenWorkspaceWasPartiallyDiscarded($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof WorkspaceWasPartiallyPublished) {
            $this->whenWorkspaceWasPartiallyPublished($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof WorkspaceWasPublished) {
            $this->whenWorkspaceWasPublished($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof WorkspaceWasRebased) {
            $this->whenWorkspaceWasRebased($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof WorkspaceRebaseFailed) {
            $this->whenWorkspaceRebaseFailed($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof ContentStreamWasRemoved) {
            $this->whenContentStreamWasRemoved($eventInstance, $eventEnvelope);
        } elseif ($eventInstance instanceof EmbedsContentStreamAndNodeAggregateIdentifier) {
            $this->updateContentStreamVersion($eventInstance, $eventEnvelope);
        } else {
            throw new \RuntimeException('Not supported: ' . get_class($eventInstance));
        }
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->checkpointStorage->getHighestAppliedSequenceNumber();
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
            'contentStreamIdentifier' => $event->contentStreamIdentifier,
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
            'contentStreamIdentifier' => $event->newContentStreamIdentifier
        ]);
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        // the content stream is in use now
        $this->getDatabaseConnection()->update($this->tableName, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->newContentStreamIdentifier
        ]);
    }

    private function whenContentStreamWasForked(ContentStreamWasForked $event, EventEnvelope $eventEnvelope): void
    {
        $this->getDatabaseConnection()->insert($this->tableName, [
            'contentStreamIdentifier' => $event->contentStreamIdentifier,
            'version' => self::extractVersion($eventEnvelope),
            'sourceContentStreamIdentifier' => $event->sourceContentStreamIdentifier,
            'state' => ContentStreamFinder::STATE_REBASING, // TODO: FORKED?
        ]);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamIdentifier,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousContentStreamIdentifier,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamIdentifier,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousContentStreamIdentifier,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newSourceContentStreamIdentifier,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousSourceContentStreamIdentifier,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newSourceContentStreamIdentifier,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousSourceContentStreamIdentifier,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->newContentStreamIdentifier,
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->previousContentStreamIdentifier,
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        $this->updateStateForContentStream(
            $event->candidateContentStreamIdentifier,
            ContentStreamFinder::STATE_REBASE_ERROR
        );
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event, EventEnvelope $eventEnvelope): void
    {
        $this->getDatabaseConnection()->update($this->tableName, [
            'removed' => true,
            'version' => self::extractVersion($eventEnvelope),
        ], [
            'contentStreamIdentifier' => $event->contentStreamIdentifier
        ]);
    }

    private function updateStateForContentStream(ContentStreamIdentifier $contentStreamIdentifier, string $state): void
    {
        $this->getDatabaseConnection()->update($this->tableName, [
            'state' => $state,
        ], [
            'contentStreamIdentifier' => $contentStreamIdentifier
        ]);
    }

    private function getDatabaseConnection(): Connection
    {
        return $this->dbalClient->getConnection();
    }

    private function updateContentStreamVersion(EmbedsContentStreamAndNodeAggregateIdentifier $eventInstance, EventEnvelope $eventEnvelope)
    {
        $this->getDatabaseConnection()->update($this->tableName, [
            'version' => self::extractVersion($eventEnvelope),
        ], [
            'contentStreamIdentifier' => $eventInstance->getContentStreamIdentifier()
        ]);
    }


    private static function extractVersion(EventEnvelope $eventEnvelope): int
    {
        if (!str_starts_with($eventEnvelope->streamName->value, ContentStreamEventStreamName::EVENT_STREAM_NAME_PREFIX)) {
            throw new \RuntimeException('Cannot extract version number, as it was projected on wrong stream "' . $eventEnvelope->streamName->value . '", but needs to start with ' . ContentStreamEventStreamName::EVENT_STREAM_NAME_PREFIX);
        }
        return $eventEnvelope->version->value;
    }
}

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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphSchemaBuilder;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
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
use Neos\ContentRepository\Infrastructure\Projection\AbstractProcessedEventsAwareProjector;
use Neos\EventStore\CatchUp\CatchUp;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStore\SetupResult;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\ProvidesSetupInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @internal
 */
class ContentStreamProjection implements ProjectionInterface
{
    /**
     * @var ContentStreamFinder|null Cache for the content stream finder returned by {@see getState()}, so that always the same instance is returned
     */
    private ?ContentStreamFinder $contentStreamFinder = null;

    public function __construct(
        private readonly EventNormalizer $eventNormalizer,
        private readonly CheckpointStorageInterface $checkpointStorage,
        private readonly DbalClientInterface $dbalClient,
        private readonly string $tableNamePrefix,
    )
    {
    }

    public function setUp(): void
    {
        $this->setupTables();

        if ($this->checkpointStorage instanceof ProvidesSetupInterface) {
            $this->checkpointStorage->setup();
        }
    }

    private function setupTables(): void
    {
        $connection = $this->dbalClient->getConnection();
        $schemaManager = $connection->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }

        $schema = new Schema();
        $contentStreamTable = $schema->createTable($this->tableNamePrefix . '_contentstream')
            ->addOption('collate', 'utf8mb4_unicode_ci');
        $contentStreamTable->addColumn('contentStreamIdentifier', Types::STRING)
            ->setLength(255)
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
        $this->getDatabaseConnection()->exec('TRUNCATE ' . $this->tableNamePrefix . '_contentstream');
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
        ]);
    }

    public function catchUp(EventStreamInterface $eventStream): void
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
            $this->whenContentStreamWasCreated($eventInstance);
        } elseif ($eventInstance instanceof RootWorkspaceWasCreated) {
            $this->whenRootWorkspaceWasCreated($eventInstance);
        } elseif ($eventInstance instanceof WorkspaceWasCreated) {
            $this->whenWorkspaceWasCreated($eventInstance);
        } elseif ($eventInstance instanceof ContentStreamWasForked) {
            $this->whenContentStreamWasForked($eventInstance);
        } elseif ($eventInstance instanceof WorkspaceWasDiscarded) {
            $this->whenWorkspaceWasDiscarded($eventInstance);
        } elseif ($eventInstance instanceof WorkspaceWasPartiallyDiscarded) {
            $this->whenWorkspaceWasPartiallyDiscarded($eventInstance);
        } elseif ($eventInstance instanceof WorkspaceWasPartiallyPublished) {
            $this->whenWorkspaceWasPartiallyPublished($eventInstance);
        } elseif ($eventInstance instanceof WorkspaceWasPublished) {
            $this->whenWorkspaceWasPublished($eventInstance);
        } elseif ($eventInstance instanceof WorkspaceWasRebased) {
            $this->whenWorkspaceWasRebased($eventInstance);
        } elseif ($eventInstance instanceof WorkspaceRebaseFailed) {
            $this->whenWorkspaceRebaseFailed($eventInstance);
        } elseif ($eventInstance instanceof ContentStreamWasRemoved) {
            $this->whenContentStreamWasRemoved($eventInstance);
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
                $this->dbalClient
            );
        }
        return $this->contentStreamFinder;
    }

    public function whenContentStreamWasCreated(ContentStreamWasCreated $event): void
    {
        $this->getDatabaseConnection()->insert($this->tableNamePrefix . '_contentstream', [
            'contentStreamIdentifier' => $event->contentStreamIdentifier,
            'state' => ContentStreamFinder::STATE_CREATED,
        ]);
    }

    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        // the content stream is in use now
        $this->getDatabaseConnection()->update($this->tableNamePrefix . '_contentstream', [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->getNewContentStreamIdentifier()
        ]);
    }

    public function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        // the content stream is in use now
        $this->getDatabaseConnection()->update($this->tableNamePrefix . '_contentstream', [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->getNewContentStreamIdentifier()
        ]);
    }

    public function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->getDatabaseConnection()->insert($this->tableNamePrefix . '_contentstream', [
            'contentStreamIdentifier' => $event->contentStreamIdentifier,
            'sourceContentStreamIdentifier' => $event->sourceContentStreamIdentifier,
            'state' => ContentStreamFinder::STATE_REBASING, // TODO: FORKED?
        ]);
    }

    public function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewSourceContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousSourceContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewSourceContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousSourceContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        // the new content stream is in use now
        $this->updateStateForContentStream(
            $event->getNewContentStreamIdentifier(),
            ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE
        );

        // the previous content stream is no longer in use
        $this->updateStateForContentStream(
            $event->getPreviousContentStreamIdentifier(),
            ContentStreamFinder::STATE_NO_LONGER_IN_USE
        );
    }

    public function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        $this->updateStateForContentStream(
            $event->getCandidateContentStreamIdentifier(),
            ContentStreamFinder::STATE_REBASE_ERROR
        );
    }

    public function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        $this->getDatabaseConnection()->update($this->tableNamePrefix . '_contentstream', [
            'removed' => true
        ], [
            'contentStreamIdentifier' => $event->contentStreamIdentifier
        ]);
    }

    private function updateStateForContentStream(ContentStreamIdentifier $contentStreamIdentifier, string $state): void
    {
        $this->getDatabaseConnection()->update($this->tableNamePrefix . '_contentstream', [
            'state' => $state,
        ], [
            'contentStreamIdentifier' => $contentStreamIdentifier
        ]);
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
        return $this->dbalClient->getConnection();
    }

}

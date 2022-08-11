<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\PendingChangesProjection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\EventStore\CatchUp\CatchUp;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\EventStreamInterface;

/**
 * TODO: this class needs testing and probably a major refactoring!
 * @internal
 * @implements ProjectionInterface<ChangeFinder>
 */
class ChangeProjection implements ProjectionInterface
{
    /**
     * @var ChangeFinder|null Cache for the ChangeFinder returned by {@see getState()},
     * so that always the same instance is returned
     */
    private ?ChangeFinder $changeFinder = null;
    private DoctrineCheckpointStorage $checkpointStorage;

    public function __construct(
        private readonly EventNormalizer $eventNormalizer,
        private readonly DbalClientInterface $dbalClient,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly string $tableName,
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
        $changeTable = $schema->createTable($this->tableName);
        $changeTable->addColumn('contentStreamIdentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $changeTable->addColumn('changed', Types::BOOLEAN)
            ->setNotnull(true);
        $changeTable->addColumn('moved', Types::BOOLEAN)
            ->setNotnull(true);

        $changeTable->addColumn('nodeAggregateIdentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $changeTable->addColumn('originDimensionSpacePoint', Types::TEXT)
            ->setNotnull(false);
        $changeTable->addColumn('originDimensionSpacePointHash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $changeTable->addColumn('deleted', Types::BOOLEAN)
            ->setNotnull(true);
        $changeTable->addColumn('removalAttachmentPoint', Types::STRING)
            ->setLength(255)
            ->setNotnull(false);

        $changeTable->setPrimaryKey([
            'contentStreamIdentifier',
            'nodeAggregateIdentifier',
            'originDimensionSpacePointHash'
        ]);

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
            NodeAggregateWasMoved::class,
            NodePropertiesWereSet::class,
            NodeAggregateWithNodeWasCreated::class,
            NodeAggregateWasDisabled::class,
            NodeAggregateWasEnabled::class,
            NodeAggregateWasRemoved::class,
            DimensionSpacePointWasMoved::class,
        ]);
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

        if ($eventInstance instanceof NodeAggregateWasMoved) {
            $this->whenNodeAggregateWasMoved($eventInstance);
        } elseif ($eventInstance instanceof NodePropertiesWereSet) {
            $this->whenNodePropertiesWereSet($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateWithNodeWasCreated) {
            $this->whenNodeAggregateWithNodeWasCreated($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateWasDisabled) {
            $this->whenNodeAggregateWasDisabled($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateWasEnabled) {
            $this->whenNodeAggregateWasEnabled($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateWasRemoved) {
            $this->whenNodeAggregateWasRemoved($eventInstance);
        } elseif ($eventInstance instanceof DimensionSpacePointWasMoved) {
            $this->whenDimensionSpacePointWasMoved($eventInstance);
        } else {
            throw new \RuntimeException('Not supported: ' . get_class($eventInstance));
        }
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->checkpointStorage->getHighestAppliedSequenceNumber();
    }

    public function getState(): ChangeFinder
    {
        if (!$this->changeFinder) {
            $this->changeFinder = new ChangeFinder(
                $this->dbalClient,
                $this->tableName
            );
        }
        return $this->changeFinder;
    }

    private function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        // WORKAROUND: we simply use the first MoveNodeMapping here to find the dimension space point
        // @todo properly handle this
        if (is_null($event->nodeMoveMappings)) {
            throw new \Exception(
                'Could not apply NodeAggregateWasMoved to change projection due to missing nodeMoveMappings.',
                1645382694
            );
        }
        $mapping = iterator_to_array($event->nodeMoveMappings);

        $this->markAsMoved(
            $event->getContentStreamIdentifier(),
            $event->getNodeAggregateIdentifier(),
            $mapping[0]->getMovedNodeOrigin()
        );
    }

    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {
        $this->markAsChanged(
            $event->contentStreamIdentifier,
            $event->nodeAggregateIdentifier,
            $event->originDimensionSpacePoint
        );
    }

    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        $this->markAsChanged(
            $event->contentStreamIdentifier,
            $event->nodeAggregateIdentifier,
            $event->originDimensionSpacePoint
        );
    }

    private function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $this->markAsChanged(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
            );
        }
    }

    private function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $this->markAsChanged(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
            );
        }
    }

    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->transactional(function () use ($event) {
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier(
                $event->contentStreamIdentifier
            );
            if ($workspace instanceof Workspace && $workspace->getBaseWorkspaceName() === null) {
                // Workspace is the live workspace (has no base workspace); we do not need to do anything
                return;
            }

            $this->getDatabaseConnection()->executeUpdate(
                'DELETE FROM ' . $this->tableName . '
                    WHERE
                        contentStreamIdentifier = :contentStreamIdentifier
                        AND nodeAggregateIdentifier = :nodeAggregateIdentifier
                        AND originDimensionSpacePointHash IN (:affectedDimensionSpacePointHashes)
                    ',
                [
                    'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                    'nodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                    'affectedDimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints
                        ->getPointHashes()
                ],
                [
                    'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
                ]
            );

            foreach ($event->affectedOccupiedDimensionSpacePoints as $occupiedDimensionSpacePoint) {
                $this->getDatabaseConnection()->executeUpdate(
                    'INSERT INTO ' . $this->tableName . '
                            (contentStreamIdentifier, nodeAggregateIdentifier, originDimensionSpacePoint,
                             originDimensionSpacePointHash, deleted, changed, moved, removalAttachmentPoint)
                        VALUES (
                            :contentStreamIdentifier,
                            :nodeAggregateIdentifier,
                            :originDimensionSpacePoint,
                            :originDimensionSpacePointHash,
                            1,
                            0,
                            0,
                            :removalAttachmentPoint
                        )
                    ',
                    [
                        'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                        'nodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                        'originDimensionSpacePoint' => json_encode($occupiedDimensionSpacePoint),
                        'originDimensionSpacePointHash' => $occupiedDimensionSpacePoint->hash,
                        'removalAttachmentPoint' => $event->removalAttachmentPoint?->__toString()
                    ]
                );
            }
        });
    }

    private function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE ' . $this->tableName . ' c
                    SET
                        c.originDimensionSpacePoint = :newDimensionSpacePoint,
                        c.originDimensionSpacePointHash = :newDimensionSpacePointHash
                    WHERE
                      c.originDimensionSpacePointHash = :originalDimensionSpacePointHash
                      AND c.contentStreamIdentifier = :contentStreamIdentifier
                      ',
                [
                    'originalDimensionSpacePointHash' => $event->source->hash,
                    'newDimensionSpacePointHash' => $event->target->hash,
                    'newDimensionSpacePoint' => json_encode($event->target->jsonSerialize()),
                    'contentStreamIdentifier' => (string)$event->contentStreamIdentifier
                ]
            );
        });
    }

    private function markAsChanged(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void {
        $this->transactional(function () use (
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $originDimensionSpacePoint
        ) {
            // HACK: basically we are not allowed to read other Projection's finder methods here;
            // but we nevertheless do it.
            // we can maybe figure out another way of solving this lateron.
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier);
            if ($workspace instanceof Workspace && $workspace->getBaseWorkspaceName() === null) {
                // Workspace is the live workspace (has no base workspace); we do not need to do anything
                return;
            }
            $change = $this->getChange(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $originDimensionSpacePoint
            );
            if ($change === null) {
                $change = new Change(
                    $contentStreamIdentifier,
                    $nodeAggregateIdentifier,
                    $originDimensionSpacePoint,
                    true,
                    false,
                    false
                );
                $change->addToDatabase($this->getDatabaseConnection(), $this->tableName);
            } else {
                $change->changed = true;
                $change->updateToDatabase($this->getDatabaseConnection(), $this->tableName);
            }
        });
    }

    private function markAsMoved(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void {
        $this->transactional(function () use (
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $originDimensionSpacePoint
        ) {
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifier);
            if ($workspace instanceof Workspace && $workspace->getBaseWorkspaceName() === null) {
                // Workspace is the live workspace (has no base workspace); we do not need to do anything
                return;
            }
            $change = $this->getChange(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $originDimensionSpacePoint
            );
            if ($change === null) {
                $change = new Change(
                    $contentStreamIdentifier,
                    $nodeAggregateIdentifier,
                    $originDimensionSpacePoint,
                    false,
                    true,
                    false
                );
                $change->addToDatabase($this->getDatabaseConnection(), $this->tableName);
            } else {
                $change->moved = true;
                $change->updateToDatabase($this->getDatabaseConnection(), $this->tableName);
            }
        });
    }

    private function getChange(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?Change {
        $changeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM ' . $this->tableName . ' n
WHERE n.contentStreamIdentifier = :contentStreamIdentifier
AND n.nodeAggregateIdentifier = :nodeAggregateIdentifier
AND n.originDimensionSpacePointHash = :originDimensionSpacePointHash',
            [
                'contentStreamIdentifier' => $contentStreamIdentifier,
                'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash
            ]
        )->fetch();

        // We always allow root nodes
        return $changeRow ? Change::fromDatabaseRow($changeRow) : null;
    }

    private function transactional(\Closure $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    /**
     * @return Connection
     */
    private function getDatabaseConnection(): Connection
    {
        return $this->dbalClient->getConnection();
    }
}

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
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use function sprintf;

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

        // MIGRATIONS
        $currentSchema = $schemaManager->createSchema();
        if ($currentSchema->hasTable($this->tableName)) {
            $tableSchema = $currentSchema->getTable($this->tableName);
            // added 2023-03-18
            if ($tableSchema->hasColumn('nodeAggregateIdentifier')) {
                // table in old format -> we migrate to new.
                $connection->executeStatement(sprintf('ALTER TABLE %s CHANGE nodeAggregateIdentifier nodeAggregateId VARCHAR(255); ', $this->tableName));
            }
            // added 2023-03-18
            if ($tableSchema->hasColumn('contentStreamIdentifier')) {
                $connection->executeStatement(sprintf('ALTER TABLE %s CHANGE contentStreamIdentifier contentStreamId VARCHAR(255); ', $this->tableName));
            }
        }

        $schema = new Schema();
        $changeTable = $schema->createTable($this->tableName);
        $changeTable->addColumn('contentStreamId', Types::STRING)
            ->setLength(40)
            ->setNotnull(true);
        $changeTable->addColumn('changed', Types::BOOLEAN)
            ->setNotnull(true);
        $changeTable->addColumn('moved', Types::BOOLEAN)
            ->setNotnull(true);

        $changeTable->addColumn('nodeAggregateId', Types::STRING)
            ->setLength(64)
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
            'contentStreamId',
            'nodeAggregateId',
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

    public function canHandle(EventInterface $event): bool
    {
        return in_array($event::class, [
            NodeAggregateWasMoved::class,
            NodePropertiesWereSet::class,
            NodeReferencesWereSet::class,
            NodeAggregateWithNodeWasCreated::class,
            NodeAggregateWasDisabled::class,
            NodeAggregateWasEnabled::class,
            NodeAggregateWasRemoved::class,
            DimensionSpacePointWasMoved::class,
            NodeGeneralizationVariantWasCreated::class,
            NodeSpecializationVariantWasCreated::class,
            NodePeerVariantWasCreated::class
        ]);
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            NodeAggregateWasMoved::class => $this->whenNodeAggregateWasMoved($event),
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($event),
            NodeReferencesWereSet::class => $this->whenNodeReferencesWereSet($event),
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($event),
            NodeAggregateWasDisabled::class => $this->whenNodeAggregateWasDisabled($event),
            NodeAggregateWasEnabled::class => $this->whenNodeAggregateWasEnabled($event),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($event),
            DimensionSpacePointWasMoved::class => $this->whenDimensionSpacePointWasMoved($event),
            NodeSpecializationVariantWasCreated::class => $this->whenNodeSpecializationVariantWasCreated($event),
            NodeGeneralizationVariantWasCreated::class => $this->whenNodeGeneralizationVariantWasCreated($event),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($event),
            default => throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
        };
    }

    public function getCheckpointStorage(): CheckpointStorageInterface
    {
        return $this->checkpointStorage;
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
        /* @var \Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMapping[] $mapping */
        $mapping = iterator_to_array($event->nodeMoveMappings);

        $this->markAsMoved(
            $event->getContentStreamId(),
            $event->getNodeAggregateId(),
            $mapping[0]->movedNodeOrigin
        );
    }

    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {
        $this->markAsChanged(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->originDimensionSpacePoint
        );
    }

    private function whenNodeReferencesWereSet(NodeReferencesWereSet $event): void
    {
        foreach ($event->affectedSourceOriginDimensionSpacePoints as $dimensionSpacePoint) {
            $this->markAsChanged(
                $event->contentStreamId,
                $event->sourceNodeAggregateId,
                $dimensionSpacePoint
            );
        }
    }

    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        $this->markAsChanged(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->originDimensionSpacePoint
        );
    }

    private function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $this->markAsChanged(
                $event->contentStreamId,
                $event->nodeAggregateId,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
            );
        }
    }

    private function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $this->markAsChanged(
                $event->contentStreamId,
                $event->nodeAggregateId,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
            );
        }
    }

    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->transactional(function () use ($event) {
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamId(
                $event->contentStreamId
            );
            if ($workspace instanceof Workspace && $workspace->baseWorkspaceName === null) {
                // Workspace is the live workspace (has no base workspace); we do not need to do anything
                return;
            }

            $this->getDatabaseConnection()->executeUpdate(
                'DELETE FROM ' . $this->tableName . '
                    WHERE
                        contentStreamId = :contentStreamId
                        AND nodeAggregateId = :nodeAggregateId
                        AND originDimensionSpacePointHash IN (:affectedDimensionSpacePointHashes)
                    ',
                [
                    'contentStreamId' => $event->contentStreamId->value,
                    'nodeAggregateId' => $event->nodeAggregateId->value,
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
                            (contentStreamId, nodeAggregateId, originDimensionSpacePoint,
                             originDimensionSpacePointHash, deleted, changed, moved, removalAttachmentPoint)
                        VALUES (
                            :contentStreamId,
                            :nodeAggregateId,
                            :originDimensionSpacePoint,
                            :originDimensionSpacePointHash,
                            1,
                            0,
                            0,
                            :removalAttachmentPoint
                        )
                    ',
                    [
                        'contentStreamId' => $event->contentStreamId->value,
                        'nodeAggregateId' => $event->nodeAggregateId->value,
                        'originDimensionSpacePoint' => json_encode($occupiedDimensionSpacePoint),
                        'originDimensionSpacePointHash' => $occupiedDimensionSpacePoint->hash,
                        'removalAttachmentPoint' => $event->removalAttachmentPoint?->value,
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
                      AND c.contentStreamId = :contentStreamId
                      ',
                [
                    'originalDimensionSpacePointHash' => $event->source->hash,
                    'newDimensionSpacePointHash' => $event->target->hash,
                    'newDimensionSpacePoint' => $event->target->toJson(),
                    'contentStreamId' => $event->contentStreamId->value
                ]
            );
        });
    }


    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        $this->markAsChanged(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->specializationOrigin
        );
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $this->markAsChanged(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->generalizationOrigin
        );
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $this->markAsChanged(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->peerOrigin
        );
    }

    private function markAsChanged(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): void {
        $this->transactional(function () use (
            $contentStreamId,
            $nodeAggregateId,
            $originDimensionSpacePoint
        ) {
            // HACK: basically we are not allowed to read other Projection's finder methods here;
            // but we nevertheless do it.
            // we can maybe figure out another way of solving this lateron.
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamId($contentStreamId);
            if ($workspace instanceof Workspace && $workspace->baseWorkspaceName === null) {
                // Workspace is the live workspace (has no base workspace); we do not need to do anything
                return;
            }
            $change = $this->getChange(
                $contentStreamId,
                $nodeAggregateId,
                $originDimensionSpacePoint
            );
            if ($change === null) {
                $change = new Change(
                    $contentStreamId,
                    $nodeAggregateId,
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
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): void {
        $this->transactional(function () use (
            $contentStreamId,
            $nodeAggregateId,
            $originDimensionSpacePoint
        ) {
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamId($contentStreamId);
            if ($workspace instanceof Workspace && $workspace->baseWorkspaceName === null) {
                // Workspace is the live workspace (has no base workspace); we do not need to do anything
                return;
            }
            $change = $this->getChange(
                $contentStreamId,
                $nodeAggregateId,
                $originDimensionSpacePoint
            );
            if ($change === null) {
                $change = new Change(
                    $contentStreamId,
                    $nodeAggregateId,
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
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): ?Change {
        $changeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM ' . $this->tableName . ' n
WHERE n.contentStreamId = :contentStreamId
AND n.nodeAggregateId = :nodeAggregateId
AND n.originDimensionSpacePointHash = :originDimensionSpacePointHash',
            [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
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

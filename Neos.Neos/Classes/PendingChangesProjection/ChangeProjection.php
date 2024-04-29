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
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Infrastructure\DbalCheckpointStorage;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;
use Neos\ContentRepository\Core\Projection\CheckpointStorageStatusType;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * TODO: this class needs testing and probably a major refactoring!
 * @internal
 * @implements ProjectionInterface<ChangeFinder>
 */
class ChangeProjection implements ProjectionInterface
{
    private const DEFAULT_TEXT_COLLATION = 'utf8mb4_unicode_520_ci';

    /**
     * @var ChangeFinder|null Cache for the ChangeFinder returned by {@see getState()},
     * so that always the same instance is returned
     */
    private ?ChangeFinder $changeFinder = null;

    /**
     * @var array<string>|null
     */
    private ?array $liveContentStreamIdsRuntimeCache = null;
    private DbalCheckpointStorage $checkpointStorage;

    public function __construct(
        private readonly DbalClientInterface $dbalClient,
        private readonly string $tableNamePrefix,
    ) {
        $this->checkpointStorage = new DbalCheckpointStorage(
            $this->dbalClient->getConnection(),
            $this->tableNamePrefix . '_checkpoint',
            self::class
        );
    }


    public function setUp(): void
    {
        foreach ($this->determineRequiredSqlStatements() as $statement) {
            $this->getDatabaseConnection()->executeStatement($statement);
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
            $this->getDatabaseConnection()->connect();
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
        $connection = $this->dbalClient->getConnection();
        $schemaManager = $connection->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }

        $changeTable = new Table($this->tableNamePrefix, [
            DbalSchemaFactory::columnForContentStreamId('contentStreamId')->setNotNull(true),
            (new Column('created', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            (new Column('changed', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            (new Column('moved', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            DbalSchemaFactory::columnForNodeAggregateId('nodeAggregateId')->setNotNull(true),
            DbalSchemaFactory::columnForDimensionSpacePoint('originDimensionSpacePoint')->setNotNull(false),
            DbalSchemaFactory::columnForDimensionSpacePointHash('originDimensionSpacePointHash')->setNotNull(true),
            (new Column('deleted', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            // Despite the name suggesting this might be an anchor point of sorts, this is a nodeAggregateId type
            DbalSchemaFactory::columnForNodeAggregateId('removalAttachmentPoint')->setNotNull(false)
        ]);

        $changeTable->setPrimaryKey([
            'contentStreamId',
            'nodeAggregateId',
            'originDimensionSpacePointHash'
        ]);

        $liveContentStreamsTable = new Table($this->tableNamePrefix . '_livecontentstreams', [
            DbalSchemaFactory::ColumnForContentStreamId('contentstreamid')->setNotNull(true),
            (new Column('workspacename', Type::getType(Types::STRING)))->setLength(255)->setDefault('')->setNotnull(true)->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION)
        ]);
        $liveContentStreamsTable->setPrimaryKey(['contentstreamid']);

        $schema = DbalSchemaFactory::createSchemaWithTables($schemaManager, [$changeTable, $liveContentStreamsTable]);
        return DbalSchemaDiff::determineRequiredSqlStatements($connection, $schema);
    }

    public function reset(): void
    {
        $this->getDatabaseConnection()->exec('TRUNCATE ' . $this->tableNamePrefix);
        $this->getDatabaseConnection()->exec('TRUNCATE ' . $this->tableNamePrefix . '_livecontentstreams');
        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
    }

    public function canHandle(EventInterface $event): bool
    {
        return in_array($event::class, [
            RootWorkspaceWasCreated::class,
            NodeAggregateWasMoved::class,
            NodePropertiesWereSet::class,
            NodeReferencesWereSet::class,
            NodeAggregateWithNodeWasCreated::class,
            SubtreeWasTagged::class,
            SubtreeWasUntagged::class,
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
            RootWorkspaceWasCreated::class => $this->whenRootWorkspaceWasCreated($event),
            NodeAggregateWasMoved::class => $this->whenNodeAggregateWasMoved($event),
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($event),
            NodeReferencesWereSet::class => $this->whenNodeReferencesWereSet($event),
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($event),
            SubtreeWasTagged::class => $this->whenSubtreeWasTagged($event),
            SubtreeWasUntagged::class => $this->whenSubtreeWasUntagged($event),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($event),
            DimensionSpacePointWasMoved::class => $this->whenDimensionSpacePointWasMoved($event),
            NodeSpecializationVariantWasCreated::class => $this->whenNodeSpecializationVariantWasCreated($event),
            NodeGeneralizationVariantWasCreated::class => $this->whenNodeGeneralizationVariantWasCreated($event),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($event),
            default => throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
        };
    }

    public function getCheckpointStorage(): DbalCheckpointStorage
    {
        return $this->checkpointStorage;
    }

    public function getState(): ChangeFinder
    {
        if (!$this->changeFinder) {
            $this->changeFinder = new ChangeFinder(
                $this->dbalClient,
                $this->tableNamePrefix
            );
        }
        return $this->changeFinder;
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        try {
            $this->getDatabaseConnection()->insert($this->tableNamePrefix . '_livecontentstreams', [
                'contentStreamId' => $event->newContentStreamId->value,
                'workspaceName' => $event->workspaceName->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to insert root content stream id of the root workspace "%s": %s',
                $event->workspaceName->value,
                $e->getMessage()
            ), 1689933563, $e);
        }
    }

    private function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event, EventEnvelope $eventEnvelope): void
    {
        $affectedDimensionSpacePoints = iterator_to_array($event->succeedingSiblingsForCoverage->toDimensionSpacePointSet());
        $arbitraryDimensionSpacePoint = reset($affectedDimensionSpacePoints);
        if ($arbitraryDimensionSpacePoint instanceof DimensionSpacePoint) {
            // always the case due to constraint enforcement (at least one DSP is selected and must have a succeeding sibling or null)

            // WORKAROUND: we simply use the event's first DSP here as the origin dimension space point.
            // But this DSP is not necessarily occupied.
            // @todo properly handle this by storing the necessary information in the projection

            $this->markAsMoved(
                $event->getContentStreamId(),
                $event->getNodeAggregateId(),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryDimensionSpacePoint)
            );
        }
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
        $this->markAsCreated(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->originDimensionSpacePoint
        );
    }

    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
            $this->markAsChanged(
                $event->contentStreamId,
                $event->nodeAggregateId,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
            );
        }
    }

    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
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
            if ($this->isLiveContentStream($event->contentStreamId)) {
                return;
            }

            $this->getDatabaseConnection()->executeUpdate(
                'DELETE FROM ' . $this->tableNamePrefix . '
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
                    'INSERT INTO ' . $this->tableNamePrefix . '
                            (contentStreamId, nodeAggregateId, originDimensionSpacePoint,
                             originDimensionSpacePointHash, created, deleted, changed, moved, removalAttachmentPoint)
                        VALUES (
                            :contentStreamId,
                            :nodeAggregateId,
                            :originDimensionSpacePoint,
                            :originDimensionSpacePointHash,
                            0,
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
                UPDATE ' . $this->tableNamePrefix . ' c
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
        $this->markAsCreated(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->specializationOrigin
        );
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $this->markAsCreated(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->generalizationOrigin
        );
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $this->markAsCreated(
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
        $this->modifyChange(
            $contentStreamId,
            $nodeAggregateId,
            $originDimensionSpacePoint,
            static function (Change $change) {
                $change->changed = true;
            }
        );
    }

    private function markAsCreated(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): void {
        $this->modifyChange(
            $contentStreamId,
            $nodeAggregateId,
            $originDimensionSpacePoint,
            static function (Change $change) {
                $change->created = true;
                $change->changed = true;
            }
        );
    }

    private function markAsMoved(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): void {
        $this->modifyChange(
            $contentStreamId,
            $nodeAggregateId,
            $originDimensionSpacePoint,
            static function (Change $change) {
                $change->moved = true;
            }
        );
    }

    private function modifyChange(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        callable $modifyFn
    ): void {
        $this->transactional(function () use (
            $contentStreamId,
            $nodeAggregateId,
            $originDimensionSpacePoint,
            $modifyFn
        ) {
            if ($this->isLiveContentStream($contentStreamId)) {
                return;
            }

            $change = $this->getChange($contentStreamId, $nodeAggregateId, $originDimensionSpacePoint);

            if ($change === null) {
                $change = new Change($contentStreamId, $nodeAggregateId, $originDimensionSpacePoint, false, false, false, false);
                $modifyFn($change);
                $change->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
            } else {
                $modifyFn($change);
                $change->updateToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
            }
        });
    }

    private function getChange(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
    ): ?Change {
        $changeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM ' . $this->tableNamePrefix . ' n
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

    private function isLiveContentStream(ContentStreamId $contentStreamId): bool
    {
        if ($this->liveContentStreamIdsRuntimeCache === null) {
            $this->liveContentStreamIdsRuntimeCache = $this->getDatabaseConnection()->fetchFirstColumn('SELECT contentstreamid FROM ' . $this->tableNamePrefix . '_livecontentstreams');
        }
        return in_array($contentStreamId->value, $this->liveContentStreamIdsRuntimeCache, true);
    }
}

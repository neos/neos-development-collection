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

namespace Neos\ContentRepository\Core\Projection\NodeHiddenState;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Core\Infrastructure\DbalCheckpointStorage;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;
use Neos\ContentRepository\Core\Projection\CheckpointStorageStatusType;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * TODO: this class needs proper testing
 * @internal
 * @implements ProjectionInterface<NodeHiddenStateFinder>
 */
class NodeHiddenStateProjection implements ProjectionInterface
{
    private ?NodeHiddenStateFinder $nodeHiddenStateFinder;
    private DbalCheckpointStorage $checkpointStorage;

    public function __construct(
        private readonly DbalClientInterface $dbalClient,
        private readonly string $tableName
    ) {
        $this->checkpointStorage = new DbalCheckpointStorage(
            $this->dbalClient->getConnection(),
            $this->tableName . '_checkpoint',
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

        $nodeHiddenStateTable = new Table($this->tableName, [
            DbalSchemaFactory::columnForContentStreamId('contentstreamid')->setNotNull(true),
            DbalSchemaFactory::columnForNodeAggregateId('nodeaggregateid')->setNotNull(false),
            DbalSchemaFactory::columnForDimensionSpacePointHash('dimensionspacepointhash')->setNotNull(false),
            DbalSchemaFactory::columnForDimensionSpacePoint('dimensionspacepoint')->setNotNull(false),
            (new Column('hidden', Type::getType(Types::BOOLEAN)))->setDefault(false)->setNotnull(false)
        ]);
        $nodeHiddenStateTable->setPrimaryKey(
            ['contentstreamid', 'nodeaggregateid', 'dimensionspacepointhash']
        );

        $schema = DbalSchemaFactory::createSchemaWithTables($schemaManager, [$nodeHiddenStateTable]);
        return DbalSchemaDiff::determineRequiredSqlStatements($connection, $schema);
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
            NodeAggregateWasDisabled::class,
            NodeAggregateWasEnabled::class,
            ContentStreamWasForked::class,
            DimensionSpacePointWasMoved::class
        ]);
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            NodeAggregateWasDisabled::class => $this->whenNodeAggregateWasDisabled($event),
            NodeAggregateWasEnabled::class => $this->whenNodeAggregateWasEnabled($event),
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($event),
            DimensionSpacePointWasMoved::class => $this->whenDimensionSpacePointWasMoved($event),
            default => throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
        };
    }

    public function getCheckpointStorage(): DbalCheckpointStorage
    {
        return $this->checkpointStorage;
    }

    public function getState(): NodeHiddenStateFinder
    {
        if (!isset($this->nodeHiddenStateFinder)) {
            $this->nodeHiddenStateFinder = new NodeHiddenStateFinder(
                $this->dbalClient,
                $this->tableName
            );
        }
        return $this->nodeHiddenStateFinder;
    }


    private function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        $this->transactional(function () use ($event) {
            foreach ($event->affectedDimensionSpacePoints as $dimensionSpacePoint) {
                if (
                    !$this->getState()->findHiddenState(
                        $event->contentStreamId,
                        $dimensionSpacePoint,
                        $event->nodeAggregateId
                    )->isHidden
                ) {
                    $nodeHiddenState = new NodeHiddenStateRecord(
                        $event->contentStreamId,
                        $event->nodeAggregateId,
                        $dimensionSpacePoint,
                        true
                    );
                    $nodeHiddenState->addToDatabase($this->getDatabaseConnection(), $this->tableName);
                }
            }
        });
    }

    private function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        $this->getDatabaseConnection()->executeQuery(
            '
                DELETE FROM
                    ' . $this->tableName . '
                WHERE
                    contentstreamid = :contentStreamId
                    AND nodeaggregateid = :nodeAggregateId
                    AND dimensionspacepointhash IN (:dimensionSpacePointHashes)
            ',
            [
                'contentStreamId' => $event->contentStreamId->value,
                'nodeAggregateId' => $event->nodeAggregateId->value,
                'dimensionSpacePointHashes' => $event->affectedDimensionSpacePoints->getPointHashes()
            ],
            [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

    private function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeUpdate('
                INSERT INTO ' . $this->tableName . ' (
                    contentstreamid,
                    nodeaggregateid,
                    dimensionspacepoint,
                    dimensionspacepointhash,
                    hidden
                )
                SELECT
                  "' . $event->newContentStreamId->value . '" AS contentstreamid,
                  nodeaggregateid,
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  hidden
                FROM
                    ' . $this->tableName . ' h
                    WHERE h.contentstreamid = :sourceContentStreamId
            ', [
                'sourceContentStreamId' => $event->sourceContentStreamId->value
            ]);
        });
    }

    private function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE ' . $this->tableName . ' nhs
                    SET
                        nhs.dimensionspacepoint = :newDimensionSpacePoint,
                        nhs.dimensionspacepointhash = :newDimensionSpacePointHash
                    WHERE
                      nhs.dimensionspacepointhash = :originalDimensionSpacePointHash
                      AND nhs.contentstreamid = :contentStreamId
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

    private function transactional(\Closure $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    private function getDatabaseConnection(): Connection
    {
        return $this->dbalClient->getConnection();
    }
}

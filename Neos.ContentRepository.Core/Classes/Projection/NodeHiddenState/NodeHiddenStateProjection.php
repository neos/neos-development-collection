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
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\EventStore\CatchUp\CatchUp;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\EventStreamInterface;

/**
 * TODO: this class needs proper testing
 * @internal
 * @implements ProjectionInterface<NodeHiddenStateFinder>
 */
class NodeHiddenStateProjection implements ProjectionInterface
{
    private ?NodeHiddenStateFinder $nodeHiddenStateFinder;
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
        $contentStreamTable->addColumn('contentstreamid', Types::STRING)
            ->setLength(40)
            ->setNotnull(true);
        $contentStreamTable->addColumn('nodeaggregateid', Types::STRING)
            ->setLength(64)
            ->setNotnull(true);
        $contentStreamTable->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $contentStreamTable->addColumn('dimensionspacepoint', Types::TEXT)
            ->setNotnull(false);
        $contentStreamTable->addColumn('hidden', Types::BOOLEAN)
            ->setDefault(false)
            ->setNotnull(false);

        $contentStreamTable->setPrimaryKey(
            ['contentstreamid', 'nodeaggregateid', 'dimensionspacepointhash']
        );

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
            NodeAggregateWasDisabled::class,
            NodeAggregateWasEnabled::class,
            ContentStreamWasForked::class,
            DimensionSpacePointWasMoved::class
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

        // @phpstan-ignore-next-line
        match ($eventInstance::class) {
            NodeAggregateWasDisabled::class => $this->whenNodeAggregateWasDisabled($eventInstance),
            NodeAggregateWasEnabled::class => $this->whenNodeAggregateWasEnabled($eventInstance),
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($eventInstance),
            DimensionSpacePointWasMoved::class => $this->whenDimensionSpacePointWasMoved($eventInstance),
        };
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->checkpointStorage->getHighestAppliedSequenceNumber();
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

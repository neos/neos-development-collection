<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Command\MigrateEventsCommandController;
use Neos\ContentRepositoryRegistry\Factory\EventStore\DoctrineEventStoreFactory;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Content Repository service to perform migrations of events.
 *
 * Each function is used here for a specific migration. The migrations are only useful for production
 * workloads which have events prior to the code change.
 *
 * @internal this is currently only used by the {@see MigrateEventsCommandController}
 */
final class EventMigrationService implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly Connection $connection
    ) {
    }

    public function migrateBlub(\Closure $outputFn): void
    {
        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);
        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId)
            . '_bkp_' . date('Y_m_d_H_i_s');
        $outputFn(sprintf('Backup: copying events table to %s', $backupEventTableName));
        $this->copyEventTable($backupEventTableName);

        if (!count($eventsToDrop)) {
            $outputFn('Migration was not necessary.');
            return;
        }

        $this->connection->beginTransaction();
        $this->connection->executeStatement(
            'DELETE FROM ' . $eventTableName . ' WHERE sequencenumber IN (:sequenceNumbers)',
            [
                'sequenceNumbers' => $eventsToDrop
            ],
            [
                'sequenceNumbers' => ArrayParameterType::STRING
            ]
        );
        $this->connection->commit();

        $outputFn();
        $outputFn(sprintf('Migration applied to %s events. Please replay the projections `./flow subscription:replayall`', count($eventsToDrop)));
    }

    /** ------------------------ */

    /**
     * @return array<string, mixed>
     */
    protected static function decodeEventPayload(EventEnvelope $eventEnvelope): array
    {
        try {
            return json_decode($eventEnvelope->event->data->value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-decode event payload of event #%d: %s', $eventEnvelope->sequenceNumber->value, $e->getMessage()), 1715951538, $e);
        }
    }

    private function copyEventTable(string $backupEventTableName): void
    {
        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);
        $this->connection->executeStatement(
            'CREATE TABLE ' . $backupEventTableName . ' AS
            SELECT *
            FROM ' . $eventTableName
        );
    }
}

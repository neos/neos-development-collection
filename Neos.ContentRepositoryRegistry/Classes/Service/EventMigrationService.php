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
        private readonly Connection $dbal
    ) {
    }

    /**
     * Optional migration to adjust event time stamps and node dates to UTC
     *
     * https://github.com/neos/neos-development-collection/pull/5716
     *
     * Detects if the ATOM stored "initiatingTimeStamp" has a uniform offset which is not UTC (0)
     * Then all "recordedAt" times are assumed to be in that timezone and their timestamp adjusted to match UTC
     *
     * Included in June 2026 - part of the bugfix 9.0.13, 9.1.6 and minor 9.2.0 release
     *
     * @param \Closure $outputFn
     * @return void
     */
    public function migrateRecordedAtToUtc(\Closure $outputFn): void
    {
        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);

        // Ignore null values for events without metadata
        $allOffsets = array_values(array_filter($this->dbal->fetchFirstColumn(<<<SQL
        SELECT DISTINCT SUBSTR(JSON_UNQUOTE(JSON_EXTRACT(e.metadata, '$.initiatingTimestamp')), 20) FROM {$eventTableName} AS e
        SQL)));

        if (count($allOffsets) > 1) {
            $outputFn(sprintf('Migration could not apply. The event store contains events with different timezones [%s]. Nothing was changed.', join(', ', $allOffsets)));
            return;
        }

        $singleOffset = $allOffsets[0];

        if ($singleOffset === '+00:00') {
            $outputFn('Migration was not necessary. All dates are UTC. Nothing was changed.');
            return;
        }

        // Actual migration

        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId)
            . '_bkp_' . date('Y_m_d_H_i_s');
        $outputFn(sprintf('Backup: copying events table to %s', $backupEventTableName));
        $this->copyEventTable($backupEventTableName);

        $this->dbal->beginTransaction();

        $affectedRows = $this->dbal->executeStatement(
            <<<SQL
            UPDATE {$eventTableName} AS e
            SET e.recordedat = CONVERT_TZ(e.recordedat, :fromOffset, '+00:00');
            SQL,
            [
                'fromOffset' => '+01:00'
            ]
        );
        $this->dbal->commit();

        $outputFn();
        $outputFn(sprintf('Migration applied to %s events. Please replay the projections `./flow subscription:replayall` to see the new adjusted UTC dates in the node timestamps', $affectedRows));
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
        $this->dbal->executeStatement(
            'CREATE TABLE ' . $backupEventTableName . ' AS
            SELECT *
            FROM ' . $eventTableName
        );
    }
}

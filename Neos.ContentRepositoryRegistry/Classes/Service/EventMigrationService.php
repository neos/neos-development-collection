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

        $offsetStartsWithSequenceNumber = $this->dbal->fetchAllAssociative(<<<SQL
        SELECT sequenceNumber, tzoffset
        FROM (
          SELECT
            sequenceNumber,
            SUBSTR(JSON_UNQUOTE(JSON_EXTRACT(e.metadata, '$.initiatingTimestamp')), 20) as tzoffset,
            LAG(SUBSTR(JSON_UNQUOTE(JSON_EXTRACT(e.metadata, '$.initiatingTimestamp')), 20)) OVER (ORDER BY sequenceNumber) AS prevTzoffset
          FROM {$eventTableName} as e
          WHERE JSON_EXTRACT(e.metadata, '$.initiatingTimestamp') IS NOT NULL
        ) t
        WHERE tzoffset != prevTzoffset
           -- select first row where there is no previous
           OR prevTzoffset IS NULL
        ORDER BY sequenceNumber;
        SQL);

        if (count($offsetStartsWithSequenceNumber) === 1 && $offsetStartsWithSequenceNumber[0]['tzoffset'] === '+00:00') {
            $outputFn('Migration was not necessary. All dates are UTC. Nothing was changed.');
            return;
        }

        $uniqueOffsets = array_unique(array_column($offsetStartsWithSequenceNumber, 'tzoffset'));

        $outputFn(sprintf('Migration necessary. Found following non UTC offsets [%s]', join(', ', array_filter($uniqueOffsets, fn ($value) => $value !== '+00:00'))));
        $outputFn(sprintf('    Debug: %s', json_encode($offsetStartsWithSequenceNumber)));

        // Actual migration
        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId)
            . '_bkp_' . date('Y_m_d_H_i_s');
        $outputFn(sprintf('Backup: copying events table to %s', $backupEventTableName));
        $this->copyEventTable($backupEventTableName);

        $this->dbal->beginTransaction();

        $affectedRows = 0;
        foreach ($offsetStartsWithSequenceNumber as $index => $offsetStart) {
            if ($offsetStart['tzoffset'] === '+00:00') {
                // nothing to do ;)
                continue;
            }

            $offsetEnd = $offsetStartsWithSequenceNumber[$index + 1] ?? null;

            $affectedRows += $this->dbal->executeStatement(
            <<<SQL
            UPDATE {$eventTableName} AS e
            SET e.recordedat = CONVERT_TZ(e.recordedat, :fromOffset, '+00:00')
            WHERE sequencenumber >= :start AND (:end IS NULL || sequencenumber < :end);
            ;
            SQL,
                [
                    'fromOffset' => $offsetStart['tzoffset'],
                    'start' => $offsetStart['sequenceNumber'],
                    'end' => $offsetEnd['sequenceNumber'] ?? null,
                ]
            );

        }

        $this->dbal->commit();

        $outputFn();
        $outputFn(sprintf('Migration applied to %s events. Please replay the projections `./flow subscription:replayall` to see the new adjusted UTC dates in the node timestamps', $affectedRows));
        $outputFn('Done. Dont re-rerun the migration as it will shift all dates again ;)');
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

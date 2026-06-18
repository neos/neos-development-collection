<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsRecordedAtToUtc;

use Neos\ContentRepositoryRegistry\Factory\EventStore\DoctrineEventStoreFactory;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\CRUpgradeContext;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\EventStoreBackupTrait;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\OutputMessageTrait;

/**
 * @internal CR upgrade internals
 */
final readonly class EventsRecordedAtToUtcUpgrade
{
    use EventStoreBackupTrait;
    use OutputMessageTrait;

    private string $eventTableName;

    public function __construct(
        private CRUpgradeContext $context,
        private \Closure $outputFn,
    ) {
        $this->eventTableName = DoctrineEventStoreFactory::databaseTableName($this->context->contentRepositoryId);
    }

    /**
     * Optional migration to adjust event time stamps and node dates to UTC
     *
     * https://github.com/neos/neos-development-collection/pull/5716
     *
     * By storing "recordedAt" as datetime field we lost its original timezone information.
     * But we can make the assumption that its timezone should be the same as the one encoded in the ATOM metadata field "initiatingTimeStamp"
     *
     * The migration first groups all events by the ATOM offset found in "initiatingTimeStamp".
     * If all events are UTC "+00:00" the migration is not necessary. For all non UTC groups we convert the "recordedAt" datetime field
     * to the datetime in the UTC timezone.
     *
     * The migration must not be executed multiple times as it would remove the offset to match UTC again for the "recordedAt" datetime even if they are already meant to be UTC.
     * To prevent this from happening we compare the "recordedAt" and "initiatingTimeStamp" and if they are equal considering timezones we know the migration was run.
     *
     * Included in June 2026 - part of the bugfix 9.0.13, 9.1.6 and minor 9.2.0 release
     *
     * @return void
     */
    public function execute(bool $force): void
    {
        $offsetStartsWithSequenceNumber = $this->context->dbal->fetchAllAssociative(<<<SQL
        SELECT sequenceNumber, tzoffset
        FROM (
          SELECT
            sequenceNumber,
            SUBSTR(JSON_UNQUOTE(JSON_EXTRACT(e.metadata, '$.initiatingTimestamp')), 20) as tzoffset,
            LAG(SUBSTR(JSON_UNQUOTE(JSON_EXTRACT(e.metadata, '$.initiatingTimestamp')), 20)) OVER (ORDER BY sequenceNumber) AS prevTzoffset
          FROM {$this->eventTableName} as e
          WHERE JSON_EXTRACT(e.metadata, '$.initiatingTimestamp') IS NOT NULL
        ) t
        WHERE tzoffset != prevTzoffset
           -- select first row where there is no previous
           OR prevTzoffset IS NULL
        ORDER BY sequenceNumber;
        SQL);

        if ($offsetStartsWithSequenceNumber === []) {
            $this->log('Migration was not necessary. No events.');
            return;
        }

        if (count($offsetStartsWithSequenceNumber) === 1 && $offsetStartsWithSequenceNumber[0]['tzoffset'] === '+00:00') {
            $this->log('Migration was not necessary. All dates are UTC. Nothing was changed.');
            return;
        }

        $uniqueOffsets = array_unique(array_column($offsetStartsWithSequenceNumber, 'tzoffset'));

        $this->log(sprintf('Migration necessary. Found following non UTC offsets [%s]', join(', ', array_filter($uniqueOffsets, fn ($value) => $value !== '+00:00'))));
        $this->log(sprintf('    Debug: %s', json_encode($offsetStartsWithSequenceNumber)));

        if ($this->educatedGuessIfSafeToApplyUTCMigration() === false) {
            if (!$force) {
                $this->log('Nothing was migrated. If you know what you are doing try again by using a bit more force.');
                return;
            }
        }

        // Actual migration
        $this->backupEventTable();

        $this->context->dbal->beginTransaction();

        $affectedRows = 0;
        foreach ($offsetStartsWithSequenceNumber as $index => $offsetStart) {
            if ($offsetStart['tzoffset'] === '+00:00') {
                // nothing to do ;)
                continue;
            }

            $offsetEnd = $offsetStartsWithSequenceNumber[$index + 1] ?? null;

            $affectedRows += $this->context->dbal->executeStatement(
                <<<SQL
            UPDATE {$this->eventTableName} AS e
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

        $this->context->dbal->commit();

        $this->log('');
        $this->log(sprintf('Migration applied to %s events. Please replay the projections `./flow subscription:replayall` to see the new adjusted UTC dates in the node timestamps', $affectedRows));
        $this->log('Done. Please dont re-rerun the migration.');
    }

    private function educatedGuessIfSafeToApplyUTCMigration(): bool
    {
        // Check to attempt to find out if migration was run.
        // We find the first event not of type PublishableToWorkspaceInterface (all events on workspace streams)
        // as these should have the same initiatingTimestamp and recordedAt dates.
        // If the dates are not equal in UTC time the migration need to be run.
        $sampleNonPublishableEventWithNonUTCTime = $this->context->dbal->fetchAssociative(<<<SQL
            SELECT sequencenumber, recordedat, JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.initiatingTimestamp')) AS initiatingtimestampatom
            FROM {$this->eventTableName}
            WHERE stream LIKE 'Workspace:%'
              AND SUBSTR(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.initiatingTimestamp')), 20) != '+00:00'
              LIMIT 1;
            SQL
        );

        if ($sampleNonPublishableEventWithNonUTCTime === false) {
            $this->log('Could not find a single non publishable event with non UTC date to validate if migration was run before.');
            return false;
        }

        $recordedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $sampleNonPublishableEventWithNonUTCTime['recordedat'], new \DateTimeZone('UTC'));
        $initiatingTimestamp = (\DateTimeImmutable::createFromFormat(\DateTimeImmutable::ATOM, $sampleNonPublishableEventWithNonUTCTime['initiatingtimestampatom']) ?: null)?->setTimezone(new \DateTimeZone('UTC'));

        if ($recordedAt === false || $initiatingTimestamp === null) {
            $this->log('Could determine if migration was run before, invalid dates.');
            $this->log(sprintf('    Debug: %s', json_encode($sampleNonPublishableEventWithNonUTCTime)));
            return false;
        }

        $absoluteDifference = (new \DateTimeImmutable('@0'))->add($recordedAt->diff($initiatingTimestamp));

        if (abs($absoluteDifference->getTimestamp()) < 3) {
            // Equal within 3 seconds, as we don't use the same date time instance
            $this->log(sprintf('Warning event %s already migrated', $sampleNonPublishableEventWithNonUTCTime['sequencenumber']));
            $this->log(sprintf('    Debug: RecordedAt %s, Initiating %s, Difference %s (s)', $recordedAt->format('Y-m-d H:i:s'), $initiatingTimestamp->format('Y-m-d H:i:s'), $absoluteDifference->getTimestamp()));
            $this->log(sprintf('    Debug: %s', json_encode($sampleNonPublishableEventWithNonUTCTime)));
            return false;
        }

        return true;
    }
}

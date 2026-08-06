<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsDeduplicateBaseWorkspaceChanges;

use Doctrine\DBAL\ArrayParameterType;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\CRUpgradeContext;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\EventEnvelopeFactory;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\EventStoreBackupTrait;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\OutputMessageTrait;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Upgrade to deduplicate parallel base workspace changes
 *
 * https://github.com/neos/neos-development-collection/issues/5877
 *
 * Workspace operations, also ChangeBaseWorkspace were not thread safe before the addition of workspace versioning
 * in the read model and thus safe soft constraint checks.
 * That resulted in the possibility that a single workspace was changed two or more times in parallel by the same user.
 * Because each ChangeBaseWorkspace can be slow and cleans up at the end the content stream via ContentStreamWasRemoved,
 * that results in possibly multiple illegal ContentStreamWasRemoved events and in total a fully illegal ChangeBaseWorkspace.
 *
 * 1.) The upgrade first identifies any duplicate ContentStreamWasRemoved events on a single stream.
 * 2.) If found we assume check via their unique prefixed correlation id that they belong to a ChangeBaseWorkspace sequence.
 * 3.) Then we find all events of the ChangeBaseWorkspace sequences and that occurred during these the content stream removals.
 * 4.) If there ar no other concurrent changes on our workspace - which would have been illegal as well - and it's truly
 *     the race condition as understood with only the events a ChangeBaseWorkspace emits we continue.
 * 5.) The ChangeBaseWorkspace sequences can even be interlaced due to the race condition but the correlation id identifies the last sequence to keep uniquely.
 * 6.) The last and thus valid ChangeBaseWorkspace sequence will be preserved, and we delete all events from the previous illegal sequence(s).
 *
 * After the migration is applied the workspace will have the same new base workspace as without the migration,
 * but we deduplicated any temporary changes that happened in a race condition. No content on the workspaces is affected.
 *
 * Included in June 2026 - part of the minor 9.2.0 release
 *
 * @internal
 */
class EventsDeduplicateBaseWorkspaceChangesUpgrade
{
    use EventStoreBackupTrait;
    use OutputMessageTrait;

    public function __construct(
        private CRUpgradeContext $context,
        private \Closure $outputFn,
    ) {
    }

    public static function getShortDescription(): string
    {
        return 'Deduplicate parallel base workspace changes';
    }

    public function isAvailable(): bool
    {
        $duplicateContentStreamRemovalWithStreams = $this->findDuplicateContentStreamRemovalWithStreams();

        if ($duplicateContentStreamRemovalWithStreams === []) {
            return false;
        }

        return true;
    }

    public function execute(bool $dryRun): void
    {
        //
        // 1.)
        //
        $duplicateContentStreamRemovalWithStreams = $this->findDuplicateContentStreamRemovalWithStreams();

        if ($duplicateContentStreamRemovalWithStreams === []) {
            $this->log('Migration was not necessary. No duplicate content stream removals.');
            return;
        }

        $this->log(sprintf('%d content streams were removed more than once:', count($duplicateContentStreamRemovalWithStreams)));
        $this->log('');
        $this->log(join("\n", array_map(fn (IllegalContentStreamRemovalsByStream $i) => $i->toDebugString(), $duplicateContentStreamRemovalWithStreams)));
        $this->log('');

        $sequenceNumbersToRemove = [];

        foreach ($duplicateContentStreamRemovalWithStreams as $duplicateContentStreamRemovals) {
            $stream = $duplicateContentStreamRemovals->stream;

            //
            // 2.)
            //
            foreach ($duplicateContentStreamRemovals->correlationIds as $correlationId) {
                if (!str_starts_with($correlationId->value, 'ChangeBaseWorkspace_')) {
                    $this->log(sprintf('Error resolve duplicate content stream removal of %s as it was not caused due to a ChangeBaseWorkspace', $stream->value));
                    return;
                }
            }

            //
            // 3.)
            //
            /** @var list<EventEnvelope> $conflictingEvents */
            $conflictingEvents = array_map(EventEnvelopeFactory::createFromArray(...), $this->context->dbal->fetchAllAssociative(<<<SQL
            SELECT *
            FROM {$this->context->eventStoreTableName}
              WHERE
                correlationId IN (:correlationIds)
                OR (sequenceNumber >= :lowestSequenceNumber AND sequenceNumber <= :highestSequenceNumber)
            ORDER BY sequencenumber;
            SQL, [
                'lowestSequenceNumber' => $duplicateContentStreamRemovals->lowestSequenceNumber->value,
                'highestSequenceNumber' => $duplicateContentStreamRemovals->highestSequenceNumber->value,
                'correlationIds' => array_column($duplicateContentStreamRemovals->correlationIds, 'value'),
            ], [
                'correlationIds' => ArrayParameterType::STRING,
            ]));

            // Correlation id as index
            $changeBaseWorkspaceSequenceMap = [];

            // Correlation id as index
            $changeBaseWorkspaceSequenceNumbersByCorrelationMap = [];

            $newForkedContentStreamMap = [];
            $baseWorkspaceChangeCorrelationIdMap = array_fill_keys(array_column($duplicateContentStreamRemovals->correlationIds, 'value'), true);
            $winningChangeBaseWorkspaceCorrelationId = null;

            foreach ($conflictingEvents as $conflictingEvent) {
                //
                // 4.)
                //
                if (!$conflictingEvent->event->correlationId || !array_key_exists($conflictingEvent->event->correlationId->value, $baseWorkspaceChangeCorrelationIdMap)) {
                    if (array_key_exists($conflictingEvent->streamName->value, $newForkedContentStreamMap) || $stream->equals($conflictingEvent->streamName)) {
                        $this->log(sprintf('Stream %s: Concurrent change during change base workspace sequence affected stream %s at %s', $stream->value, $conflictingEvent->streamName->value, $conflictingEvent->sequenceNumber->value));
                        $this->log(sprintf('    Debug: %s', json_encode($conflictingEvent)));
                        return;
                    }
                    continue;
                }

                //
                // 5.)
                //
                $currentChangeBaseWorkspaceSequence = $changeBaseWorkspaceSequenceMap[$conflictingEvent->event->correlationId->value] ??= ChangeBaseWorkspaceSequence::start();

                if ($currentChangeBaseWorkspaceSequence === ChangeBaseWorkspaceSequence::ENDED) {
                    $this->log(sprintf('Stream %s: Invalid change base workspace sequence, expected no further events %s at %s', $stream->value, $conflictingEvent->event->type->value, $conflictingEvent->sequenceNumber->value));
                    $this->log(sprintf('    Debug: %s', json_encode($conflictingEvents)));
                    return;
                }

                if ($conflictingEvent->event->type->value !== $currentChangeBaseWorkspaceSequence->value) {
                    $this->log(sprintf('Stream %s: Invalid change base workspace sequence, expected %s got %s at %s', $stream->value, $currentChangeBaseWorkspaceSequence->value, $conflictingEvent->event->type->value, $conflictingEvent->sequenceNumber->value));
                    $this->log(sprintf('    Debug: %s', json_encode($conflictingEvents)));
                    return;
                }

                if ($currentChangeBaseWorkspaceSequence === ChangeBaseWorkspaceSequence::ContentStreamWasForked) {
                    $newForkedContentStreamMap[$conflictingEvent->streamName->value] = true;
                }

                $winningChangeBaseWorkspaceCorrelationId = $conflictingEvent->event->correlationId;
                $changeBaseWorkspaceSequenceNumbersByCorrelationMap[$conflictingEvent->event->correlationId->value][] = $conflictingEvent->sequenceNumber->value;
                $changeBaseWorkspaceSequenceMap[$conflictingEvent->event->correlationId->value] = $currentChangeBaseWorkspaceSequence->next();
            }

            foreach ($changeBaseWorkspaceSequenceMap as $correlationId => $lastChangeBaseWorkspaceSequence) {
                if ($lastChangeBaseWorkspaceSequence !== ChangeBaseWorkspaceSequence::ENDED) {
                    $this->log(sprintf('Stream %s: Invalid end of change base workspace sequence %s, got %s', $stream->value, $correlationId, $lastChangeBaseWorkspaceSequence->value));
                    $this->log(sprintf('    Debug: %s', json_encode($conflictingEvents)));
                    return;
                }
            }

            if (!$winningChangeBaseWorkspaceCorrelationId) {
                throw new \RuntimeException(sprintf('Fatal error in upgrade. No winning ChangeBaseWorkspaceCorrelationId found'), 1781783151);
            }

            //
            // 6.)
            //
            // keep the last change base workspace sequence
            unset($changeBaseWorkspaceSequenceNumbersByCorrelationMap[$winningChangeBaseWorkspaceCorrelationId->value]);

            $sequenceNumbersToRemove = array_merge($sequenceNumbersToRemove, ...array_values($changeBaseWorkspaceSequenceNumbersByCorrelationMap));
        }

        if ($sequenceNumbersToRemove === []) {
            $this->log('Error no sequence numbers found to remove.');
            return;
        }

        $this->log(sprintf('Found %d events to be removed', count($sequenceNumbersToRemove)));
        $this->log(sprintf('    Debug: %s', join(',', $sequenceNumbersToRemove)));

        if ($dryRun) {
            $this->log('Didnt migrate anything because its a dry run.');
            return;
        }
        // Actual migration
        $this->backupEventTable();

        $this->context->dbal->beginTransaction();

        $affectedRows = $this->context->dbal->executeStatement(
            <<<SQL
        DELETE FROM {$this->context->eventStoreTableName} WHERE sequencenumber IN (:sequenceNumbersToRemove);
        SQL,
            [
                'sequenceNumbersToRemove' => $sequenceNumbersToRemove,
            ],
            [
                'sequenceNumbersToRemove' => ArrayParameterType::INTEGER
            ]
        );

        $this->context->dbal->commit();

        $this->log('');
        $this->log(sprintf('Migration applied to %s events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`', $affectedRows));
        $this->log('Done.');
    }

    /**
     * @return list<IllegalContentStreamRemovalsByStream>
     */
    private function findDuplicateContentStreamRemovalWithStreams(): array
    {
        $duplicateContentStreamRemovalWithStreams = $this->context->dbal->fetchAllAssociative(<<<SQL
        SELECT stream, GROUP_CONCAT(sequencenumber ORDER BY sequencenumber) sequenceNumbers, GROUP_CONCAT(correlationid ORDER BY sequencenumber) correlationIds, COUNT(*) removals
        FROM {$this->context->eventStoreTableName}
          WHERE type = 'ContentStreamWasRemoved'
        GROUP BY stream
        HAVING removals > 1
        ORDER BY MIN(sequencenumber);
        SQL);

        return array_map(IllegalContentStreamRemovalsByStream::fromRow(...), $duplicateContentStreamRemovalWithStreams);
    }
}

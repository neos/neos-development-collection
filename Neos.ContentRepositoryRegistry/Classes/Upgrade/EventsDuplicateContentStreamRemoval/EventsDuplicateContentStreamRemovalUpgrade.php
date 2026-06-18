<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsDuplicateContentStreamRemoval;

use Doctrine\DBAL\ArrayParameterType;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\CRUpgradeContext;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\EventEnvelopeFactory;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\EventStoreBackupTrait;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\OutputMessageTrait;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventEnvelope;
use Symfony\Component\Yaml\Yaml;

class EventsDuplicateContentStreamRemovalUpgrade
{
    use EventStoreBackupTrait;
    use OutputMessageTrait;

    public function __construct(
        private CRUpgradeContext $context,
        private \Closure $outputFn,
    ) {
    }

    public function execute(bool $dryRun): void
    {
        $duplicateContentStreamRemovalWithStreams = $this->context->dbal->fetchAllAssociative(<<<SQL
        SELECT stream, GROUP_CONCAT(sequencenumber ORDER BY sequencenumber) sequenceNumbers, GROUP_CONCAT(correlationid ORDER BY sequencenumber) correlationIds, COUNT(*) removals
        FROM {$this->context->eventStoreTableName}
          WHERE type = 'ContentStreamWasRemoved'
        GROUP BY stream
        HAVING removals > 1
        ORDER BY MIN(sequencenumber);
        SQL);

        if ($duplicateContentStreamRemovalWithStreams === []) {
            $this->log('Migration was not necessary. No duplicate content stream removals.');
            return;
        }

        $this->log(sprintf('%d content streams were removed more than once:', count($duplicateContentStreamRemovalWithStreams)));
        $this->log('');
        $this->log(Yaml::dump($duplicateContentStreamRemovalWithStreams, 2));
        $this->log('');

        foreach ($duplicateContentStreamRemovalWithStreams as $duplicateContentStreamRemovals) {
            // We dont write "," into correlation ids
            /** @var list<CorrelationId> $correlationIds */
            $correlationIds = array_map(CorrelationId::fromString(...), explode(',', $duplicateContentStreamRemovals['correlationIds']));
            foreach ($correlationIds as $correlationId) {
                if (!str_starts_with($correlationId->value, 'ChangeBaseWorkspace_')) {
                    $this->log(sprintf('Error resolve duplicate content stream removal of %s as it was not caused due to a ChangeBaseWorkspace', $duplicateContentStreamRemovals['stream']));
                    return;
                }
            }
        }

        $sequenceNumbersToRemove = [];

        foreach ($duplicateContentStreamRemovalWithStreams as $duplicateContentStreamRemovals) {
            $stream = StreamName::fromString($duplicateContentStreamRemovals['stream']);

            if (!ContentStreamEventStreamName::isContentStreamStreamName($stream)) {
                $this->log(sprintf('Error found illegal content stream removal event on non content stream %s', $stream->value));
            }

            /** @var list<CorrelationId> $correlationIds */
            $correlationIds = array_map(CorrelationId::fromString(...), explode(',', $duplicateContentStreamRemovals['correlationIds']));
            foreach ($correlationIds as $correlationId) {
                if (!str_starts_with($correlationId->value, 'ChangeBaseWorkspace_')) {
                    $this->log(sprintf('Error resolve duplicate content stream removal of %s as it was not caused due to a ChangeBaseWorkspace', $duplicateContentStreamRemovals['stream']));
                    return;
                }
            }

            $sequenceNumbers = array_map(intval(...), explode(',', $duplicateContentStreamRemovals['sequenceNumbers']));
            $lowestSequenceNumber = SequenceNumber::fromInteger(min($sequenceNumbers));
            $highestSequenceNumber = SequenceNumber::fromInteger(max($sequenceNumbers));

            /** @var list<EventEnvelope> $conflictingEvents */
            $conflictingEvents = array_map(EventEnvelopeFactory::createFromArray(...), $this->context->dbal->fetchAllAssociative(<<<SQL
            SELECT *
            FROM {$this->context->eventStoreTableName}
              WHERE
                correlationId IN (:correlationIds)
                OR (sequenceNumber >= :lowestSequenceNumber AND sequenceNumber <= :highestSequenceNumber)
            ORDER BY sequencenumber;
            SQL, [
                'lowestSequenceNumber' => $lowestSequenceNumber->value,
                'highestSequenceNumber' => $highestSequenceNumber->value,
                'correlationIds' => array_column($correlationIds, 'value'),
            ], [
                'correlationIds' => ArrayParameterType::STRING,
            ]));

            // Correlation id as index
            $changeBaseWorkspaceSequenceMap = [];

            $allSequenceNumbersMap = [];
            $sequenceNumbersToKeep = [];

            $newForkedContentStreamMap = [];
            $baseWorkspaceChangeCorrelationIdMap = array_fill_keys(array_column($correlationIds, 'value'), true);

            foreach ($conflictingEvents as $conflictingEvent) {
                if (!$conflictingEvent->event->correlationId || !array_key_exists($conflictingEvent->event->correlationId->value, $baseWorkspaceChangeCorrelationIdMap)) {
                    if (array_key_exists($conflictingEvent->streamName->value, $newForkedContentStreamMap) || $stream->equals($conflictingEvent->streamName)) {
                        $this->log(sprintf('Stream %s: Concurrent change during change base workspace sequence affected stream %s at %s', $stream->value, $conflictingEvent->streamName->value, $conflictingEvent->sequenceNumber->value));
                        $this->log(sprintf('    Debug: %s', json_encode($conflictingEvent)));
                        return;
                    }
                    continue;
                }

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

                $allSequenceNumbersMap[$conflictingEvent->sequenceNumber->value] = true;

                if ($currentChangeBaseWorkspaceSequence === ChangeBaseWorkspaceSequence::start()) {
                    $sequenceNumbersToKeep = [];
                }
                $sequenceNumbersToKeep[$conflictingEvent->sequenceNumber->value] = true;

                $changeBaseWorkspaceSequenceMap[$conflictingEvent->event->correlationId->value] = $currentChangeBaseWorkspaceSequence->next();
            }

            foreach ($changeBaseWorkspaceSequenceMap as $correlationId => $lastChangeBaseWorkspaceSequence) {
                if ($lastChangeBaseWorkspaceSequence !== ChangeBaseWorkspaceSequence::ENDED) {
                    $this->log(sprintf('Stream %s: Invalid end of change base workspace sequence %s, got %s', $stream->value, $correlationId, $lastChangeBaseWorkspaceSequence->value));
                    $this->log(sprintf('    Debug: %s', json_encode($conflictingEvents)));
                    return;
                }
            }

            $sequenceNumbersMapToRemoveForStream = array_diff_key($allSequenceNumbersMap, $sequenceNumbersToKeep);

            $sequenceNumbersToRemove = array_merge($sequenceNumbersToRemove, array_keys($sequenceNumbersMapToRemoveForStream));
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
        $this->log(sprintf('Migration applied to %s events. Please replay the projections `./flow subscription:replayall`', $affectedRows));
        $this->log('Done.');
    }
}

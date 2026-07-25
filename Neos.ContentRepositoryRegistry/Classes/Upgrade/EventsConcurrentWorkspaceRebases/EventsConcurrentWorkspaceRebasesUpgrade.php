<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

use Doctrine\DBAL\ArrayParameterType;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\CRUpgradeContext;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\EventEnvelopeFactory;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\EventStoreBackupTrait;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\OutputMessageTrait;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\EventEnvelope;

/**
 * TODO
 * @internal
 */
class EventsConcurrentWorkspaceRebasesUpgrade
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
        $forkedContentStreamsWithAlreadyRemovedSourceContentStream = $this->context->dbal->fetchAllAssociative(<<<SQL
        SELECT forked.sequencenumber AS sourceForkedAtPosition, forked.correlationId AS forkCorrelationId, forked.newContentStreamId, removals.sequencenumber AS removedAtPosition, removals.contentstreamid AS sourceContentStreamId FROM (
          SELECT JSON_UNQUOTE(JSON_EXTRACT(payload, '$.contentStreamId')) AS contentStreamId, sequencenumber FROM {$this->context->eventStoreTableName}
            WHERE type = 'ContentStreamWasRemoved'
        ) AS removals
          JOIN (SELECT sequencenumber, correlationId, JSON_UNQUOTE(JSON_EXTRACT(payload, '$.newContentStreamId')) as newContentStreamId, JSON_UNQUOTE(JSON_EXTRACT(payload, '$.sourceContentStreamId')) as sourceContentStreamId FROM {$this->context->eventStoreTableName} WHERE type = 'ContentStreamWasForked') AS forked
            ON removals.contentstreamid = forked.sourcecontentstreamid
            AND removals.sequencenumber < forked.sequencenumber        
        SQL);

        if ($forkedContentStreamsWithAlreadyRemovedSourceContentStream === []) {
            $this->log('Migration was not necessary. No forks on already removed content streams.');
            return;
        }

        $sequenceNumbersToRemove = [];
        /** @var list<RebaseSequenceContentStreamPatch> $rebaseSequencesToPatchContentStream */
        $rebaseSequencesToPatchContentStream = [];

        foreach ($forkedContentStreamsWithAlreadyRemovedSourceContentStream as $forkedContentStreamWithAlreadyRemovedSourceContentStream) {
            $forkCorrelationId = CorrelationId::fromString($forkedContentStreamWithAlreadyRemovedSourceContentStream['forkCorrelationId']);
            $newContentStreamId = ContentStreamId::fromString($forkedContentStreamWithAlreadyRemovedSourceContentStream['newContentStreamId']);

            if (!str_starts_with($forkCorrelationId->value, 'RebaseWorkspace_')) {
                $this->log(sprintf('Error fork content stream %s from removed source was not caused due to a RebaseWorkspace and cannot be migrated', $newContentStreamId->value));
                return;
            }
            /** @var list<EventEnvelope> $rebaseWorkspaceEvents */
            $rebaseWorkspaceEvents = array_map(EventEnvelopeFactory::createFromArray(...), $this->context->dbal->fetchAllAssociative(<<<SQL
            SELECT *
            FROM {$this->context->eventStoreTableName}
              WHERE
                correlationId = :correlationId
            ORDER BY sequencenumber
            SQL, [
                'correlationId' => $forkCorrelationId->value
            ]));


            try {
                $rebaseWorkspaceSequence = RebaseEmptyWorkspaceSequence::fromEvents($rebaseWorkspaceEvents);
            } catch (\Exception $exception) {
                $this->log(sprintf('Error: %s', $exception->getMessage()));
                $this->log(sprintf('    Debug: %s', json_encode($rebaseWorkspaceEvents)));
                return;
            }

            if (!$rebaseWorkspaceSequence->newContentStreamId->equals($newContentStreamId)) {
                $this->log(sprintf('Error: Expected rebase of %s got %s', $newContentStreamId->value, $rebaseWorkspaceSequence->newContentStreamId->value));
                $this->log(sprintf('    Debug: %s', json_encode($rebaseWorkspaceEvents)));
            }

            $sequenceNumbersToRemove = [...$sequenceNumbersToRemove, ...$rebaseWorkspaceSequence->getSequenceNumbers()];

            /** @var list<EventEnvelope> $remainingContentStreamEvents */
            $remainingContentStreamEvents = array_map(EventEnvelopeFactory::createFromArray(...), $this->context->dbal->fetchAllAssociative(<<<SQL
            SELECT *
            FROM {$this->context->eventStoreTableName}
              WHERE
                correlationId != :correlationId
                AND stream = :stream
            ORDER BY sequencenumber
            SQL, [
                'correlationId' => $forkCorrelationId->value,
                'stream' => ContentStreamEventStreamName::fromContentStreamId($newContentStreamId)->getEventStreamName()->value,
            ]));

            if ($remainingContentStreamEvents !== []) {
                try {
                    $nextRebaseCorrelationId = RebaseWorkspaceCorrelationId::fromEvents($remainingContentStreamEvents);
                } catch (\Exception $exception) {
                    $this->log(sprintf('Error: %s', $exception->getMessage()));
                    $this->log(sprintf('    Debug: %s', json_encode($remainingContentStreamEvents)));
                    return;
                }

                /** @var list<EventEnvelope> $nextRebaseWorkspaceEvents */
                $nextRebaseWorkspaceEvents = array_map(EventEnvelopeFactory::createFromArray(...), $this->context->dbal->fetchAllAssociative(<<<SQL
                SELECT *
                FROM {$this->context->eventStoreTableName}
                  WHERE
                    correlationId = :correlationId
                ORDER BY sequencenumber
                SQL, [
                    'correlationId' => $nextRebaseCorrelationId->value
                ]));

                try {
                    $nextRebaseWorkspaceSequence = RebaseEmptyWorkspaceSequence::fromEvents($nextRebaseWorkspaceEvents);
                } catch (\Exception $exception) {
                    $this->log(sprintf('Error: %s', $exception->getMessage()));
                    $this->log(sprintf('    Debug: %s', json_encode($nextRebaseWorkspaceEvents)));
                    return;
                }

                $rebaseSequencesToPatchContentStream[] = new RebaseSequenceContentStreamPatch(
                    rebaseSequence: $nextRebaseWorkspaceSequence,
                    previousContentStreamIdPatch: $rebaseWorkspaceSequence->previousContentStreamId
                );
            }
        }

        if ($sequenceNumbersToRemove === []) {
            $this->log('Error no sequence numbers found to remove.');
            return;
        }

        $this->log(sprintf('Found %d events to be removed', count($sequenceNumbersToRemove)));
        $this->log(sprintf('    Debug: %s', join(',', array_column($sequenceNumbersToRemove, 'value'))));

        if ($rebaseSequencesToPatchContentStream !== []) {
            $this->log(sprintf('Found %d remaining workspace rebases to be adjusted after the deletion', count($rebaseSequencesToPatchContentStream)));
            $this->log(sprintf('    Debug: %s', join(',', array_map(fn (RebaseSequenceContentStreamPatch $patch) => $patch->rebaseSequence->correlationId->value, $rebaseSequencesToPatchContentStream))));
        }

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
                'sequenceNumbersToRemove' => array_column($sequenceNumbersToRemove, 'value'),
            ],
            [
                'sequenceNumbersToRemove' => ArrayParameterType::INTEGER
            ]
        );

        foreach ($rebaseSequencesToPatchContentStream as $rebaseSequenceContentStreamPatch) {
            // Update "ContentStreamWasClosed" to previous content stream
            $affectedRows += $this->context->dbal->executeStatement(
                <<<SQL
                UPDATE {$this->context->eventStoreTableName}
                SET stream = :stream, payload = JSON_SET(payload, '$.contentStreamId', :contentStreamId)
                WHERE sequencenumber = :sequenceNumber
                SQL,
                [
                    'sequenceNumber' => $rebaseSequenceContentStreamPatch->rebaseSequence->get(RebaseEmptyWorkspaceSequenceType::ContentStreamWasClosed)->sequenceNumber->value,
                    'contentStreamId' => $rebaseSequenceContentStreamPatch->previousContentStreamIdPatch->value,
                    'stream' => ContentStreamEventStreamName::fromContentStreamId($rebaseSequenceContentStreamPatch->previousContentStreamIdPatch)->value,
                ],
            );

            // Update "WorkspaceWasRebased" to previous content stream
            $affectedRows += $this->context->dbal->executeStatement(
                <<<SQL
                UPDATE {$this->context->eventStoreTableName}
                SET payload = JSON_SET(payload, '$.contentStreamId', :contentStreamId)
                WHERE sequencenumber = :sequenceNumber
                SQL,
                [
                    'sequenceNumber' => $rebaseSequenceContentStreamPatch->rebaseSequence->get(RebaseEmptyWorkspaceSequenceType::WorkspaceWasRebased)->sequenceNumber->value,
                    'contentStreamId' => $rebaseSequenceContentStreamPatch->previousContentStreamIdPatch->value,
                ],
            );

            // Update "ContentStreamWasRemoved" to previous content stream
            $affectedRows += $this->context->dbal->executeStatement(
                <<<SQL
                UPDATE {$this->context->eventStoreTableName}
                SET stream = :stream, payload = JSON_SET(payload, '$.contentStreamId', :contentStreamId)
                WHERE sequencenumber = :sequenceNumber
                SQL,
                [
                    'sequenceNumber' => $rebaseSequenceContentStreamPatch->rebaseSequence->get(RebaseEmptyWorkspaceSequenceType::ContentStreamWasRemoved)->sequenceNumber->value,
                    'contentStreamId' => $rebaseSequenceContentStreamPatch->previousContentStreamIdPatch->value,
                    'stream' => ContentStreamEventStreamName::fromContentStreamId($rebaseSequenceContentStreamPatch->previousContentStreamIdPatch)->value,
                ],
            );
        }

        $this->context->dbal->commit();

        $this->log('');
        $this->log(sprintf('Migration applied to %s events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`', $affectedRows));
        $this->log('Done.');
    }
}

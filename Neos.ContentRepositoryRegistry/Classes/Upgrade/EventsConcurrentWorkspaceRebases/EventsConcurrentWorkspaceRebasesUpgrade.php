<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

use Doctrine\DBAL\ArrayParameterType;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
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
        //
        // 1.)
        //
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

        # todo
        # assert that there are no new events on that stream except its next rebase

        $sequenceNumbersToRemove = [];

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

            $rebasedWorkspaceName = null;
            $rebaseWorkspaceSequence = RebaseWorkspaceSequence::start();
            foreach ($rebaseWorkspaceEvents as $rebaseWorkspaceEvent) {
                if ($rebaseWorkspaceEvent->event->type->value !== $rebaseWorkspaceSequence->value) {
                    $this->log(sprintf('Stream %s: Invalid rebase workspace sequence, expected %s got %s at %s', $rebaseWorkspaceEvent->streamName->value, $rebaseWorkspaceSequence->value, $rebaseWorkspaceEvent->event->type->value, $rebaseWorkspaceEvent->sequenceNumber->value));
                    $this->log(sprintf('    Debug: %s', json_encode($rebaseWorkspaceEvents)));
                    return;
                }

                if ($rebaseWorkspaceSequence === RebaseWorkspaceSequence::WorkspaceWasRebased) {
                    $rebasedWorkspaceName = WorkspaceName::fromString(
                        json_decode($rebaseWorkspaceEvent->event->data->value, true, flags: JSON_THROW_ON_ERROR)['workspaceName']
                    );
                }

                if ($rebaseWorkspaceSequence === RebaseWorkspaceSequence::ContentStreamWasForked) {
                    if (!$rebaseWorkspaceEvent->streamName->equals(ContentStreamEventStreamName::fromContentStreamId($newContentStreamId)->getEventStreamName())) {
                        $this->log(sprintf('Stream %s: Invalid stream, expected %s as %s', $rebaseWorkspaceEvent->streamName->value, $newContentStreamId->value, $rebaseWorkspaceEvent->sequenceNumber->value));
                        $this->log(sprintf('    Debug: %s', json_encode($rebaseWorkspaceEvents)));
                    }
                }

                $rebaseWorkspaceSequence = $rebaseWorkspaceSequence->next();
                $sequenceNumbersToRemove[] = $rebaseWorkspaceEvent->sequenceNumber->value;
            }
            if ($rebaseWorkspaceSequence !== RebaseWorkspaceSequence::ENDED || $rebasedWorkspaceName === null) {
                $this->log(sprintf('Invalid end of rebase workspace sequence %s, got %s', $forkCorrelationId->value, $rebaseWorkspaceSequence->value));
                $this->log(sprintf('    Debug: %s', json_encode($rebaseWorkspaceEvents)));
                return;
            }

            /** @var list<EventEnvelope> $rebaseWorkspaceEvents */
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
                $this->log(sprintf('TODO', json_encode($rebaseWorkspaceEvents)));
                return;
            }
        }

        if ($sequenceNumbersToRemove === []) {
            $this->log('Error no sequence numbers found to remove.');
            return;
        }

        $this->log(sprintf('Found %d events to be removed', count($sequenceNumbersToRemove)));
        $this->log(sprintf('    Debug: %s', join(',', $sequenceNumbersToRemove)));

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
}

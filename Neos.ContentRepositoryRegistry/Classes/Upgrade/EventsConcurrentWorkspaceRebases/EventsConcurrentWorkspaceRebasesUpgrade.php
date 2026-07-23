<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

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
        SELECT forked.sequencenumber AS sourceForkedAtPosition, removals.sequencenumber AS removedAtPosition, removals.contentstreamid FROM (
          SELECT JSON_UNQUOTE(JSON_EXTRACT(payload, '$.contentStreamId')) AS contentStreamId, sequencenumber FROM {$this->context->eventStoreTableName}
            WHERE type = 'ContentStreamWasRemoved'
        ) AS removals
          JOIN (SELECT sequencenumber, payload, JSON_UNQUOTE(JSON_EXTRACT(payload, '$.sourceContentStreamId')) as sourceContentStreamId FROM {$this->context->eventStoreTableName} WHERE type = 'ContentStreamWasForked') AS forked
            ON removals.contentstreamid = forked.sourcecontentstreamid
            AND removals.sequencenumber < forked.sequencenumber        
        SQL);

        if ($forkedContentStreamsWithAlreadyRemovedSourceContentStream === []) {
            $this->log('Migration was not necessary. No forks on already removed content streams.');
            return;
        }

        // allow nothing, or just another second ContentStreamWasForked

        # todo
        # assert that its a rebase
        # assert that there are no new events on that stream except its next rebase
        # assert

        \Neos\Flow\var_dump($forkedContentStreamsWithAlreadyRemovedSourceContentStream);
        die();

        foreach ($forkedContentStreamsWithAlreadyRemovedSourceContentStream as $forkedContentStreamWithAlreadyRemovedSourceContentStream) {
            $eventsToRemove = $this->context->dbal->fetchAllAssociative(<<<SQL
                
            SQL, [

            ]);
        }

        \Neos\Flow\var_dump($forkedContentStreamsWithAlreadyRemovedSourceContentStream);
        die();
    }
}

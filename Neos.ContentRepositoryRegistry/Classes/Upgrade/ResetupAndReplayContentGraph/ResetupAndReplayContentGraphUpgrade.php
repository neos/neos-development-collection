<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\ResetupAndReplayContentGraph;

use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\Projection\ProjectionStatusType;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\CRUpgradeContext;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\OutputMessageTrait;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\VirtualStreamName;

/**
 * Upgrade to allow to empty, set up and replay the graph projection in one step
 *
 * The CR provides a simple setup tooling via "./flow cr:setup" it allows to create the database schemas in the beginning
 * and also minor upgrades from one existing schema to the desired like index changes or small renames.
 *
 * Some Neos versions will include changes which go beyond this as they heavily adjust the schema.
 *
 * The following upgrade is required:
 *
 *  - 1. Reset (drop old tables),
 *  - 2. Setup (create new empty tables)
 *  - 3. Replay (refill new tables)
 *
 * Attempting to upgrade with "./flow cr:setup" in step 2 without dropping the old content graph tables would fail.
 *
 * Included in June 2026 - part of the minor 9.2.0 release
 */
final readonly class ResetupAndReplayContentGraphUpgrade
{
    use OutputMessageTrait;

    private SubscriptionEngine $subscriptionEngine;

    private SubscriptionStoreInterface $subscriptionStore;

    private SubscriptionId $contentGraphSubscriptionId;

    /** Same setting as {@see ContentRepositoryMaintainer::REPLAY_BATCH_SIZE} */
    private const REPLAY_BATCH_SIZE = 500;

    public function __construct(
        private CRUpgradeContext $context,
        private \Closure $outputFn,
        private \Closure $replayProgressCallback,
        private ContentRepositoryMaintainer $contentRepositoryMaintainer,
    ) {
        $subscriptionEngine = (new \ReflectionClass(ContentRepositoryMaintainer::class))->getProperty('subscriptionEngine')->getValue($this->contentRepositoryMaintainer);
        $subscriptionStore = (new \ReflectionClass(SubscriptionEngine::class))->getProperty('subscriptionStore')->getValue($subscriptionEngine);

        $this->contentGraphSubscriptionId = SubscriptionId::fromString('contentGraph');
        $this->subscriptionEngine = $subscriptionEngine;
        $this->subscriptionStore = $subscriptionStore;
    }

    public function execute(bool $force): void
    {
        if (!$force) {
            $eventStorePosition = $this->getEventStorePosition();
            $graphProjectionStatus = $this->getGraphProjectionStatus();
            $this->log(
                sprintf('    DEBUG: Content graph projection schema status "%s". Subscription was at sequence %d of available %d with status "%s"' . PHP_EOL, $graphProjectionStatus->setupStatus->type->name, $graphProjectionStatus->subscriptionPosition->value, $eventStorePosition->value, $graphProjectionStatus->subscriptionStatus->value)
            );
            if ($eventStorePosition->value > $graphProjectionStatus->subscriptionPosition->value) {
                $this->log(sprintf(
                    'Content graph projection is with position %1$d behind event-store position %2$d. See `flow cr:status`. Not continuing not replaying the projection as we will not invoke catchup hooks for the graph events after %1$d. If you know what you are doing try again by using a bit more force.',
                    $graphProjectionStatus->subscriptionPosition->value,
                    $eventStorePosition->value,
                ));
                return;
            }
            if ($graphProjectionStatus->setupStatus->type === ProjectionStatusType::OK) {
                $this->log(
                    'Content graph projection is fully set-up. See `flow cr:status`. Not continuing emptying the projection. If you know what you are doing try again by using a bit more force.'
                );
                return;
            }
        }

        $tableNames = ContentGraphTableNames::create($this->context->contentRepositoryId);

        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->node());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->hierarchyRelation());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->referenceRelation());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->dimensionSpacePoints());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->workspace());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->contentStream());
        // New table introduced via Neos 9.2.0
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->contentStreamLayer());

        $this->log('Dropped all existing graph projection tables.');
        $this->log('Running schema setup ...');

        $this->subscriptionStore->update(
            $this->contentGraphSubscriptionId,
            SubscriptionStatus::BOOTING,
            SequenceNumber::none(),
            null
        );
        $result = $this->contentRepositoryMaintainer->setUp();
        if ($result !== null) {
            $this->log(sprintf('Unexpected error during setup: <error>%s</error>', $result->getMessage()));
            return;
        }

        $this->log(sprintf('Content repository "%s" was set up', $this->context->contentRepositoryId->value));
        $this->log('Replaying the content graph projection (without invoking its catchup hooks) ...');

        $this->subscriptionEngine->withoutProjectionSubscriberCatchupHooks()->boot(SubscriptionEngineCriteria::create(ids: [$this->contentGraphSubscriptionId]), progressCallback: $this->replayProgressCallback, batchSize: self::REPLAY_BATCH_SIZE);
    }

    private function getEventStorePosition(): SequenceNumber
    {
        $lastEventEnvelope = current(iterator_to_array($this->context->doctrineEventStore->load(VirtualStreamName::all())->backwards()->limit(1))) ?: null;
        return $lastEventEnvelope?->sequenceNumber ?? SequenceNumber::none();
    }

    private function getGraphProjectionStatus(): ProjectionSubscriptionStatus
    {
        $status = $this->contentRepositoryMaintainer->status();
        foreach ($status->subscriptionStatus as $status) {
            if ($status instanceof ProjectionSubscriptionStatus) {
                if ($status->subscriptionId->equals($this->contentGraphSubscriptionId)) {
                    return $status;
                }
            }
        }
        throw new \RuntimeException('Fatal: Status of "contentGraph" is not available but required to.', 1782224867);
    }
}

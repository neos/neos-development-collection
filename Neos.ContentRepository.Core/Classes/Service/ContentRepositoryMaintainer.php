<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryStatus;
use Neos\ContentRepository\Core\Subscription\Engine\Errors;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusCollection;
use Neos\Error\Messages\Error;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\Event\EventTypes;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventStream\EventStreamFilter;
use Neos\EventStore\Model\EventStream\VirtualStreamName;

/**
 * Set up and manage a content repository
 *
 * Initialisation / Tear down
 * --------------------------
 * The method {@see setUp} sets up the content repository like event store and subscription database tables.
 * It is non-destructive.
 *
 * Resetting a content repository with {@see prune} method will purge the event stream and reset all subscription states.
 *
 * Status information
 * ------------------
 * The status of the content repository e.g. if a setup is required or if all subscriptions are active and their position
 * can be examined with {@see status}
 *
 * The event store status is available via {@see ContentRepositoryStatus::$eventStoreStatus}, and the subscription status
 * via {@see ContentRepositoryStatus::$subscriptionStatus}. Further documentation in {@see SubscriptionStatusCollection}.
 *
 * Subscriptions (mainly projections)
 * ----------------------------------
 *
 * This maintainer offers also the public API to interact with the subscription catchup. In the happy path,
 * no interaction is necessary, as {@see ContentRepository::handle()} triggers the subscriptions after applying the events.
 *
 * Special cases:
 *
 * *Replay for initialisation*
 *
 * For initialising on a new database - which contains events already - a replay will make sure that the subscriptions
 * are emptied and reapply the events. This can be triggered via {@see replaySubscription} or {@see replayAllSubscriptions}
 *
 * And after registering a new subscription a setup as well as a replay of this subscription is also required.
 *
 * *Replay to repair*
 *
 * In case a subscription is detached and then reinstalled a replay will make sure its caught up to all new events.
 * And that the previous state will be reset as the projections logic might have changed.
 *
 * Also in case a subscription runs into the error status, its code needs to be fixed, and it can be attempted to be replayed.
 *
 * @api
 */
final readonly class ContentRepositoryMaintainer implements ContentRepositoryServiceInterface
{
    private const REPLAY_BATCH_SIZE = 500;

    /**
     * @internal please use the {@see ContentRepositoryMaintainerFactory} instead!
     */
    public function __construct(
        private EventStoreInterface $eventStore,
        private SubscriptionEngine $subscriptionEngine
    ) {
    }

    public function setUp(): Error|null
    {
        $this->eventStore->setup();
        $eventStoreIsEmpty = iterator_count($this->eventStore->load(VirtualStreamName::all())->limit(1)) === 0;
        $setupResult = $this->subscriptionEngine->setup();
        if ($setupResult->errors !== null) {
            return self::createErrorForReason('Setup failed:', $setupResult->errors);
        }
        if ($eventStoreIsEmpty) {
            // note: possibly introduce $skipBooting flag instead
            // see https://github.com/patchlevel/event-sourcing/blob/b8591c56b21b049f46bead8e7ab424fd2afe9917/src/Subscription/Engine/DefaultSubscriptionEngine.php#L42
            $bootResult = $this->subscriptionEngine->boot();
            if ($bootResult->errors !== null) {
                return self::createErrorForReason('Initial catchup failed:', $bootResult->errors);
            }
        }
        return null;
    }

    public function status(): ContentRepositoryStatus
    {
        try {
            $lastEventEnvelope = current(iterator_to_array($this->eventStore->load(VirtualStreamName::all())->backwards()->limit(1))) ?: null;
            $sequenceNumber = $lastEventEnvelope?->sequenceNumber ?? SequenceNumber::none();
        } catch (\Doctrine\DBAL\Exception) {
            // todo do not depend on the dbal exception as this is totally implementation specific and the core has no dependency to dbal!
            $sequenceNumber = null;
        }

        return ContentRepositoryStatus::create(
            $this->eventStore->status(),
            $sequenceNumber,
            $this->subscriptionEngine->subscriptionStatus()
        );
    }

    public function replaySubscription(SubscriptionId $subscriptionId, \Closure|null $progressCallback = null): Error|null
    {
        $subscriptionStatus = $this->subscriptionEngine->subscriptionStatus(SubscriptionEngineCriteria::create([$subscriptionId]))->first();
        if ($subscriptionStatus === null) {
            return new Error(sprintf('Subscription "%s" is not registered.', $subscriptionId->value));
        }
        if ($subscriptionStatus->subscriptionStatus === SubscriptionStatus::NEW) {
            return new Error(sprintf('Subscription "%s" is not setup and cannot be replayed.', $subscriptionId->value));
        }
        $resetResult = $this->subscriptionEngine->reset(SubscriptionEngineCriteria::create([$subscriptionId]));
        if ($resetResult->errors !== null) {
            return self::createErrorForReason('Reset failed:', $resetResult->errors);
        }
        $bootResult = $this->subscriptionEngine->boot(SubscriptionEngineCriteria::create([$subscriptionId]), progressCallback: $progressCallback, batchSize: self::REPLAY_BATCH_SIZE);
        if ($bootResult->errors !== null) {
            return self::createErrorForReason('Catchup failed:', $bootResult->errors);
        }
        return null;
    }

    public function replayAllSubscriptions(\Closure|null $progressCallback = null): Error|null
    {
        $resetResult = $this->subscriptionEngine->reset();
        if ($resetResult->errors !== null) {
            return self::createErrorForReason('Reset failed:', $resetResult->errors);
        }
        $bootResult = $this->subscriptionEngine->boot(progressCallback: $progressCallback, batchSize: self::REPLAY_BATCH_SIZE);
        if ($bootResult->errors !== null) {
            return self::createErrorForReason('Catchup failed:', $bootResult->errors);
        }
        return null;
    }

    /**
     * Reactivate a subscription
     *
     * The explicit catchup is only needed for subscriptions in the error or detached status with an advanced position.
     * Running a full replay would work but might be overkill, instead this reactivation will just attempt
     * catchup the subscription back to active from its current position.
     *
     * @internal reactivation is an experimental and advanced concept, if possible a replay should be used instead which is more stable
     * Problematic can be events where the projection did partially apply them (some commited queries) but then suddenly crashed.
     * A reactivation attempts to fully reapply that event which can conflict with work already done.
     */
    public function reactivateSubscription(SubscriptionId $subscriptionId, \Closure|null $progressCallback = null): Error|null
    {
        $subscriptionStatus = $this->subscriptionEngine->subscriptionStatus(SubscriptionEngineCriteria::create([$subscriptionId]))->first();
        if ($subscriptionStatus === null) {
            return new Error(sprintf('Subscription "%s" is not registered.', $subscriptionId->value));
        }
        if ($subscriptionStatus->subscriptionStatus === SubscriptionStatus::NEW) {
            return new Error(sprintf('Subscription "%s" is not setup and cannot be reactivated.', $subscriptionId->value));
        }
        $reactivateResult = $this->subscriptionEngine->reactivate(SubscriptionEngineCriteria::create([$subscriptionId]), progressCallback: $progressCallback, batchSize: self::REPLAY_BATCH_SIZE);
        if ($reactivateResult->errors !== null) {
            return self::createErrorForReason('Could not reactivate subscriber:', $reactivateResult->errors);
        }
        return null;
    }

    /**
     * WARNING: Removes all events from the content repository and resets the subscriptions
     * This operation cannot be undone.
     */
    public function prune(): Error|null
    {
        // prune all streams:
        foreach ($this->findAllContentStreamStreamNames() as $contentStreamStreamName) {
            $this->eventStore->deleteStream($contentStreamStreamName);
        }
        foreach ($this->findAllWorkspaceStreamNames() as $workspaceStreamName) {
            $this->eventStore->deleteStream($workspaceStreamName);
        }
        $resetResult = $this->subscriptionEngine->reset();
        if ($resetResult->errors !== null) {
            return self::createErrorForReason('Reset failed:', $resetResult->errors);
        }
        // note: possibly introduce $skipBooting flag like for setup
        $bootResult = $this->subscriptionEngine->boot();
        if ($bootResult->errors !== null) {
            return self::createErrorForReason('Catchup failed:', $bootResult->errors);
        }
        return null;
    }

    private static function createErrorForReason(string $method, Errors $errors): Error
    {
        $message = [
            sprintf('%s Following error%s', $method, $errors->count() === 1 ? '' : 's'),
            ...array_map(fn (string $line) => '    ' . $line, explode("\n", $errors->getClampedMessage()))
        ];
        return new Error(join("\n", $message));
    }

    /**
     * @return list<StreamName>
     */
    private function findAllContentStreamStreamNames(): array
    {
        $events = $this->eventStore->load(
            VirtualStreamName::forCategory(ContentStreamEventStreamName::EVENT_STREAM_NAME_PREFIX),
            EventStreamFilter::create(
                EventTypes::create(
                    // we are only interested in the creation events to limit the amount of events to fetch
                    EventType::fromString('ContentStreamWasCreated'),
                    EventType::fromString('ContentStreamWasForked')
                )
            )
        );
        $allStreamNames = [];
        foreach ($events as $eventEnvelope) {
            $allStreamNames[] = $eventEnvelope->streamName;
        }
        return array_unique($allStreamNames, SORT_REGULAR);
    }

    /**
     * @return list<StreamName>
     */
    private function findAllWorkspaceStreamNames(): array
    {
        $events = $this->eventStore->load(
            VirtualStreamName::forCategory(WorkspaceEventStreamName::EVENT_STREAM_NAME_PREFIX),
            EventStreamFilter::create(
                EventTypes::create(
                    // we are only interested in the creation events to limit the amount of events to fetch
                    EventType::fromString('RootWorkspaceWasCreated'),
                    EventType::fromString('WorkspaceWasCreated')
                )
            )
        );
        $allStreamNames = [];
        foreach ($events as $eventEnvelope) {
            $allStreamNames[] = $eventEnvelope->streamName;
        }
        return array_unique($allStreamNames, SORT_REGULAR);
    }
}

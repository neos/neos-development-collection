<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Engine;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Subscription\DetachedSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\Exception\SubscriptionEngineAlreadyProcessingException;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionCriteria;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Core\Subscription\Subscriber\Subscribers;
use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusCollection;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusFilter;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Psr\Log\LoggerInterface;

/**
 * This is the internal core for the catchup
 *
 * All functionality is low level and well encapsulated and abstracted by the {@see ContentRepositoryMaintainer}
 * It presents the only API way to interact with catchup and offers more maintenance tasks.
 *
 * This implementation is heavily inspired and adjusted from the event-sourcing package of "patchlevel":
 * {@link https://github.com/patchlevel/event-sourcing/}
 *
 * @internal implementation detail of the catchup. See {@see ContentRepository::handle()} and {@see ContentRepositoryMaintainer}
 */
final class SubscriptionEngine
{
    private bool $processing = false;

    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly SubscriptionStoreInterface $subscriptionStore,
        private readonly Subscribers $subscribers,
        private readonly EventNormalizer $eventNormalizer,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function setup(SubscriptionEngineCriteria|null $criteria = null): Result
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();

        $this->logger?->info('Subscription Engine: Start to setup.');

        $this->subscriptionStore->setup();
        $this->discoverNewSubscriptions();
        $subscriptions = $this->subscriptionStore->findByCriteriaForUpdate(SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, SubscriptionStatusFilter::fromArray([
            SubscriptionStatus::NEW,
            SubscriptionStatus::BOOTING,
            SubscriptionStatus::ACTIVE
        ])));
        if ($subscriptions->isEmpty()) {
            // should not happen as this means the contentGraph is unavailable, see status information.
            $this->logger?->info('Subscription Engine: No subscriptions found.');
            return Result::success();
        }
        $errors = [];
        foreach ($subscriptions as $subscription) {
            $error = $this->setupSubscription($subscription);
            if ($error !== null) {
                $errors[] = $error;
            }
        }
        return $errors === [] ? Result::success() : Result::failed(Errors::fromArray($errors));
    }

    public function boot(SubscriptionEngineCriteria|null $criteria = null, ?\Closure $progressCallback = null, ?int $batchSize = null): ProcessedResult
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();
        return $this->processExclusively(
            fn () => $this->catchUpSubscriptions($criteria, SubscriptionStatusFilter::fromArray([SubscriptionStatus::BOOTING]), $progressCallback, $batchSize)
        );
    }

    public function catchUpActive(SubscriptionEngineCriteria|null $criteria = null, ?\Closure $progressCallback = null, ?int $batchSize = null): ProcessedResult
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();
        return $this->processExclusively(
            fn () => $this->catchUpSubscriptions($criteria, SubscriptionStatusFilter::fromArray([SubscriptionStatus::ACTIVE]), $progressCallback, $batchSize)
        );
    }

    public function reactivate(SubscriptionEngineCriteria|null $criteria = null, ?\Closure $progressCallback = null, ?int $batchSize = null): ProcessedResult
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();
        return $this->processExclusively(
            fn () => $this->catchUpSubscriptions($criteria, SubscriptionStatusFilter::fromArray([SubscriptionStatus::ERROR, SubscriptionStatus::DETACHED]), $progressCallback, $batchSize)
        );
    }

    public function reset(SubscriptionEngineCriteria|null $criteria = null): Result
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();

        $this->logger?->info('Subscription Engine: Start to reset.');
        $subscriptions = $this->subscriptionStore->findByCriteriaForUpdate(SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, SubscriptionStatusFilter::any()));
        if ($subscriptions->isEmpty()) {
            $this->logger?->info('Subscription Engine: No subscriptions to reset.');
            return Result::success();
        }
        $errors = [];
        foreach ($subscriptions as $subscription) {
            if (
                $subscription->status === SubscriptionStatus::NEW
                || !$this->subscribers->contain($subscription->id)
            ) {
                // Todo mark projections as detached like setup or catchup?
                continue;
            }
            $error = $this->resetSubscription($subscription);
            if ($error !== null) {
                $errors[] = $error;
            }
        }
        return $errors === [] ? Result::success() : Result::failed(Errors::fromArray($errors));
    }

    public function subscriptionStatus(SubscriptionEngineCriteria|null $criteria = null): SubscriptionStatusCollection
    {
        $statuses = [];
        try {
            $subscriptions = $this->subscriptionStore->findByCriteriaForUpdate(SubscriptionCriteria::create(ids: $criteria?->ids));
        } catch (TableNotFoundException) {
            // the schema is not setup - thus there are no subscribers
            return SubscriptionStatusCollection::createEmpty();
        }
        foreach ($subscriptions as $subscription) {
            if (!$this->subscribers->contain($subscription->id)) {
                $statuses[] = DetachedSubscriptionStatus::create(
                    $subscription->id,
                    $subscription->status,
                    $subscription->position
                );
                continue;
            }
            $subscriber = $this->subscribers->get($subscription->id);
            $statuses[] = ProjectionSubscriptionStatus::create(
                subscriptionId: $subscription->id,
                subscriptionStatus: $subscription->status,
                subscriptionPosition: $subscription->position,
                subscriptionError: $subscription->error,
                setupStatus: $subscriber->projection->status(),
            );
        }
        foreach ($this->subscribers as $subscriber) {
            if ($subscriptions->contain($subscriber->id)) {
                continue;
            }
            if ($criteria?->ids?->contain($subscriber->id) === false) {
                // this might be a NEW subscription but we dont return it as status is filtered.
                continue;
            }
            // this NEW state is not persisted yet
            $statuses[] = ProjectionSubscriptionStatus::create(
                subscriptionId: $subscriber->id,
                subscriptionStatus: SubscriptionStatus::NEW,
                subscriptionPosition: SequenceNumber::none(),
                subscriptionError: null,
                setupStatus: $subscriber->projection->status(),
            );
        }
        return SubscriptionStatusCollection::fromArray($statuses);
    }

    /**
     * Find all subscribers that don't have a corresponding subscription.
     * For each match a subscription is added
     *
     * Note: newly discovered subscriptions are not ACTIVE by default, instead they have to be initialized via {@see self::setup()} explicitly
     */
    private function discoverNewSubscriptions(): void
    {
        $subscriptions = $this->subscriptionStore->findByCriteriaForUpdate(SubscriptionCriteria::noConstraints());
        foreach ($this->subscribers as $subscriber) {
            if ($subscriptions->contain($subscriber->id)) {
                continue;
            }
            $subscription = new Subscription(
                $subscriber->id,
                SubscriptionStatus::NEW,
                SequenceNumber::fromInteger(0),
                null,
                null
            );
            $this->subscriptionStore->add($subscription);
            $this->logger?->info(sprintf('Subscription Engine: New Subscriber "%s" was found and added to the subscription store.', $subscriber->id->value));
        }
    }

    /**
     * Set up the subscription by retrieving the corresponding subscriber and calling the setUp method on its handler
     * If the setup fails, the subscription will be in the {@see SubscriptionStatus::ERROR} state and a corresponding {@see Error} is returned
     */
    private function setupSubscription(Subscription $subscription): ?Error
    {
        if (!$this->subscribers->contain($subscription->id)) {
            // mark detached subscriptions as we cannot set up
            $this->subscriptionStore->update(
                $subscription->id,
                status: SubscriptionStatus::DETACHED,
                position: $subscription->position,
                subscriptionError: $subscription->error
            );
            $this->logger?->info(sprintf('Subscription Engine: Subscriber for "%s" not found and has been marked as detached.', $subscription->id->value));
            return null;
        }

        $subscriber = $this->subscribers->get($subscription->id);
        try {
            $subscriber->projection->setUp();
        } catch (\Throwable $e) {
            $this->logger?->error(sprintf('Subscription Engine: Subscriber "%s" for "%s" has an error in the setup method: %s', $subscriber::class, $subscription->id->value, $e->getMessage()));
            $this->subscriptionStore->update(
                $subscription->id,
                SubscriptionStatus::ERROR,
                $subscription->position,
                SubscriptionError::fromPreviousStatusAndException($subscription->status, $e)
            );
            return Error::create($subscription->id, $e->getMessage(), $e, null);
        }

        if ($subscription->status === SubscriptionStatus::ACTIVE) {
            $this->logger?->debug(sprintf('Subscription Engine: Active subscriber "%s" for "%s" has been re-setup.', $subscriber::class, $subscription->id->value));
            return null;
        } else {
            $this->subscriptionStore->update(
                $subscription->id,
                SubscriptionStatus::BOOTING,
                $subscription->position,
                null
            );
        }
        $this->logger?->debug(sprintf('Subscription Engine: Subscriber "%s" for "%s" has been setup, set to %s from previous %s.', $subscriber::class, $subscription->id->value, SubscriptionStatus::BOOTING->value, $subscription->status->name));
        return null;
    }

    private function resetSubscription(Subscription $subscription): ?Error
    {
        $subscriber = $this->subscribers->get($subscription->id);
        try {
            $subscriber->projection->resetState();
        } catch (\Throwable $e) {
            $this->logger?->error(sprintf('Subscription Engine: Subscriber "%s" for "%s" has an error in the resetState method: %s', $subscriber::class, $subscription->id->value, $e->getMessage()));
            return Error::create($subscription->id, $e->getMessage(), $e, null);
        }
        $this->subscriptionStore->update(
            $subscription->id,
            SubscriptionStatus::BOOTING,
            position: SequenceNumber::none(),
            subscriptionError: null
        );
        $this->logger?->debug(sprintf('Subscription Engine: For Subscriber "%s" for "%s" the resetState method has been executed.', $subscriber::class, $subscription->id->value));
        return null;
    }

    /**
     * @param \Closure|null $progressCallback The callback that is invoked for every {@see EventEnvelope} that is processed per subscriber
     * @param int|null $batchSize Number of events to process before the transaction is commited and reopened. (defaults to all events).
     */
    private function catchUpSubscriptions(SubscriptionEngineCriteria $criteria, SubscriptionStatusFilter $status, \Closure|null $progressCallback, int|null $batchSize): ProcessedResult
    {
        if ($batchSize !== null && $batchSize <= 0) {
            throw new \InvalidArgumentException(sprintf('Invalid batchSize %d specified, must be either NULL or a positive integer.', $batchSize), 1733597950);
        }

        $this->logger?->info(sprintf('Subscription Engine: Start catching up subscriptions in states %s.', join(',', $status->toStringArray())));

        $subscriptionCriteria = SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, $status);

        $numberOfProcessedEvents = 0;
        /** @var array<Error> $errors */
        $errors = [];

        $this->subscriptionStore->beginTransaction();

        $subscriptionsToCatchup = $this->subscriptionStore->findByCriteriaForUpdate($subscriptionCriteria);
        foreach ($subscriptionsToCatchup as $subscription) {
            if (!$this->subscribers->contain($subscription->id)) {
                // mark detached subscriptions as we cannot handle them and exclude them from catchup
                $this->subscriptionStore->update(
                    $subscription->id,
                    status: SubscriptionStatus::DETACHED,
                    position: $subscription->position,
                    subscriptionError: null,
                );
                $this->logger?->info(sprintf('Subscription Engine: Subscriber for "%s" not found and has been marked as detached.', $subscription->id->value));
                $subscriptionsToCatchup = $subscriptionsToCatchup->without($subscription->id);
            }
        }

        if ($subscriptionsToCatchup->isEmpty()) {
            $this->logger?->info('Subscription Engine: No subscriptions matched criteria. Finishing catch up.');
            $this->subscriptionStore->commit();
            return ProcessedResult::success(0);
        }

        $subscriptionIdsToInvokeAroundCatchUpHooks = $subscriptionsToCatchup->getIds();
        foreach ($subscriptionsToCatchup as $subscription) {
            $subscriber = $this->subscribers->get($subscription->id);
            try {
                $subscriber->catchUpHook?->onBeforeCatchUp($subscription->status);
            } catch (\Throwable $e) {
                $errors[] = $error = Error::create($subscription->id, $e->getMessage(), $errors === [] ? $e : null, position: null);
                $this->logCatchupHookError($error);
            }
        }

        while (true) {
            /**
             * If batching is enabled, the {@see $continueBatching} flag will indicate that the last run was stopped and continuation is necessary to handle the rest of the events.
             * It's possible that batching stops at the last event, in that case the transaction is still reopened to set the active state correctly.
             */
            $continueBatching = false;

            $startSequenceNumber = $subscriptionsToCatchup->lowestPosition()?->next() ?? SequenceNumber::none();
            $this->logger?->debug(sprintf('Subscription Engine: Event stream is processed from position %s.', $startSequenceNumber->value));

            /** @var array<string,SequenceNumber> $highestSequenceNumberForSubscriber */
            $highestSequenceNumberForSubscriber = [];

            $eventStream = $this->eventStore->load(VirtualStreamName::all())->withMinimumSequenceNumber($startSequenceNumber);
            foreach ($eventStream as $eventEnvelope) {
                $sequenceNumber = $eventEnvelope->sequenceNumber;
                if ($numberOfProcessedEvents > 0) {
                    $this->logger?->debug(sprintf('Subscription Engine: Current event stream position: %s', $sequenceNumber->value));
                }
                if ($progressCallback !== null) {
                    $progressCallback($eventEnvelope);
                }
                $domainEvent = $this->eventNormalizer->denormalize($eventEnvelope->event);
                foreach ($subscriptionsToCatchup as $subscription) {
                    if ($subscription->position->value >= $sequenceNumber->value) {
                        $this->logger?->debug(sprintf('Subscription Engine: Subscription "%s" is farther than the current position (%d >= %d), continue catch up.', $subscription->id->value, $subscription->position->value, $sequenceNumber->value));
                        continue;
                    }
                    if (!$subscriptionIdsToInvokeAroundCatchUpHooks->contain($subscription->id)) {
                        $this->logger?->info(sprintf('Subscription Engine: Subscription "%s" with status "%s" was not part of the first batch, continue catch up.', $subscription->id->value, $subscription->status->value));
                        continue;
                    }
                    $subscriber = $this->subscribers->get($subscription->id);

                    try {
                        $subscriber->catchUpHook?->onBeforeEvent($domainEvent, $eventEnvelope);
                    } catch (\Throwable $e) {
                        $errors[] = $error = Error::create($subscription->id, $e->getMessage(), $errors === [] ? $e : null, $eventEnvelope->sequenceNumber);
                        $this->logCatchupHookError($error);
                    }

                    try {
                        $subscriber->projection->apply($domainEvent, $eventEnvelope);
                    } catch (\Throwable $e) {
                        // ERROR Case:
                        $errors[] = Error::create($subscription->id, $e->getMessage(), $errors === [] ? $e : null, $eventEnvelope->sequenceNumber);
                        $this->logger?->error(sprintf('Subscription Engine: Subscriber "%s" for "%s" could not process the event "%s" (sequence number: %d): %s', $subscriber::class, $subscription->id->value, $eventEnvelope->event->type->value, $eventEnvelope->sequenceNumber->value, $e->getMessage()));

                        // for the leftover events we are not including this failed subscription for catchup
                        $subscriptionsToCatchup = $subscriptionsToCatchup->without($subscription->id);
                        // update the subscription error state on either its unchanged or new position (if some events worked)
                        // note that the possibly partially applied event will not be rolled back.
                        $this->subscriptionStore->update(
                            $subscription->id,
                            status: SubscriptionStatus::ERROR,
                            position: $highestSequenceNumberForSubscriber[$subscription->id->value] ?? $subscription->position,
                            subscriptionError: SubscriptionError::fromPreviousStatusAndException(
                                $subscription->status,
                                $e
                            ),
                        );
                        continue;
                    }
                    // HAPPY Case:
                    $this->logger?->debug(sprintf('Subscription Engine: Subscriber "%s" for "%s" processed the event "%s" (sequence number: %d).', substr(strrchr($subscriber::class, '\\') ?: '', 1), $subscription->id->value, $eventEnvelope->event->type->value, $eventEnvelope->sequenceNumber->value));
                    $highestSequenceNumberForSubscriber[$subscription->id->value] = $eventEnvelope->sequenceNumber;

                    try {
                        $subscriber->catchUpHook?->onAfterEvent($domainEvent, $eventEnvelope);
                    } catch (\Throwable $e) {
                        $errors[] = $error = Error::create($subscription->id, $e->getMessage(), $errors === [] ? $e : null, $eventEnvelope->sequenceNumber);
                        $this->logCatchupHookError($error);
                    }
                }
                $numberOfProcessedEvents++;
                if ($batchSize !== null && $numberOfProcessedEvents % $batchSize === 0) {
                    $continueBatching = true;
                    $this->logger?->info(sprintf('Subscription Engine: Batch completed with %d events', $numberOfProcessedEvents));
                    break;
                }
            }
            foreach ($subscriptionsToCatchup as $subscription) {
                // after catchup mark all subscriptions as active, so they are triggered automatically now.
                // The position will be set to the one the subscriber handled last, or if no events were in the stream, and we booted we keep the persisted position
                $this->subscriptionStore->update(
                    $subscription->id,
                    status: $continueBatching === false ? SubscriptionStatus::ACTIVE : $subscription->status,
                    position: $highestSequenceNumberForSubscriber[$subscription->id->value] ?? $subscription->position,
                    subscriptionError: null,
                );
                if ($continueBatching === false && $subscription->status !== SubscriptionStatus::ACTIVE) {
                    $this->logger?->info(sprintf('Subscription Engine: Subscription "%s" has been set to active after booting', $subscription->id->value));
                }
            }
            $this->logger?->info(sprintf('Subscription Engine: Finish catch up. %d processed events %d errors.', $numberOfProcessedEvents, count($errors)));

            $this->subscriptionStore->commit();

            foreach ($subscriptionIdsToInvokeAroundCatchUpHooks as $subscriptionId) {
                try {
                    $this->subscribers->get($subscriptionId)->catchUpHook?->onAfterBatchCompleted();
                } catch (\Throwable $e) {
                    $errors[] = $error = Error::create($subscriptionId, $e->getMessage(), $errors === [] ? $e : null, position: null);
                    $this->logCatchupHookError($error);
                }
            }

            if ($continueBatching === true && $errors === []) {
                // start new batch
                $this->subscriptionStore->beginTransaction();
                $subscriptionsToCatchup = $this->subscriptionStore->findByCriteriaForUpdate($subscriptionCriteria);
            } else {
                break;
            }
        }

        foreach ($subscriptionIdsToInvokeAroundCatchUpHooks as $subscriptionId) {
            try {
                $this->subscribers->get($subscriptionId)->catchUpHook?->onAfterCatchUp();
            } catch (\Throwable $e) {
                $errors[] = $error = Error::create($subscriptionId, $e->getMessage(), $errors === [] ? $e : null, position: null);
                $this->logCatchupHookError($error);
            }
        }

        return $errors === [] ? ProcessedResult::success($numberOfProcessedEvents) : ProcessedResult::failed($numberOfProcessedEvents, Errors::fromArray($errors));
    }

    private function logCatchupHookError(Error $error): void
    {
        $this->logger?->error(
            sprintf('Subscription Engine: Subscription %s has error in catchup hook: %s', $error->subscriptionId->value, $error->message)
        );
    }

    /**
     * @template T
     * @param \Closure(): T $closure
     * @return T
     */
    private function processExclusively(\Closure $closure): mixed
    {
        if ($this->processing) {
            throw new SubscriptionEngineAlreadyProcessingException('Subscription engine is already processing', 1732714075);
        }
        $this->processing = true;
        try {
            return $closure();
        } finally {
            $this->processing = false;
        }
    }
}

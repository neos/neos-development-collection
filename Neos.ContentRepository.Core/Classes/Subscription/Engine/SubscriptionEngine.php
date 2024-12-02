<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Engine;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Subscription\DetachedSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\Exception\CatchUpFailed;
use Neos\ContentRepository\Core\Subscription\Exception\SubscriptionEngineAlreadyProcessingException;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionCriteria;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Core\Subscription\Subscriber\Subscribers;
use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\Subscriptions;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusCollection;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusFilter;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Psr\Log\LoggerInterface;

/**
 * This is the internal core for the catchup
 *
 * All functionality is low level and well encapsulated and abstracted by the {@see ContentRepositoryMaintainer}
 * It presents the only API way to interact with catchup and offers more maintenance tasks.
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
        $subscriptions = $this->subscriptionStore->findByCriteria(SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, SubscriptionStatusFilter::fromArray([
            SubscriptionStatus::NEW,
            SubscriptionStatus::BOOTING,
            SubscriptionStatus::ACTIVE
        ])));
        if ($subscriptions->isEmpty()) {
            $this->logger?->info('Subscription Engine: No subscriptions found.'); // todo not happy? Because there must be at least the content graph?!!
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

    public function boot(SubscriptionEngineCriteria|null $criteria = null, \Closure $progressCallback = null): ProcessedResult
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();
        return $this->processExclusively(
            function () use ($criteria, $progressCallback) {
                $this->logger?->info('Subscription Engine: Start catching up subscriptions in state "BOOTING".');
                $subscriptionsToCatchup = $this->subscriptionStore->findByCriteria(
                    SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, SubscriptionStatus::BOOTING)
                );
                return $this->catchUpSubscriptions($subscriptionsToCatchup, $progressCallback);
            }
        );
    }

    public function catchUpActive(SubscriptionEngineCriteria|null $criteria = null, \Closure $progressCallback = null): ProcessedResult
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();
        return $this->processExclusively(
            function () use ($criteria, $progressCallback) {
                $this->logger?->info('Subscription Engine: Start catching up subscriptions in state "ACTIVE".');
                $subscriptionsToCatchup = $this->subscriptionStore->findByCriteria(
                    SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, SubscriptionStatus::ACTIVE)
                );
                return $this->catchUpSubscriptions($subscriptionsToCatchup, $progressCallback);
            }
        );
    }

    public function reactivate(SubscriptionEngineCriteria|null $criteria = null, \Closure $progressCallback = null): ProcessedResult
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();
        return $this->processExclusively(
            function () use ($criteria, $progressCallback) {
                $this->logger?->info('Subscription Engine: Start catching up subscriptions in state "ACTIVE".');
                $subscriptionsToCatchup = $this->subscriptionStore->findByCriteria(
                    SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, SubscriptionStatusFilter::fromArray([
                        SubscriptionStatus::ERROR,
                        SubscriptionStatus::DETACHED,
                    ]))
                );
                return $this->catchUpSubscriptions($subscriptionsToCatchup, $progressCallback);
            }
        );
    }

    public function reset(SubscriptionEngineCriteria|null $criteria = null): Result
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();

        $this->logger?->info('Subscription Engine: Start to reset.');
        $subscriptions = $this->subscriptionStore->findByCriteria(SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, SubscriptionStatusFilter::any()));
        if ($subscriptions->isEmpty()) {
            $this->logger?->info('Subscription Engine: No subscriptions to reset.');
            return Result::success();
        }
        $errors = [];
        foreach ($subscriptions as $subscription) {
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
            $subscriptions = $this->subscriptionStore->findByCriteria(SubscriptionCriteria::create(ids: $criteria?->ids));
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

    private function handleEvent(EventEnvelope $eventEnvelope, EventInterface $domainEvent, SubscriptionId $subscriptionId): Error|null
    {
        $subscriber = $this->subscribers->get($subscriptionId);
        try {
            $subscriber->handle($domainEvent, $eventEnvelope);
        } catch (\Throwable $e) {
            $this->logger?->error(sprintf('Subscription Engine: Subscriber "%s" for "%s" could not process the event "%s" (sequence number: %d): %s', $subscriber::class, $subscriptionId->value, $eventEnvelope->event->type->value, $eventEnvelope->sequenceNumber->value, $e->getMessage()));
            return Error::fromSubscriptionIdAndException($subscriptionId, $e);
        }
        $this->logger?->debug(sprintf('Subscription Engine: Subscriber "%s" for "%s" processed the event "%s" (sequence number: %d).', substr(strrchr($subscriber::class, '\\') ?: '', 1), $subscriptionId->value, $eventEnvelope->event->type->value, $eventEnvelope->sequenceNumber->value));
        return null;
    }

    /**
     * Find all subscribers that don't have a corresponding subscription.
     * For each match a subscription is added
     *
     * Note: newly discovered subscriptions are not ACTIVE by default, instead they have to be initialized via {@see self::setup()} explicitly
     */
    private function discoverNewSubscriptions(): void
    {
        $subscriptions = $this->subscriptionStore->findByCriteria(SubscriptionCriteria::noConstraints());
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
            // todo wrap in savepoint to ensure error do not mess up the projection?
            $this->logger?->error(sprintf('Subscription Engine: Subscriber "%s" for "%s" has an error in the setup method: %s', $subscriber::class, $subscription->id->value, $e->getMessage()));
            $this->subscriptionStore->update(
                $subscription->id,
                SubscriptionStatus::ERROR,
                $subscription->position,
                SubscriptionError::fromPreviousStatusAndException($subscription->status, $e)
            );
            return Error::fromSubscriptionIdAndException($subscription->id, $e);
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
        $this->logger?->debug(sprintf('Subscription Engine: Subscriber "%s" for "%s" has been setup, set to %s from previous %s.', $subscriber::class, $subscription->id->value, SubscriptionStatus::BOOTING->name, $subscription->status->name));
        return null;
    }

    private function resetSubscription(Subscription $subscription): ?Error
    {
        $subscriber = $this->subscribers->get($subscription->id);
        try {
            $subscriber->projection->resetState();
        } catch (\Throwable $e) {
            $this->logger?->error(sprintf('Subscription Engine: Subscriber "%s" for "%s" has an error in the resetState method: %s', $subscriber::class, $subscription->id->value, $e->getMessage()));
            return Error::fromSubscriptionIdAndException($subscription->id, $e);
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

    private function catchUpSubscriptions(Subscriptions $subscriptionsToCatchup, \Closure $progressClosure = null): ProcessedResult
    {
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
            return ProcessedResult::success(0);
        }

        foreach ($subscriptionsToCatchup as $subscription) {
            try {
                $this->subscribers->get($subscription->id)->onBeforeCatchUp($subscription->status);
            } catch (\Throwable $e) {
                // analog to onAfterCatchUp, we tolerate no exceptions here and consider it a critical developer error.
                $message = sprintf('Subscriber "%s" failed onBeforeCatchUp: %s', $subscription->id->value, $e->getMessage());
                $this->logger?->critical($message);
                throw new CatchUpFailed($message, 1732374000, $e);
            }
        }
        $startSequenceNumber = $subscriptionsToCatchup->lowestPosition()?->next() ?? SequenceNumber::none();
        $this->logger?->debug(sprintf('Subscription Engine: Event stream is processed from position %s.', $startSequenceNumber->value));

        /** @var array<Error> $errors */
        $errors = [];
        $numberOfProcessedEvents = 0;
        /** @var array<string,SequenceNumber> $highestSequenceNumberForSubscriber */
        $highestSequenceNumberForSubscriber = [];

        $eventStream = $this->eventStore->load(VirtualStreamName::all())->withMinimumSequenceNumber($startSequenceNumber);
        foreach ($eventStream as $eventEnvelope) {
            $sequenceNumber = $eventEnvelope->sequenceNumber;
            if ($numberOfProcessedEvents > 0) {
                $this->logger?->debug(sprintf('Subscription Engine: Current event stream position: %s', $sequenceNumber->value));
            }
            if ($progressClosure !== null) {
                $progressClosure($eventEnvelope);
            }
            $domainEvent = $this->eventNormalizer->denormalize($eventEnvelope->event);
            foreach ($subscriptionsToCatchup as $subscription) {
                if ($subscription->position->value >= $sequenceNumber->value) {
                    $this->logger?->debug(sprintf('Subscription Engine: Subscription "%s" is farther than the current position (%d >= %d), continue catch up.', $subscription->id->value, $subscription->position->value, $sequenceNumber->value));
                    continue;
                }
                $this->subscriptionStore->transactional(function () use ($eventEnvelope, $domainEvent, $subscription, $numberOfProcessedEvents, &$subscriptionsToCatchup, &$errors, &$highestSequenceNumberForSubscriber) {
                    $error = $this->handleEvent($eventEnvelope, $domainEvent, $subscription->id);
                    if ($error !== null) {
                        // ERROR Case:
                        // 1.) for the leftover events we are not including this failed subscription for catchup
                        $subscriptionsToCatchup = $subscriptionsToCatchup->without($subscription->id);
                        // 2.) update the subscription error state on either its unchanged or new position (if some events worked)
                        $this->subscriptionStore->update(
                            $subscription->id,
                            status: SubscriptionStatus::ERROR,
                            // todo rather use previous position:
                            position: $highestSequenceNumberForSubscriber[$subscription->id->value] ?? $subscription->position,
                            subscriptionError: SubscriptionError::fromPreviousStatusAndException(
                                $subscription->status,
                                $error->throwable
                            ),
                        );
                        // 3.) invoke onAfterCatchUp, as onBeforeCatchUp was invoked already and to be consistent we want to "shutdown" this catchup iteration event though we know it failed
                        // todo put the ERROR $subscriptionStatus into the after hook, so it can properly be reacted upon
                        try {
                            $this->subscribers->get($subscription->id)->onAfterCatchUp();
                        } catch (\Throwable $e) {
                            // analog to onBeforeCatchUp, we tolerate no exceptions here and consider it a critical developer error.
                            $message = sprintf('Subscriber "%s" had an error and also failed onAfterCatchUp: %s', $subscription->id->value, $e->getMessage());
                            $this->logger?->critical($message);
                            throw new CatchUpFailed($message, 1732733740, $e);
                        }
                        $errors[] = $error;
                        return;
                    }

                    $highestSequenceNumberForSubscriber[$subscription->id->value] = $eventEnvelope->sequenceNumber;

                    // after catchup mark all subscriptions as active, so they are triggered automatically now.
                    // The position will be set to the one the subscriber handled last, or if no events were in the stream, and we booted we keep the persisted position
                    $this->subscriptionStore->update(
                        $subscription->id,
                        status: SubscriptionStatus::ACTIVE,
                        position: $eventEnvelope->sequenceNumber,
                        subscriptionError: null,
                    );
                    if ($numberOfProcessedEvents === 0 && $subscription->status !== SubscriptionStatus::ACTIVE) {
                        $this->logger?->info(sprintf('Subscription Engine: Subscription "%s" has been set to active from %s.', $subscription->id->value, $subscription->status->name));
                    }
                });

            }
            $numberOfProcessedEvents++;
        }
        foreach ($subscriptionsToCatchup as $subscription) {
            if ($numberOfProcessedEvents === 0 && $subscription->status !== SubscriptionStatus::ACTIVE) {
                $this->subscriptionStore->update(
                    $subscription->id,
                    status: SubscriptionStatus::ACTIVE,
                    position: $subscription->position,
                    subscriptionError: null,
                );
                $this->logger?->info(sprintf('Subscription Engine: Subscription "%s" has been set to active from %s.', $subscription->id->value, $subscription->status->name));
            }
        }

        foreach ($subscriptionsToCatchup as $subscription) {
            try {
                $this->subscribers->get($subscription->id)->onAfterCatchUp();
            } catch (\Throwable $e) {
                // analog to onBeforeCatchUp, we tolerate no exceptions here and consider it a critical developer error.
                $message = sprintf('Subscriber "%s" failed onAfterCatchUp: %s', $subscription->id->value, $e->getMessage());
                $this->logger?->critical($message);
                throw new CatchUpFailed($message, 1732374000, $e);
            }
        }
        $this->logger?->info(sprintf('Subscription Engine: Finish catch up. %d processed events %d errors.', $numberOfProcessedEvents, count($errors)));
        return $errors === [] ? ProcessedResult::success($numberOfProcessedEvents) : ProcessedResult::failed($numberOfProcessedEvents, Errors::fromArray($errors));
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
        $this->subscriptionStore->acquireLock();
        try {
            return $closure();
        } finally {
            $this->subscriptionStore->releaseLock();
            $this->processing = false;
        }
    }
}

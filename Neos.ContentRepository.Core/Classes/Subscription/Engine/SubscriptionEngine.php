<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Engine;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Subscription\Exception\CatchUpFailed;
use Neos\ContentRepository\Core\Subscription\Exception\SubscriptionEngineAlreadyProcessingException;
use Neos\ContentRepository\Core\Subscription\SubscriptionAndProjectionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionAndProjectionStatuses;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusFilter;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Psr\Log\LoggerInterface;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionCriteria;
use Neos\ContentRepository\Core\Subscription\Subscriber\Subscribers;
use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\Subscriptions;

/**
 * @api
 */
final class SubscriptionEngine
{
    private bool $processing = false;
    private SubscriptionManager $subscriptionManager; // todo inline!!

    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly Subscribers $subscribers,
        private readonly EventNormalizer $eventNormalizer,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function setup(SubscriptionEngineCriteria|null $criteria = null): Result
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();

        $this->logger?->info('Subscription Engine: Start to setup.');

        $subscriberGroups = $this->subscribers->groupByStore();
        $errors = [];
        foreach ($subscriberGroups as [$store]) {
            $store->setup();

            $this->subscriptionManager = new SubscriptionManager($store); // todo hack
            $this->discoverNewSubscriptions();

            $subscriptions = $store->findByCriteria(SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, SubscriptionStatusFilter::fromArray([
                SubscriptionStatus::NEW,
                SubscriptionStatus::BOOTING,
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::DETACHED,
                SubscriptionStatus::ERROR,
            ])));
            if ($subscriptions->isEmpty()) {
                $this->logger?->info('Subscription Engine: No subscriptions found.'); // todo not happy? Because there must be at least the content graph?!!
                return Result::success();
            }
            foreach ($subscriptions as $subscription) {
                $error = $this->setupSubscription($subscription);
                if ($error !== null) {
                    $errors[] = $error;
                }
            }
            $this->subscriptionManager->flush();
        }

        return $errors === [] ? Result::success() : Result::failed(Errors::fromArray($errors));
    }

    public function boot(SubscriptionEngineCriteria|null $criteria = null, \Closure $progressCallback = null): ProcessedResult
    {
        return $this->processExclusively(fn () => $this->catchUpSubscriptions($criteria ?? SubscriptionEngineCriteria::noConstraints(), SubscriptionStatus::BOOTING, $progressCallback));
    }

    public function catchUpActive(SubscriptionEngineCriteria|null $criteria = null, \Closure $progressCallback = null): ProcessedResult
    {
        return $this->processExclusively(fn () => $this->catchUpSubscriptions($criteria ?? SubscriptionEngineCriteria::noConstraints(), SubscriptionStatus::ACTIVE, $progressCallback));
    }

    public function reset(SubscriptionEngineCriteria|null $criteria = null): Result
    {
        $criteria ??= SubscriptionEngineCriteria::noConstraints();

        $this->logger?->info('Subscription Engine: Start to reset.');

        $subscriberGroups = $this->subscribers->groupByStore();
        $errors = [];
        foreach ($subscriberGroups as [$store]) {
            $subscriptions = $store->findByCriteria(SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, SubscriptionStatusFilter::any()));
            $this->subscriptionManager = new SubscriptionManager($store);
            if ($subscriptions->isEmpty()) {
                $this->logger?->info('Subscription Engine: No subscriptions to reset.');
                return Result::success();
            }
            foreach ($subscriptions as $subscription) {
                $error = $this->resetSubscription($subscription);
                if ($error !== null) {
                    $errors[] = $error;
                }
            }
            $this->subscriptionManager->flush();
        }
        return $errors === [] ? Result::success() : Result::failed(Errors::fromArray($errors));
    }

    public function subscriptionStatuses(SubscriptionCriteria|null $criteria = null): SubscriptionAndProjectionStatuses
    {
        $subscriberGroups = $this->subscribers->groupByStore();
        $statuses = [];
        foreach ($subscriberGroups as [$store]) {
            try {
                $subscriptions = $store->findByCriteria($criteria ?? SubscriptionCriteria::noConstraints());
            } catch (TableNotFoundException) {
                // the schema is not setup - thus there are no subscribers
                continue;
            }
            foreach ($subscriptions as $subscription) {
                $subscriber = $this->subscribers->contain($subscription->id) ? $this->subscribers->get($subscription->id) : null;
                $statuses[] = SubscriptionAndProjectionStatus::create(
                    subscriptionId: $subscription->id,
                    subscriptionStatus: $subscription->status,
                    subscriptionPosition: $subscription->position,
                    subscriptionError: $subscription->error,
                    projectionStatus: $subscriber?->projection->status(),
                );
            }
        }

        return SubscriptionAndProjectionStatuses::fromArray($statuses);
    }

    private function handleEvent(EventEnvelope $eventEnvelope, EventInterface $domainEvent, Subscription $subscription): Error|null
    {
        $subscriber = $this->subscribers->get($subscription->id);
        try {
            $subscriber->handle($domainEvent, $eventEnvelope);
        } catch (\Throwable $e) {
            $this->logger?->error(sprintf('Subscription Engine: Subscriber "%s" for "%s" could not process the event "%s" (sequence number: %d): %s', $subscriber::class, $subscription->id->value, $eventEnvelope->event->type->value, $eventEnvelope->sequenceNumber->value, $e->getMessage()));
            $subscription->fail($e);
            $this->subscriptionManager->update($subscription);
            return Error::fromSubscriptionIdAndException($subscription->id, $e);
        }
        $this->logger?->debug(sprintf('Subscription Engine: Subscriber "%s" for "%s" processed the event "%s" (sequence number: %d).', substr(strrchr($subscriber::class, '\\') ?: '', 1), $subscription->id->value, $eventEnvelope->event->type->value, $eventEnvelope->sequenceNumber->value));
        $subscription->set(
            position: $eventEnvelope->sequenceNumber
        );
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
        $this->subscriptionManager->findForAndUpdate(
            SubscriptionCriteria::noConstraints(),
            function (Subscriptions $subscriptions) {
                foreach ($this->subscribers as $subscriber) {
                    if ($subscriptions->contain($subscriber->id)) {
                        continue;
                    }
                    $subscription = Subscription::createFromSubscriber($subscriber);
                    $this->subscriptionManager->add($subscription);
                    $this->logger?->info(sprintf('Subscription Engine: New Subscriber "%s" was found and added to the subscription store.', $subscriber->id->value));
                }
            }
        );
    }

    /**
     * Set up the subscription by retrieving the corresponding subscriber and calling the setUp method on its handler
     * If the setup fails, the subscription will be in the {@see SubscriptionStatus::ERROR} state and a corresponding {@see Error} is returned
     */
    private function setupSubscription(Subscription $subscription): ?Error
    {
        if (!$this->subscribers->contain($subscription->id)) {
            // mark detached subscriptions as we cannot set up
            $subscription->set(
                status: SubscriptionStatus::DETACHED,
            );
            $this->subscriptionManager->update($subscription);
            $this->logger?->info(sprintf('Subscription Engine: Subscriber for "%s" not found and has been marked as detached.', $subscription->id->value));
            return null;
        }

        $subscriber = $this->subscribers->get($subscription->id);
        try {
            $subscriber->projection->setUp();
        } catch (\Throwable $e) {
            // todo wrap in savepoint to ensure error do not mess up the projection?
            $this->logger?->error(sprintf('Subscription Engine: Subscriber "%s" for "%s" has an error in the setup method: %s', $subscriber::class, $subscription->id->value, $e->getMessage()));
            $subscription->fail($e);
            $this->subscriptionManager->update($subscription);
            return Error::fromSubscriptionIdAndException($subscription->id, $e);
        }

        if ($subscription->status === SubscriptionStatus::ACTIVE) {
            $this->logger?->debug(sprintf('Subscription Engine: Active subscriber "%s" for "%s" has been re-setup.', $subscriber::class, $subscription->id->value));
            return null;
        }
        if ($subscription->status === SubscriptionStatus::ERROR) {
            $this->logger?->debug(sprintf('Subscription Engine: Failed subscriber "%s" for "%s" has been re-setup, set to %s. Previous error: %s.', $subscriber::class, $subscription->id->value, SubscriptionStatus::BOOTING->name, $subscription->error?->errorMessage));
            $subscription->set(
                status: SubscriptionStatus::BOOTING
            );
            $subscription->unsetError();
            $this->subscriptionManager->update($subscription);
            return null;
        }
        $this->logger?->debug(sprintf('Subscription Engine: Subscriber "%s" for "%s" has been setup, set to %s from previous %s.', $subscriber::class, $subscription->id->value, SubscriptionStatus::BOOTING->name, $subscription->status->name));
        $subscription->set(
            status: SubscriptionStatus::BOOTING
        );
        $this->subscriptionManager->update($subscription);
        return null;
    }

    /**
     * TODO
     */
    private function resetSubscription(Subscription $subscription): ?Error
    {
        $subscriber = $this->subscribers->get($subscription->id);
        try {
            $subscriber->projection->resetState();
        } catch (\Throwable $e) {
            $this->logger?->error(sprintf('Subscription Engine: Subscriber "%s" for "%s" has an error in the resetState method: %s', $subscriber::class, $subscription->id->value, $e->getMessage()));
            return Error::fromSubscriptionIdAndException($subscription->id, $e);
        }
        $subscription->set(
            status: SubscriptionStatus::BOOTING,
            position: SequenceNumber::none()
        );
        $subscription->unsetError();
        $this->subscriptionManager->update($subscription);
        $this->logger?->debug(sprintf('Subscription Engine: For Subscriber "%s" for "%s" the resetState method has been executed.', $subscriber::class, $subscription->id->value));
        return null;
    }

    private function catchUpSubscriptions(SubscriptionEngineCriteria $criteria, SubscriptionStatus $subscriptionStatus, \Closure $progressClosure = null): ProcessedResult
    {
        $this->logger?->info(sprintf('Subscription Engine: Start catching up subscriptions in state "%s".', $subscriptionStatus->value));

        $subscriberGroups = $this->subscribers->groupByStore();

        // todo merge results
        $returnResult = null;

        foreach ($subscriberGroups as [$store, $subscribers]) {
            // todo do not global override manager!
            $this->subscriptionManager = new SubscriptionManager($store);
            $returnResult = $this->subscriptionManager->findForAndUpdate(
            SubscriptionCriteria::forEngineCriteriaAndStatus($criteria, $subscriptionStatus),
                function (Subscriptions $subscriptions) use ($subscriptionStatus, $progressClosure, $store, $subscribers) {
                    foreach ($subscriptions as $subscription) {
                        // TODO we cannot mark a subscriber as detached, if it belongs to another store and no one else is using that store anymore. Then it will just be ACTIVE and never found, not even by status
                        if (!$subscribers->contain($subscription->id)) {
                            // mark detached subscriptions as we cannot handle them and exclude them from catchup
                            $subscription->set(
                                status: SubscriptionStatus::DETACHED,
                            );
                            $this->subscriptionManager->update($subscription);
                            $this->logger?->info(sprintf('Subscription Engine: Subscriber for "%s" not found and has been marked as detached.', $subscription->id->value));
                            $subscriptions = $subscriptions->without($subscription->id);
                        }
                    }
                    if ($subscriptions->isEmpty()) {
                        $this->logger?->info(sprintf('Subscription Engine: No subscriptions in state "%s". Finishing catch up', $subscriptionStatus->value));

                        return ProcessedResult::success(0);
                    }
                    foreach ($subscriptions as $subscription) {
                        try {
                            $subscribers->get($subscription->id)->onBeforeCatchUp($subscription->status);
                        } catch (\Throwable $e) {
                            // analog to onAfterCatchUp, we tolerate no exceptions here and consider it a critical developer error.
                            $message = sprintf('Subscriber "%s" failed onBeforeCatchUp: %s', $subscription->id->value, $e->getMessage());
                            $this->logger?->critical($message);
                            throw new CatchUpFailed($message, 1732374000, $e);
                        }
                    }
                    $startSequenceNumber = $subscriptions->lowestPosition()?->next() ?? SequenceNumber::none();
                    $this->logger?->debug(sprintf('Subscription Engine: Event stream is processed from position %s.', $startSequenceNumber->value));

                    /** @var list<Error> $errors */
                    $errors = [];
                    $numberOfProcessedEvents = 0;
                    try {
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
                            foreach ($subscriptions as $subscription) {
                                if ($subscription->status !== $subscriptionStatus) {
                                    continue;
                                }
                                if ($subscription->position->value >= $sequenceNumber->value) {
                                    $this->logger?->debug(sprintf('Subscription Engine: Subscription "%s" is farther than the current position (%d >= %d), continue catch up.', $subscription->id->value, $subscription->position->value, $sequenceNumber->value));
                                    continue;
                                }
                                $store->createSavepoint();
                                $error = $this->handleEvent($eventEnvelope, $domainEvent, $subscription);
                                if (!$error) {
                                    $store->releaseSavepoint();
                                    continue;
                                }
                                $store->rollbackSavepoint();
                                $errors[] = $error;
                            }
                            $numberOfProcessedEvents++;
                        }
                    } finally {
                        foreach ($subscriptions as $subscription) {
                            $this->subscriptionManager->update($subscription);
                        }
                    }
                    foreach ($subscriptions as $subscription) {
                        try {
                            $subscribers->get($subscription->id)->onAfterCatchUp();
                        } catch (\Throwable $e) {
                            // analog to onBeforeCatchUp, we tolerate no exceptions here and consider it a critical developer error.
                            $message = sprintf('Subscriber "%s" failed onAfterCatchUp: %s', $subscription->id->value, $e->getMessage());
                            $this->logger?->critical($message);
                            throw new CatchUpFailed($message, 1732374000, $e);
                        }
                        if ($subscription->status !== $subscriptionStatus) {
                            continue;
                        }

                        if ($subscription->status !== SubscriptionStatus::ACTIVE) {
                            $subscription->set(
                                status: SubscriptionStatus::ACTIVE,
                            );
                            $this->subscriptionManager->update($subscription);
                            $this->logger?->info(sprintf('Subscription Engine: Subscription "%s" has been set to active after booting.', $subscription->id->value));
                        }
                    }
                    $this->logger?->info(sprintf('Subscription Engine: Finish catch up. %d processed events %d errors.', $numberOfProcessedEvents, count($errors)));
                    return $errors === [] ? ProcessedResult::success($numberOfProcessedEvents) : ProcessedResult::failed($numberOfProcessedEvents, Errors::fromArray($errors));
                }
            );
        }
        return $returnResult ?? ProcessedResult::success(0);
    }

    /**
     * @template T
     * @param \Closure(): T $closure
     * @return T
     */
    private function processExclusively(\Closure $closure): mixed
    {
        if ($this->processing) {
            throw new SubscriptionEngineAlreadyProcessingException();
        }
        $this->processing = true;
        try {
            return $closure();
        } finally {
            $this->processing = false;
        }
    }
}

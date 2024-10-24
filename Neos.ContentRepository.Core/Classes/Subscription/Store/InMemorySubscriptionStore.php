<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Store;

use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\Subscriptions;

/**
 * @internal
 */
final class InMemorySubscriptionStore implements SubscriptionStoreInterface
{
    private Subscriptions $subscriptions;

    public function __construct()
    {
        $this->subscriptions = Subscriptions::none();
    }


    public function findOneById(SubscriptionId $subscriptionId): ?Subscription
    {
        return $this->subscriptions->contain($subscriptionId) ? $this->subscriptions->get($subscriptionId) : null;
    }

    public function findByCriteria(SubscriptionCriteria $criteria): Subscriptions
    {
        return $this->subscriptions->filter(function (Subscription $subscription) use ($criteria) {
            if ($criteria->ids !== null && !$criteria->ids->contain($subscription->id)) {
                return false;
            }
            if ($criteria->groups !== null && !$criteria->groups->contain($subscription->group)) {
                return false;
            }
            if ($criteria->status !== null && !in_array($subscription->status, $criteria->status, true)) {
                return false;
            }
            return true;
        });
    }

    public function acquireLock(SubscriptionId $subscriptionId): bool
    {
        // no locking for this implementation
        return true;
    }

    public function releaseLock(SubscriptionId $subscriptionId): void
    {
        // no locking for this implementation
    }

    public function add(Subscription $subscription): void
    {
        $this->subscriptions = $this->subscriptions->withAdded($subscription);
    }

    public function update(SubscriptionId $subscriptionId, \Closure $updater): void
    {
        $subscription = $this->subscriptions->get($subscriptionId);
        $subscription = $updater($subscription);
        $this->subscriptions = $this->subscriptions->withReplaced($subscriptionId, $subscription);
    }
}

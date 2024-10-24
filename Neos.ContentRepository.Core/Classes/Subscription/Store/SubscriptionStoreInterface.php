<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Store;

use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\Subscriptions;

/**
 * @internal
 */
interface SubscriptionStoreInterface
{
    public function findOneById(SubscriptionId $subscriptionId): ?Subscription;

    public function findByCriteria(SubscriptionCriteria $criteria): Subscriptions;

    public function acquireLock(SubscriptionId $subscriptionId): bool;

    public function releaseLock(SubscriptionId $subscriptionId): void;

    public function add(Subscription $subscription): void;

    /**
     * @param \Closure(Subscription): Subscription $updater
     */
    public function update(SubscriptionId $subscriptionId, \Closure $updater): void;
}

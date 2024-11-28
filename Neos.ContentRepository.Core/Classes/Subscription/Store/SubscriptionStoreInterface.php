<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Store;

use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\Subscriptions;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @internal only API for custom content repository integrations
 */
interface SubscriptionStoreInterface
{
    public function setup(): void;

    public function findByCriteria(SubscriptionCriteria $criteria): Subscriptions;

    public function add(Subscription $subscription): void;

    public function update(
        SubscriptionId $subscriptionId,
        SubscriptionStatus $status,
        SequenceNumber $position,
        SubscriptionError|null $subscriptionError,
    ): void;

    public function acquireLock(): void;

    public function releaseLock(): void;

    /**
     * @template T
     * @param \Closure():T $closure
     * @return T
     */
    public function transactional(\Closure $closure): mixed;
}

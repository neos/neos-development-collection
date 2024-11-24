<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Store;

use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\Subscriptions;

/**
 * @api
 */
interface SubscriptionStoreInterface
{
    public function setup(): void;

    public function findByCriteria(SubscriptionCriteria $criteria): Subscriptions;

    public function add(Subscription $subscription): void;

    public function update(Subscription $subscription): void;

    /**
     * @template T
     * @param \Closure():T $closure
     * @return T
     */
    public function transactional(\Closure $closure): mixed;

    public function createSavepoint(): void;

    public function releaseSavepoint(): void;

    public function rollbackSavepoint(): void;

    public function getId(): string;
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\CatchUpHook;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\EventEnvelope;

/**
 * This is an api with which you can hook into the catch-up process of a projection.
 *
 * To register such a CatchUpHook, create a corresponding {@see CatchUpHookFactoryInterface}
 * and pass it to {@see ProjectionFactoryInterface::build()}.
 *
 * @api
 */
interface CatchUpHookInterface
{
    /**
     * This hook is called at the beginning of a catch-up run, **after** the database lock is acquired,
     * but **before** any projection was called.
     *
     * Note that any errors thrown will be collected and the current catchup batch will be finished as normal.
     * The collect errors will be returned and rethrown by the content repository.
     *
     * @throws CatchUpHookFailed
     */
    public function onBeforeCatchUp(SubscriptionStatus $subscriptionStatus): void;

    /**
     * This hook is called for every event during the catchup process, **before** the projection
     * is updated but in the same transaction.
     *
     * Note that any errors thrown will be collected and the current catchup batch will be finished as normal.
     * The collect errors will be returned and rethrown by the content repository.
     *
     * @throws CatchUpHookFailed
     */
    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void;

    /**
     * This hook is called for every event during the catchup process, **after** the projection
     * is updated but in the same transaction,
     *
     * Note that any errors thrown will be collected and the current catchup batch will be finished as normal.
     * The collect errors will be returned and rethrown by the content repository.
     *
     * @throws CatchUpHookFailed
     */
    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void;

    /**
     * This hook is called for each batch of processed events during the catchup process, **after** the projection
     * and their new position is updated and the transaction is commited.
     *
     * The database lock is directly acquired again after it is released if the batching needs to continue.
     * It can happen that this method is called even without having seen events in the meantime.
     *
     * Note that any errors thrown will be collected but no further batch is started.
     * The collect errors will be returned and rethrown by the content repository.
     *
     * @throws CatchUpHookFailed
     */
    public function onAfterBatchCompleted(): void;

    /**
     * This hook is called at the END of a catch-up run, **after** the projection
     * and their new position is updated and the transaction is commited.
     *
     * Note that any errors thrown will be collected and the catchup will finish as normal.
     * The collect errors will be returned and rethrown by the content repository.
     *
     * @throws CatchUpHookFailed
     */
    public function onAfterCatchUp(): void;
}

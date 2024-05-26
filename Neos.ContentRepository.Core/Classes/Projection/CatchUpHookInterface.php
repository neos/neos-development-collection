<?php

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\EventStore\Model\EventEnvelope;

/**
 * This is an internal API with which you can hook into the catch-up process of a Projection.
 *
 * To register such a CatchUpHook, create a corresponding {@see CatchUpHookFactoryInterface}
 * and pass it to {@see ProjectionFactoryInterface::build()}.
 *
 * @api
 */
interface CatchUpHookInterface
{
    /**
     * This hook is called at the beginning of {@see ContentRepository::catchUpProjection()};
     *
     * @return void
     */
    public function onBeforeCatchUp(): void;

    /**
     * This hook is called for every event during the catchup process, **before** the projection
     * is updated. Thus, this hook runs AFTER the database lock is acquired.
     */
    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void;

    /**
     * This hook is called for every event during the catchup process, **after** the projection
     * is updated. Thus, this hook runs AFTER the database lock is acquired.
     */
    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void;

    /**
     * This hook is called directly before the catchup is done
     * in {@see ContentRepository::catchUpProjection()}.
     *
     * It can happen that this method is called multiple times, even without
     * having seen Events in the meantime.
     *
     * If there exist more events which need to be processed, the database lock
     * is directly acquired again after it is released.
     */
    public function onBeforeBatchCompleted(): void;

    /**
     * This hook is called at the END of {@see ProjectionInterface::catchUpProjection()}, directly
     * before exiting the method.
     *
     * At this point, the Database Lock has already been released.
     */
    public function onAfterCatchUp(): void;
}

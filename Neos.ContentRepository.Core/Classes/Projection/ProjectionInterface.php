<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Common interface for a Content Repository projection. This API is NOT exposed to the outside world, but is
 * the contract between {@see ContentRepository} and the individual projections.
 *
 * If the Projection needs to be notified that a catchup is about to happen, you can additionally
 * implement {@see WithMarkStaleInterface}. This is useful f.e. to disable runtime caches in the ProjectionState.
 *
 * @template-covariant TState of ProjectionStateInterface
 * @api you can write custom projections
 */
interface ProjectionInterface
{
    /**
     * Set up the projection state (create/update required database tables, ...).
     */
    public function setUp(): void;

    /**
     * Determines the setup status of the projection. E.g. are the database tables created or any columns missing.
     */
    public function status(): ProjectionStatus;

    /**
     * Must invoke the closure which will update the catchup hooks and {@see apply}.
     * Additionally, to guarantee exactly once delivery and also to behave correct during exceptions (even if php crashes),
     * a database transaction must be started, or if a transaction is already active on the same connection save points
     * must be used and rolled back on error.
     *
     * @param-immediately-invoked-callable $closure
     */
    public function transactional(\Closure $closure): void;

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void;

    /**
     * NOTE: The ProjectionStateInterface returned must be ALWAYS THE SAME INSTANCE.
     *
     * If the Projection needs to be notified that a catchup is about to happen, you can additionally
     * implement {@see WithMarkStaleInterface}. This is useful f.e. to disable runtime caches in the ProjectionState.
     *
     * @return TState
     */
    public function getState(): ProjectionStateInterface;

    public function resetState(): void;
}

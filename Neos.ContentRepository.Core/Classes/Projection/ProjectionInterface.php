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

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void;

    /**
     * NOTE: The state will be accessed eagerly ONCE upon initialisation of the content repository
     * and put into the immutable {@see ProjectionStates} collection.
     * This ensures always the same instance is being returned when accessing it.
     *
     * Projections should on construction already have the state prepared, that also for internal
     * use cases the SAME INSTANCE is always used.
     *
     * @return TState
     */
    public function getState(): ProjectionStateInterface;

    public function resetState(): void;
}

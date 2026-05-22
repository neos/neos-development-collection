<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Dedicated projection like interface for simulated rebasing/publishing
 *
 * The implementation must ensure that any changes via {@see self::apply()}
 * are executed "in simulation" e.g. NOT persisted.
 *
 * This simulations projection state {@see ContentGraphReadModelInterface} must reflect the
 * current changes of the simulation as well.
 *
 * After stopping the simulation {@see self::stopSimulation} no further methods are to be invoked.
 *
 * This is generally done by leveraging a transaction and rollback.
 *
 * This interface does not extend the {@see ProjectionInterface} wich defines setup, resetState and further as within
 * simulation these methods are neither used nor allowed.
 *
 * Used to simulate commands for publishing: {@see \Neos\ContentRepository\Core\CommandHandler\CommandSimulator}
 */
interface SimulationContentGraphProjectionInterface
{
    /**
     * Returns a read model which must be aware of any events applied in the simulation
     */
    public function getState(): ContentGraphReadModelInterface;

    /**
     * Only events that are publishable are to be handled - no workspace was created or any other "global" events
     */
    public function apply(PublishableToWorkspaceInterface $event, EventEnvelope $eventEnvelope): void;

    /**
     * Simulation can only be stopped but not further nested
     */
    public function stopSimulation(): void;
}

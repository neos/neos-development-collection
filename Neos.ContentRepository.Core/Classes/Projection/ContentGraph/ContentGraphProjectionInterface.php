<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Projection\ProjectionInterface;

/**
 * @extends ProjectionInterface<ContentGraphReadModelInterface>
 * @api for creating a custom content repository graph projection implementation, **not for users of the CR**
 */
interface ContentGraphProjectionInterface extends ProjectionInterface
{
    public function getState(): ContentGraphReadModelInterface;

    /**
     * Dedicated method for simulated rebasing
     *
     * The implementation must ensure that the function passed is invoked
     * and that any changes via {@see ContentGraphProjectionInterface::apply()}
     * are executed "in simulation" e.g. NOT persisted after returning.
     *
     * The projection state {@see ContentGraphReadModelInterface} must reflect the
     * current changes of the simulation as well during the execution of the function.
     *
     * This is generally done by leveraging a transaction and rollback.
     *
     * Used to simulate commands for publishing: {@see \Neos\ContentRepository\Core\CommandHandler\CommandSimulator}
     *
     * @template T
     * @param \Closure(): T $fn
     * @return T the return value of $fn
     */
    public function inSimulation(\Closure $fn): mixed;
}

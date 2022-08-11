<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\ContentRepository;

/**
 * Interface for a class that (asynchronously) triggers a catchup of affected projections after a
 * {@see ContentRepository::handle()} call.
 *
 * Usually, this (asynchronously) triggers {@see ProjectionInterface::catchUp()} via a subprocess or an event queue.
 */
interface ProjectionCatchUpTriggerInterface
{
    public function triggerCatchUp(Projections $projections): void;
}

<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\EventStore\EventPersister;

/**
 * Additional marker interface to add to a {@see ProjectionInterface}.
 *
 * If the Projection needs to be notified that a catchup is about to happen, you can additionally
 * implement this interface. This is useful f.e. to disable runtime caches in the ProjectionState.
 */
interface WithMarkStaleInterface
{

    /**
     * Triggered directly before {@see ProjectionCatchUpTriggerInterface::triggerCatchUp()} is called;
     * by the {@see EventPersister::publishEvents()} method.
     *
     * Can be f.e. used to disable caches inside the Projection State.
     *
     * @return void
     */
    public function markStale(): void;
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventPersister;

/**
 * Additional marker interface to add to a {@see ProjectionInterface}.
 *
 * If the Projection needs to be notified that a catchup is about to happen, you can additionally
 * implement this interface. This is useful f.e. to disable runtime caches in the ProjectionState.
 *
 * @api
 */
interface WithMarkStaleInterface
{
    /**
     * Triggered during catching up after applying events
     * {@see ContentRepository::catchUpProjection()}
     *
     * Can be f.e. used to flush caches inside the Projection State.
     *
     * @return void
     */
    public function markStale(): void;
}

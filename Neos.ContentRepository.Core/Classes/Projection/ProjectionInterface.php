<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\CommandHandler\PendingProjections;
use Neos\ContentRepository\ContentRepository;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event;

/**
 * Common interface for a Content Repository projection. This API is NOT exposed to the outside world, but is
 * the contract between {@see ContentRepository} and the individual projections.
 *
 * If the Projection needs to be notified that a catchup is about to happen, you can additionally
 * implement {@see WithMarkStaleInterface}. This is useful f.e. to disable runtime caches in the ProjectionState.
 *
 * @template TState of ProjectionStateInterface
 * @api you can write custom projections
 */
interface ProjectionInterface
{
    /**
     * Set up the projection state (create databases, call CheckpointStorage::setup()).
     */
    public function setUp(): void;

    /**
     * Can the projection handle this event? Must be deterministic.
     *
     * Used to determine whether this projection should be triggered in response to an event; and also
     * needed as part of the Blocking logic ({@see PendingProjections}).
     *
     * @param Event $event
     * @return bool
     */
    public function canHandle(Event $event): bool;

    /**
     * Catch up the projection, consuming the not-yet-seen events in the given event stream.
     *
     * How this is called depends a lot on your infrastructure - usually via some indirection
     * from {@see ProjectionCatchUpTriggerInterface}.
     *
     * @param EventStreamInterface $eventStream
     * @param ContentRepository $contentRepository
     * @return void
     */
    public function catchUp(EventStreamInterface $eventStream, ContentRepository $contentRepository): void;

    /**
     * Part of the Blocking implementation of commands - usually delegates to an internal
     * {@see CheckpointStorageInterface::getHighestAppliedSequenceNumber()}.
     *
     * See {@see PendingProjections} for implementation details.
     */
    public function getSequenceNumber(): SequenceNumber;

    /**
     * NOTE: The ProjectionStateInterface returned must be ALWAYS THE SAME INSTANCE.
     *
     * If the Projection needs to be notified that a catchup is about to happen, you can additionally
     * implement {@see WithMarkStaleInterface}. This is useful f.e. to disable runtime caches in the ProjectionState.
     *
     * @return TState
     */
    public function getState(): ProjectionStateInterface;

    public function reset(): void;
}

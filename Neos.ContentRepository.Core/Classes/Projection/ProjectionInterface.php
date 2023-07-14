<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\CommandHandler\PendingProjections;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\Model\EventEnvelope;
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

    public function canHandle(EventInterface $event): bool;

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void;

    public function getCheckpointStorage(): CheckpointStorageInterface;

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

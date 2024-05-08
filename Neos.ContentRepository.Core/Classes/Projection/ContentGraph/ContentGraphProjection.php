<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\ContentGraphFinder;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\CheckpointStorageInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @implements ProjectionInterface<ContentGraphFinder>
 * @api people load this projection class name to access the Content Graph
 */
final class ContentGraphProjection implements ProjectionInterface, WithMarkStaleInterface
{
    /**
     * @param ProjectionInterface<ContentGraphFinder> $projectionImplementation
     */
    public function __construct(
        private readonly ProjectionInterface $projectionImplementation
    ) {
    }

    public function setUp(): void
    {
        $this->projectionImplementation->setUp();
    }

    public function status(): ProjectionStatus
    {
        return $this->projectionImplementation->status();
    }

    public function reset(): void
    {
        $this->projectionImplementation->reset();
    }

    public function canHandle(EventInterface $event): bool
    {
        return $this->projectionImplementation->canHandle($event);
    }

    public function getState(): ContentGraphFinder
    {
        return $this->projectionImplementation->getState();
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        $this->projectionImplementation->apply($event, $eventEnvelope);
    }

    public function getCheckpointStorage(): CheckpointStorageInterface
    {
        return $this->projectionImplementation->getCheckpointStorage();
    }

    public function markStale(): void
    {
        if ($this->projectionImplementation instanceof WithMarkStaleInterface) {
            $this->projectionImplementation->markStale();
        }

        $this->getState()->forgetInstances();
    }
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\CheckpointStorageInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @implements ProjectionInterface<ContentGraphInterface>
 * @api people load this projection class name to access the Content Graph
 */
final class ContentGraphProjection implements ProjectionInterface, WithMarkStaleInterface
{
    /**
     * @param WithMarkStaleInterface&ProjectionInterface<ContentGraphInterface> $projectionImplementation
     */
    public function __construct(
        private readonly ProjectionInterface&WithMarkStaleInterface $projectionImplementation
    ) {
    }

    public function setUp(): void
    {
        $this->projectionImplementation->setUp();
    }

    public function reset(): void
    {
        $this->projectionImplementation->reset();
    }

    public function canHandle(EventInterface $event): bool
    {
        return $this->projectionImplementation->canHandle($event);
    }

    public function getState(): ContentGraphInterface
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
        $this->projectionImplementation->markStale();
    }
}

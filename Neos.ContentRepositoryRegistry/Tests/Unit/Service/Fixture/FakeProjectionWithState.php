<?php
namespace Neos\ContentRepositoryRegistry\Tests\Unit\Service\Fixture;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface as TState;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\Helper\InMemoryCheckpointStorage;
use Neos\EventStore\Model\EventEnvelope;

/**
 *
 */
class FakeProjectionWithState implements ProjectionInterface
{
    public function __construct(public readonly string $state) {}

    public function setUp(): void
    {
    }

    public function canHandle(EventInterface $event): bool
    {
        return false;
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
    }

    public function getCheckpointStorage(): CheckpointStorageInterface
    {
        return new InMemoryCheckpointStorage('InMemoryTestCheckpointStorage');
    }

    public function getState(): TState
    {
        /** @phpstan-ignore-next-line */
        return new FakeProjectionState($this->state);
    }

    public function reset(): void
    {
    }

}

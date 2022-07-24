<?php
declare(strict_types=1);
// TODO: RENAME To ContentGraph
namespace Neos\ContentRepository\Projection\Content;

use Neos\ContentRepository\Event\NodeWasCreated;
use Neos\ContentRepository\EventStore\EventNormalizer;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\EventStore\CatchUp\CatchUp;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\ProvidesSetupInterface;

/**
 * @implements ProjectionInterface<ContentGraph>
 */
final class ContentGraphProjection implements ProjectionInterface
{
    public function __construct(
        private readonly ProjectionInterface $projectionImplementation
    ) {}

    public function setUp(): void
    {
        $this->projectionImplementation->setUp();
    }

    public function reset(): void
    {
        $this->projectionImplementation->reset();
    }

    public function canHandle(Event $event): bool
    {
        return $this->projectionImplementation->canHandle($event);
    }

    public function getState(): ContentGraphInterface
    {
        return $this->projectionImplementation->getState();
    }

    public function catchUp(EventStreamInterface $eventStream): void
    {
        $this->projectionImplementation->catchUp($eventStream);
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->projectionImplementation->getSequenceNumber();
    }
}

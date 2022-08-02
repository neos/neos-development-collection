<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Projection\ContentGraph;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\WithMarkStaleInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @implements ProjectionInterface<ContentGraphInterface>
 */
final class ContentGraphProjection implements ProjectionInterface, WithMarkStaleInterface
{
    public function __construct(
        private readonly ProjectionInterface&WithMarkStaleInterface $projectionImplementation
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

    public function catchUp(EventStreamInterface $eventStream, ContentRepository $contentRepository): void
    {
        $this->projectionImplementation->catchUp($eventStream, $contentRepository);
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->projectionImplementation->getSequenceNumber();
    }

    public function markStale(): void
    {
        $this->projectionImplementation->markStale();
    }
}

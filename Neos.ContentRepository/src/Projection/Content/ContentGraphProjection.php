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
        private readonly EventNormalizer $eventNormalizer,
        private readonly CheckpointStorageInterface $checkpointStorage,
    ) {}

    public function setUp(): void
    {
        if ($this->repository instanceof ProvidesSetupInterface) {
            $this->repository->setup();
        }
        if ($this->checkpointStorage instanceof ProvidesSetupInterface) {
            $this->checkpointStorage->setup();
        }
    }

    public function reset(): void
    {
        $this->repository->reset();
        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
    }

    public function canHandle(Event $event): bool
    {
        return method_exists($this, 'when' . $event->type->value);
    }

    private function whenNodeWasCreated(NodeWasCreated $event): void
    {
        $this->repository->add($event->contentStreamId, $event->nodeId);
    }

    public function getState(): ContentGraph
    {
        return new ContentGraph($this->repository);
    }

    private function apply(EventEnvelope $eventEnvelope): void
    {
        if (!$this->canHandle($eventEnvelope->event)) {
            return;
        }
        $eventInstance = $this->eventNormalizer->denormalize($eventEnvelope->event);
        $this->{'when' . $eventEnvelope->event->type->value}($eventInstance);
    }

    public function catchUp(EventStreamInterface $eventStream): void
    {
        $catchUp = CatchUp::create($this->apply(...), $this->checkpointStorage);
        $catchUp->run($eventStream);
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->checkpointStorage->getHighestAppliedSequenceNumber();
    }
}

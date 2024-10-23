<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionInterface;
use Neos\EventStore\Helper\InMemoryEventStore;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Events;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\EventStream\VirtualStreamName;

/**
 * Implementation detail of {@see ContentRepository::handle}, when rebasing or partially publishing
 *
 * @internal
 */
final class CommandSimulator
{
    private bool $inSimulation = false;

    /**
     * @param ContentGraphProjectionInterface $contentRepositoryProjection
     * @param EventNormalizer $eventNormalizer
     * @param array<CommandHandlerInterface> $handlers
     */
    public function __construct(
        private readonly CommandHandlingDependencies $commandHandlingDependencies,
        private readonly ContentGraphProjectionInterface $contentRepositoryProjection,
        private readonly EventNormalizer $eventNormalizer,
        private readonly array $handlers,
        private readonly InMemoryEventStore $inMemoryEventStore
    ) {
    }

    /**
     * @template T
     * @param \Closure(): T $fn
     * @return T
     */
    public function run(\Closure $fn): mixed
    {
        $this->inSimulation = true;
        try {
            return $this->contentRepositoryProjection->inSimulation($fn);
        } finally {
            $this->inSimulation = false;
        }
    }

    public function handle(CommandInterface $command): void
    {
        if ($this->inSimulation === false) {
            throw new \RuntimeException('Simulation is not running');
        }

        $eventsToPublish = $this->handleCommand($command, $this->commandHandlingDependencies);

        if ($eventsToPublish->events->isEmpty()) {
            return;
        }

        // the following logic could also be done in an AppEventStore::commit method (being called
        // directly from the individual Command Handlers).
        $normalizedEvents = Events::fromArray(
            $eventsToPublish->events->map($this->eventNormalizer->normalize(...))
        );

        $commitResult = $this->inMemoryEventStore->commit(
            $eventsToPublish->streamName,
            $normalizedEvents,
            ExpectedVersion::ANY() // The version of the stream in the IN MEMORY event store does not matter to us,
        // because this is only used in memory during the partial publish or rebase operation; so it cannot be written to
        // concurrently.
        // HINT: We cannot use $eventsToPublish->expectedVersion, because this is based on the PERSISTENT event stream (having different numbers)
        );


        $eventStream = $this->inMemoryEventStore->load(VirtualStreamName::all())->withMinimumSequenceNumber(
        // fetch all events that were now committed. Plus one because the first sequence number is one too otherwise we get one event to many.
        // (all elephants shall be placed shamefully placed on my head)
            SequenceNumber::fromInteger($commitResult->highestCommittedSequenceNumber->value - $eventsToPublish->events->count() + 1)
        );

        foreach ($eventStream as $eventEnvelope) {
            $event = $this->eventNormalizer->denormalize($eventEnvelope->event);

            if (!$this->contentRepositoryProjection->canHandle($event)) {
                continue;
            }

            $this->contentRepositoryProjection->apply($event, $eventEnvelope);
        }
    }

    public function currentSequenceNumber(): SequenceNumber
    {
        foreach ($this->inMemoryEventStore->load(VirtualStreamName::all())->backwards()->limit(1) as $eventEnvelope) {
            return $eventEnvelope->sequenceNumber;
        }
        return SequenceNumber::none();
    }

    public function eventStream(): EventStreamInterface
    {
        return $this->inMemoryEventStore->load(VirtualStreamName::all());
    }

    private function handleCommand(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        // TODO fail if multiple handlers can handle the same command
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($command)) {
                return $handler->handle($command, $commandHandlingDependencies);
            }
        }
        throw new \RuntimeException(sprintf('No handler found for Command "%s"', get_debug_type($command)), 1649582778);
    }
}

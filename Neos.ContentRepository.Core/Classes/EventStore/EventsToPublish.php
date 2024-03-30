<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * Result of {@see CommandHandlerInterface::handle()} that basically represents an {@see EventStoreInterface::commit()}
 * call but allows for intercepting and decorating events in {@see ContentRepository::handle()}
 *
 * @internal only used during event publishing (from within command handlers) - and their implementation is not API
 */
final readonly class EventsToPublish
{
    public function __construct(
        public StreamName $streamName,
        public Events $events,
        public ExpectedVersion $expectedVersion,
    ) {
    }

    public static function empty(): self
    {
        return new EventsToPublish(
            StreamName::fromString("empty"),
            Events::fromArray([]),
            ExpectedVersion::ANY()
        );
    }

    public function withCausationOfFirstEventAndAdditionalMetaData(EventMetadata $metadata): self
    {
        /** @var list<EventInterface|DecoratedEvent> $firstEvent */
        $restEvents = iterator_to_array($this->events);
        if (empty($restEvents)) {
            return $this;
        }
        $firstEvent = array_shift($restEvents);

        if ($firstEvent instanceof DecoratedEvent && $firstEvent->eventMetadata) {
            $metadata = EventMetadata::fromArray(array_merge($firstEvent->eventMetadata->value, $metadata->value));
        }

        $decoratedFirstEvent = DecoratedEvent::create($firstEvent, eventId: Event\EventId::create(), metadata: $metadata);

        $decoratedRestEvents = [];
        foreach ($restEvents as $event) {
            $decoratedRestEvents[] = DecoratedEvent::create($event, causationId: $decoratedFirstEvent->eventId);
        }

        return new EventsToPublish(
            $this->streamName,
            Events::fromArray([$decoratedFirstEvent, ...$decoratedRestEvents]),
            $this->expectedVersion
        );
    }
}

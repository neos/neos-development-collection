<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventStream\ExpectedStreamConstraints;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * Neos high level representation of {@see \Neos\EventStore\Model\EventsForCommit} see {@see EventStoreInterface::commitAll()}
 * But encapsulating "domain" {@see EventInterface} instances.
 *
 * Not normalized events allows for intercepting and decorating events in {@see ContentRepository::handle()}
 *
 * Calculated via {@see CommandHandlerInterface::handle()}
 *
 * @internal events are only intended to be published from core command handlers
 */
final readonly class EventsToPublish
{
    private function __construct(
        public EventsForStreams $eventsForStreams,
        public ExpectedStreamConstraints $expectedStreamConstraints,
    ) {
    }

    public static function create(
        EventsForStreams $eventsForStreams,
        ExpectedStreamConstraints $expectedStreamConstraints,
    ): self {
        return new self(
            eventsForStreams: $eventsForStreams,
            expectedStreamConstraints: $expectedStreamConstraints,
        );
    }

    public static function createEventsForStream(StreamName $streamName, EventInterface|DecoratedEvent|Events $events): self
    {
        return new self(
            EventsForStreams::create(
                EventsForStream::create(
                    streamName: $streamName,
                    events: $events instanceof Events ? $events : Events::with($events),
                ),
            ),
            ExpectedStreamConstraints::none()
        );
    }

    public static function createEventsForStreamAndExpectedVersion(StreamName $streamName, EventInterface|DecoratedEvent|Events $events, ExpectedVersion $expectedVersion): self
    {
        $expectedStreamConstraint = $expectedVersion->toExpectedStreamConstraint($streamName);

        return new self(
            EventsForStreams::create(
                EventsForStream::create(
                    streamName: $streamName,
                    events: $events instanceof Events ? $events : Events::with($events),
                ),
            ),
            $expectedStreamConstraint === null
                ? ExpectedStreamConstraints::none()
                : ExpectedStreamConstraints::create($expectedStreamConstraint)
        );
    }

    public function merge(self $other): self
    {
        return new self(
            eventsForStreams: $this->eventsForStreams->merge($other->eventsForStreams),
            expectedStreamConstraints: $this->expectedStreamConstraints->merge($other->expectedStreamConstraints),
        );
    }

    public function withEventsForStreamAndExpectedVersion(StreamName $streamName, EventInterface|DecoratedEvent|Events $events, ExpectedVersion $expectedVersion): self
    {
        $expectedStreamConstraint = $expectedVersion->toExpectedStreamConstraint($streamName);
        if ($expectedStreamConstraint === null) {
            return $this->withEventsForStream(
                $streamName,
                $events
            );
        }

        return new self(
            $this->eventsForStreams->withAppended(
                EventsForStream::create(
                    streamName: $streamName,
                    events: $events instanceof Events ? $events : Events::with($events),
                ),
            ),
            $this->expectedStreamConstraints->withAppended($expectedStreamConstraint),
        );
    }

    public function withEventsForStream(StreamName $streamName, EventInterface|DecoratedEvent|Events $events): self
    {
        return new self(
            $this->eventsForStreams->withAppended(
                EventsForStream::create(
                    streamName: $streamName,
                    events: $events instanceof Events ? $events : Events::with($events),
                ),
            ),
            $this->expectedStreamConstraints
        );
    }

    public function withExpectedVersionForStream(StreamName $streamName, ExpectedVersion $expectedVersion): self
    {
        $expectedStreamConstraint = $expectedVersion->toExpectedStreamConstraint($streamName);
        if ($expectedStreamConstraint === null) {
            return $this;
        }

        return new self(
            $this->eventsForStreams,
            $this->expectedStreamConstraints->withAppended(
                $expectedStreamConstraint
            )
        );
    }
}

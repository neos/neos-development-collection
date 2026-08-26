<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal helper to {@see EventsToPublish} in nullable codepaths
 */
final readonly class EventsToPublishTemplate
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function merge(EventsToPublish $other): EventsToPublish
    {
        return $other;
    }

    public function withEventsForStreamAndExpectedVersion(StreamName $streamName, EventInterface|DecoratedEvent|Events $events, ExpectedVersion $expectedVersion): EventsToPublish
    {
        return EventsToPublish::createEventsForStreamAndExpectedVersion($streamName, $events, $expectedVersion);
    }
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\EventStore\Model\Event\StreamName;

/**
 * Neos high level representation of {@see \Neos\EventStore\Model\EventsForStream}
 * With "domain" {@see EventInterface} instances.
 *
 * @internal events are only intended to be published from core command handlers
 */
final readonly class EventsForStream
{
    /**
     * @param StreamName $streamName
     * @param Events $events
     */
    private function __construct(
        public StreamName $streamName,
        public Events $events,
    ) {
    }

    public static function create(
        StreamName $streamName,
        Events $events,
    ): self {
        return new self(
            streamName: $streamName,
            events: $events,
        );
    }
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\ContentRepository\Core\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\EventStore\EventStoreInterface;
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
}

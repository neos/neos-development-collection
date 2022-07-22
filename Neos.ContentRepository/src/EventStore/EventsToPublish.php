<?php
declare(strict_types=1);
namespace Neos\ContentRepository\EventStore;


use Neos\ContentRepository\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\ContentRepository;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\Event\StreamName;

/**
 * Result of {@see CommandHandlerInterface::handle()} that basically represents an {@see EventStoreInterface::commit()} call
 * but allows for intercepting and decorating events in {@see ContentRepository::handle()}
 */
final class EventsToPublish
{
    public function __construct(
        public readonly StreamName $streamName,
        public readonly Events $events,
        public readonly ExpectedVersion $expectedVersion,
    ) {}
}

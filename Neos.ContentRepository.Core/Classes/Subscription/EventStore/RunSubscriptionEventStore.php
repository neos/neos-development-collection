<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\EventStore;

use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventStore\CommitResult;
use Neos\EventStore\Model\EventStore\Status;
use Neos\EventStore\Model\EventStream\EventStreamFilter;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;

/**
 * @internal
 */
final class RunSubscriptionEventStore implements EventStoreInterface
{
    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly SubscriptionEngineCriteria|null $criteria = null,
    ) {
    }

    public function setup(): void
    {
        $this->eventStore->setup();
    }

    public function status(): Status
    {
        return $this->eventStore->status();
    }

    public function load(StreamName|VirtualStreamName $streamName, EventStreamFilter $filter = null): EventStreamInterface
    {
        return $this->eventStore->load($streamName, $filter);
    }

    public function commit(StreamName $streamName, \Neos\EventStore\Model\Events|Event $events, ExpectedVersion $expectedVersion): CommitResult
    {
        $commitResult = $this->eventStore->commit($streamName, $events, $expectedVersion);
        $this->subscriptionEngine->run($this->criteria ?? SubscriptionEngineCriteria::noConstraints());
        return $commitResult;
    }

    public function deleteStream(StreamName $streamName): void
    {
        $this->eventStore->deleteStream($streamName);
    }
}

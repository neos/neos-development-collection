<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\StructureAdjustment;

use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStore;
use Ramsey\Uuid\Uuid;

trait RemoveNodeAggregateTrait
{
    abstract protected function getEventStore(): EventStore;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    private function removeNodeAggregate(ReadableNodeAggregateInterface $tetheredNodeAggregate): CommandResult
    {
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new NodeAggregateWasRemoved(
                    $tetheredNodeAggregate->getContentStreamIdentifier(),
                    $tetheredNodeAggregate->getIdentifier(),
                    $tetheredNodeAggregate->getOccupiedDimensionSpacePoints(),
                    $tetheredNodeAggregate->getCoveredDimensionSpacePoints(),
                    UserIdentifier::forSystemUser()
                ),
                Uuid::uuid4()->toString()
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $tetheredNodeAggregate->getContentStreamIdentifier()
        );
        $this->getEventStore()->commit($streamName->getEventStreamName(), $events);

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }
}

<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\StructureAdjustment\Traits;

use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\AffectedCoveredDimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\AffectedOccupiedDimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStore;
use Ramsey\Uuid\Uuid;

trait RemoveNodeAggregateTrait
{
    abstract protected function getEventStore(): EventStore;

    private function removeNodeAggregate(ReadableNodeAggregateInterface $tetheredNodeAggregate): CommandResult
    {
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new NodeAggregateWasRemoved(
                    $tetheredNodeAggregate->getContentStreamIdentifier(),
                    $tetheredNodeAggregate->getIdentifier(),
                    AffectedOccupiedDimensionSpacePointSet::allVariants(
                        $tetheredNodeAggregate
                    ),
                    AffectedCoveredDimensionSpacePointSet::allVariants(
                        $tetheredNodeAggregate
                    ),
                    UserIdentifier::forSystemUser()
                ),
                Uuid::uuid4()->toString()
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($tetheredNodeAggregate->getContentStreamIdentifier());
        $this->getEventStore()->commit($streamName->getEventStreamName(), $events);
        return CommandResult::fromPublishedEvents($events);
    }
}

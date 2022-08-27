<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

trait RemoveNodeAggregateTrait
{
    private function removeNodeAggregate(NodeAggregate $tetheredNodeAggregate): EventsToPublish
    {
        $events = Events::with(
            new NodeAggregateWasRemoved(
                $tetheredNodeAggregate->getContentStreamIdentifier(),
                $tetheredNodeAggregate->getIdentifier(),
                $tetheredNodeAggregate->getOccupiedDimensionSpacePoints(),
                $tetheredNodeAggregate->getCoveredDimensionSpacePoints(),
                UserIdentifier::forSystemUser()
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $tetheredNodeAggregate->getContentStreamIdentifier()
        );
        return new EventsToPublish(
            $streamName->getEventStreamName(),
            $events,
            ExpectedVersion::ANY()
        );
    }
}

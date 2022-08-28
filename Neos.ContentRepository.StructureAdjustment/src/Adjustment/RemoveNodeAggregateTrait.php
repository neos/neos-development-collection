<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\User\UserIdentifier;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

trait RemoveNodeAggregateTrait
{
    private function removeNodeAggregate(NodeAggregate $tetheredNodeAggregate): EventsToPublish
    {
        $events = Events::with(
            new NodeAggregateWasRemoved(
                $tetheredNodeAggregate->contentStreamIdentifier,
                $tetheredNodeAggregate->nodeAggregateIdentifier,
                $tetheredNodeAggregate->occupiedDimensionSpacePoints,
                $tetheredNodeAggregate->coveredDimensionSpacePoints,
                UserIdentifier::forSystemUser()
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $tetheredNodeAggregate->contentStreamIdentifier
        );
        return new EventsToPublish(
            $streamName->getEventStreamName(),
            $events,
            ExpectedVersion::ANY()
        );
    }
}

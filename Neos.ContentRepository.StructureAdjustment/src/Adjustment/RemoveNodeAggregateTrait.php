<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

trait RemoveNodeAggregateTrait
{
    private function removeNodeAggregate(ContentGraphInterface $contentGraph, NodeAggregate $tetheredNodeAggregate): EventsToPublish
    {
        $events = Events::with(
            new NodeAggregateWasRemoved(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $tetheredNodeAggregate->nodeAggregateId,
                $tetheredNodeAggregate->occupiedDimensionSpacePoints,
                $tetheredNodeAggregate->coveredDimensionSpacePoints,
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            $tetheredNodeAggregate->contentStreamId
        );
        return new EventsToPublish(
            $streamName->getEventStreamName(),
            $events,
            ExpectedVersion::ANY()
        );
    }
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;

/**
 * @internal the publication of events is not API, use commands instead.
 */
trait RemoveNodeAggregateTrait
{
    private function removeNodeAggregate(ContentGraphInterface $contentGraph, NodeAggregate $tetheredNodeAggregate): Events
    {
        $events = Events::with(
            new NodeAggregateWasRemoved(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $tetheredNodeAggregate->nodeAggregateId,
                $tetheredNodeAggregate->coveredDimensionSpacePoints,
            )
        );

        return $events;
    }
}

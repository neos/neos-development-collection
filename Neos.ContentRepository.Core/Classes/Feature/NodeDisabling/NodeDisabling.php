<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeDisabling;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeDisabling
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    /**
     * @param DisableNodeAggregate $command
     * @return EventsToPublish
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregateCurrentlyDoesNotExist
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    private function handleDisableNodeAggregate(
        DisableNodeAggregate $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );
        $this->requireNodeAggregateToNotDisableDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
            ->resolveAffectedDimensionSpacePoints(
                $command->coveredDimensionSpacePoint,
                $nodeAggregate,
                $this->getInterDimensionalVariationGraph()
            );

        $events = Events::with(
            new NodeAggregateWasDisabled(
                $command->contentStreamId,
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
                $command->initiatingUserId
            ),
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @param EnableNodeAggregate $command
     * @return EventsToPublish
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function handleEnableNodeAggregate(
        EnableNodeAggregate $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );
        $this->requireNodeAggregateToDisableDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
            ->resolveAffectedDimensionSpacePoints(
                $command->coveredDimensionSpacePoint,
                $nodeAggregate,
                $this->getInterDimensionalVariationGraph()
            );

        $events = Events::with(
            new NodeAggregateWasEnabled(
                $command->contentStreamId,
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
                $command->initiatingUserId
            )
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand($command, $events),
            ExpectedVersion::ANY()
        );
    }
}

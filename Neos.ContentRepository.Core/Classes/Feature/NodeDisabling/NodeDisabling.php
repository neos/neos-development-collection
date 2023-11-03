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
        $contentStreamId = $this->requireContentStream($command->workspaceName, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        if ($nodeAggregate->disablesDimensionSpacePoint($command->coveredDimensionSpacePoint)) {
            // already disabled, so we can return a no-operation.
            return EventsToPublish::empty();
        }

        $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
            ->resolveAffectedDimensionSpacePoints(
                $command->coveredDimensionSpacePoint,
                $nodeAggregate,
                $this->getInterDimensionalVariationGraph()
            );

        $events = Events::with(
            new NodeAggregateWasDisabled(
                $contentStreamId,
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
            ),
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentStreamId)
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
        $contentStreamId = $this->requireContentStream($command->workspaceName, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        if (!$nodeAggregate->disablesDimensionSpacePoint($command->coveredDimensionSpacePoint)) {
            // already enabled, so we can return a no-operation.
            return EventsToPublish::empty();
        }

        $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
            ->resolveAffectedDimensionSpacePoints(
                $command->coveredDimensionSpacePoint,
                $nodeAggregate,
                $this->getInterDimensionalVariationGraph()
            );

        $events = Events::with(
            new NodeAggregateWasEnabled(
                $contentStreamId,
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
            )
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand($command, $events),
            ExpectedVersion::ANY()
        );
    }
}

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

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeDisabling
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    /**
     * @param DisableNodeAggregate $command
     * @param CommandHandlingDependencies $commandHandlingDependencies
     * @return EventsToPublish
     */
    private function handleDisableNodeAggregate(
        DisableNodeAggregate $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $this->requireContentStream($command->workspaceName, $commandHandlingDependencies);
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );
        if ($nodeAggregate->getDimensionSpacePointsTaggedWith(SubtreeTag::disabled())->contains($command->coveredDimensionSpacePoint)) {
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
            new SubtreeWasTagged(
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
                SubtreeTag::disabled(),
            ),
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            $expectedVersion
        );
    }

    /**
     * @param EnableNodeAggregate $command
     * @param CommandHandlingDependencies $commandHandlingDependencies
     * @return EventsToPublish
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function handleEnableNodeAggregate(
        EnableNodeAggregate $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );
        if (!$nodeAggregate->getDimensionSpacePointsTaggedWith(SubtreeTag::disabled())->contains($command->coveredDimensionSpacePoint)) {
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
            new SubtreeWasUntagged(
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
                SubtreeTag::disabled(),
            )
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand($command, $events),
            $expectedVersion
        );
    }
}

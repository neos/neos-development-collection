<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\SubtreeTagging;

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
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait SubtreeTagging
{
    use ConstraintChecks;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    private function handleTagSubtree(TagSubtree $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate($contentGraph, $command->nodeAggregateId);
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        if ($nodeAggregate->getDimensionSpacePointsTaggedWith($command->tag)->contains($command->coveredDimensionSpacePoint)) {
            // already explicitly tagged with the same Subtree Tag, so we can return a no-operation.
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
                $command->tag,
            ),
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
        );
    }

    public function handleUntagSubtree(UntagSubtree $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        if (!$nodeAggregate->getDimensionSpacePointsTaggedWith($command->tag)->contains($command->coveredDimensionSpacePoint)) {
            // not explicitly tagged with the given Subtree Tag, so we can return a no-operation.
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
                $command->tag,
            )
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand($command, $events),
            ExpectedVersion::ANY()
        );
    }
}

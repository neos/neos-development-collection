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

use Neos\ContentRepository\Core\CommandHandler\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\RebaseableCommand;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Exception\SubtreeIsAlreadyTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Exception\SubtreeIsNotTagged;
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
        $this->requireContentStream($command->workspaceName, $commandHandlingDependencies);
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = ExpectedVersion::fromVersion($commandHandlingDependencies->getContentStreamVersion($contentGraph->getContentStreamId()));
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate($contentGraph, $command->nodeAggregateId);
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        $explicitlyTaggedDimensions = $nodeAggregate->getCoveredDimensionsTaggedBy($command->tag, withoutInherited: true);
        if ($explicitlyTaggedDimensions->contains($command->coveredDimensionSpacePoint)) {
            throw new SubtreeIsAlreadyTagged(sprintf('Cannot add subtree tag "%s" because node aggregate "%s" is already explicitly tagged with that tag in dimension space point %s', $command->tag->value, $nodeAggregate->nodeAggregateId->value, $command->coveredDimensionSpacePoint->toJson()), 1731167142);
        }

        $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
            ->resolveAffectedDimensionSpacePoints(
                $command->coveredDimensionSpacePoint,
                $nodeAggregate,
                $this->getInterDimensionalVariationGraph()
            );

        $events = Events::with(
            new SubtreeWasTagged(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
                $command->tag,
            ),
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
                ->getEventStreamName(),
            RebaseableCommand::enrichWithCommand(
                $command,
                $events
            ),
            $expectedVersion
        );
    }

    public function handleUntagSubtree(UntagSubtree $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        $this->requireContentStream($command->workspaceName, $commandHandlingDependencies);
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = ExpectedVersion::fromVersion($commandHandlingDependencies->getContentStreamVersion($contentGraph->getContentStreamId()));
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

        $explicitlyTaggedDimensions = $nodeAggregate->getCoveredDimensionsTaggedBy($command->tag, withoutInherited: true);
        if (!$explicitlyTaggedDimensions->contains($command->coveredDimensionSpacePoint)) {
            throw new SubtreeIsNotTagged(sprintf('Cannot remove subtree tag "%s" because node aggregate "%s" is not explicitly tagged with that tag in dimension space point %s', $command->tag->value, $nodeAggregate->nodeAggregateId->value, $command->coveredDimensionSpacePoint->toJson()), 1731167464);
        }

        $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
            ->resolveAffectedDimensionSpacePoints(
                $command->coveredDimensionSpacePoint,
                $nodeAggregate,
                $this->getInterDimensionalVariationGraph()
            );

        $events = Events::with(
            new SubtreeWasUntagged(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
                $command->tag,
            )
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())->getEventStreamName(),
            RebaseableCommand::enrichWithCommand($command, $events),
            $expectedVersion
        );
    }
}

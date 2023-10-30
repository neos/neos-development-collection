<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Tagging;

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
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\Tagging\Command\AddSubtreeTag;
use Neos\ContentRepository\Core\Feature\Tagging\Command\RemoveSubtreeTag;
use Neos\ContentRepository\Core\Feature\Tagging\Event\SubtreeTagWasAdded;
use Neos\ContentRepository\Core\Feature\Tagging\Event\SubtreeTagWasRemoved;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeTagging
{
    use ConstraintChecks;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    private function handleAddSubtreeTag(AddSubtreeTag $command, ContentRepository $contentRepository): EventsToPublish
    {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $nodeAggregate = $this->requireProjectedNodeAggregate($command->contentStreamId, $command->nodeAggregateId, $contentRepository);
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );

// TODO Adjust to SubtreeTags (is already tagged with same tag => throw exception)
//        if ($nodeAggregate->disablesDimensionSpacePoint($command->coveredDimensionSpacePoint)) {
//            // already disabled, so we can return a no-operation.
//            return EventsToPublish::empty();
//        }

        $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
            ->resolveAffectedDimensionSpacePoints(
                $command->coveredDimensionSpacePoint,
                $nodeAggregate,
                $this->getInterDimensionalVariationGraph()
            );

        $events = Events::with(
            new SubtreeTagWasAdded(
                $command->contentStreamId,
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
                $command->tag,
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

    public function handleRemoveSubtreeTag(RemoveSubtreeTag $command, ContentRepository $contentRepository): EventsToPublish
    {
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

// TODO Adjust to SubtreeTags (is not tagged with same tag => throw exception)
//        if ($nodeAggregate->disablesDimensionSpacePoint($command->coveredDimensionSpacePoint)) {
//            // already disabled, so we can return a no-operation.
//            return EventsToPublish::empty();
//        }

        $affectedDimensionSpacePoints = $command->nodeVariantSelectionStrategy
            ->resolveAffectedDimensionSpacePoints(
                $command->coveredDimensionSpacePoint,
                $nodeAggregate,
                $this->getInterDimensionalVariationGraph()
            );

        $events = Events::with(
            new SubtreeTagWasRemoved(
                $command->contentStreamId,
                $command->nodeAggregateId,
                $affectedDimensionSpacePoints,
                $command->tag,
            )
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand($command, $events),
            ExpectedVersion::ANY()
        );
    }
}

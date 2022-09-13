<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeRemoval;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\ParentsNodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Exception\TetheredNodeAggregateCannotBeRemoved;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Feature\Common\Exception\DimensionSpacePointHasNoPrimaryGeneralization;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RestoreNodeAggregateCoverage;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateCoverageWasRestored;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeRemoval
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    /**
     * @param RemoveNodeAggregate $command
     * @return EventsToPublish
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     */
    private function handleRemoveNodeAggregate(
        RemoveNodeAggregate $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $this->requireNodeAggregateNotToBeTethered($nodeAggregate);
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );
        if ($command->removalAttachmentPoint instanceof NodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamId,
                $command->removalAttachmentPoint,
                $contentRepository
            );
        }

        $events = Events::with(
            new NodeAggregateWasRemoved(
                $command->contentStreamId,
                $command->nodeAggregateId,
                $command->nodeVariantSelectionStrategy->resolveAffectedOriginDimensionSpacePoints(
                    $nodeAggregate->getOccupationByCovered($command->coveredDimensionSpacePoint),
                    $nodeAggregate,
                    $this->getInterDimensionalVariationGraph()
                ),
                $command->nodeVariantSelectionStrategy->resolveAffectedDimensionSpacePoints(
                    $command->coveredDimensionSpacePoint,
                    $nodeAggregate,
                    $this->getInterDimensionalVariationGraph()
                ),
                $command->initiatingUserId,
                $command->removalAttachmentPoint
            )
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

    public function handleRestoreNodeAggregateCoverage(
        RestoreNodeAggregateCoverage $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $this->requireDimensionSpacePointToExist($command->dimensionSpacePointToCover);
        $primaryGeneralization = $this->interDimensionalVariationGraph->getPrimaryGeneralization(
            $command->dimensionSpacePointToCover
        );
        if (!$primaryGeneralization instanceof DimensionSpacePoint) {
            throw DimensionSpacePointHasNoPrimaryGeneralization::butWasSupposedToHave(
                $command->dimensionSpacePointToCover
            );
        }
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $primaryGeneralization
        );

        $this->requireNodeAggregateToNotCoverDimensionSpacePoint($nodeAggregate, $command->dimensionSpacePointToCover);
        $parentNodeAggregate = $this->requireProjectedParentNodeAggregateInDimensionSpacePoint(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $primaryGeneralization,
            $contentRepository
        );
        if (!$parentNodeAggregate->coversDimensionSpacePoint($command->dimensionSpacePointToCover)) {
            throw ParentsNodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint::butWasSupposedTo(
                $command->nodeAggregateId,
                $command->dimensionSpacePointToCover,
                $command->contentStreamId
            );
        }

        $events = Events::with(
            new NodeAggregateCoverageWasRestored(
                $command->contentStreamId,
                $command->nodeAggregateId,
                $primaryGeneralization,
                $command->withSpecializations
                    ? NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS
                    ->resolveAffectedDimensionSpacePoints(
                        $command->dimensionSpacePointToCover,
                        $parentNodeAggregate,
                        $this->interDimensionalVariationGraph
                    )
                    : new DimensionSpacePointSet([$command->dimensionSpacePointToCover]),
                $command->recursionMode,
                $command->initiatingUserId
            )
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

    protected function requireNodeAggregateNotToBeTethered(NodeAggregate $nodeAggregate): void
    {
        if ($nodeAggregate->classification->isTethered()) {
            throw new TetheredNodeAggregateCannotBeRemoved(
                'The node aggregate "' . $nodeAggregate->nodeAggregateId
                . '" is tethered, and thus cannot be removed.',
                1597753832
            );
        }
    }
}

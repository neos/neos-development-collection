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

namespace Neos\ContentRepository\Feature\NodeRemoval;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Feature\Common\Exception\DimensionSpacePointHasNoPrimaryGeneralization;
use Neos\ContentRepository\Feature\Common\Exception\ParentsNodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RestoreNodeAggregateCoverage;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateCoverageWasRestored;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeRemoval
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getInterDimensionalVariationGraph(): InterDimensionalVariationGraph;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    /**
     * @param RemoveNodeAggregate $command
     * @return CommandResult
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointNotFound
     */
    public function handleRemoveNodeAggregate(RemoveNodeAggregate $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier
        );
        $this->requireDimensionSpacePointToExist($command->coveredDimensionSpacePoint);
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $nodeAggregate,
            $command->coveredDimensionSpacePoint
        );
        if ($command->removalAttachmentPoint instanceof NodeAggregateIdentifier) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $command->removalAttachmentPoint
            );
        }

        $events = null;
        $this->getNodeAggregateEventPublisher()->withCommand(
            $command,
            function () use ($command, $nodeAggregate, &$events) {
                $events = DomainEvents::withSingleEvent(
                    DecoratedEvent::addIdentifier(
                        new NodeAggregateWasRemoved(
                            $command->contentStreamIdentifier,
                            $command->nodeAggregateIdentifier,
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
                            $command->initiatingUserIdentifier,
                            $command->removalAttachmentPoint
                        ),
                        Uuid::uuid4()->toString()
                    )
                );

                $this->getNodeAggregateEventPublisher()->publishMany(
                    ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
                        ->getEventStreamName(),
                    $events
                );
            }
        );
        /** @var DomainEvents $events */

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }

    public function handleRestoreNodeAggregateCoverage(RestoreNodeAggregateCoverage $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier
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
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $primaryGeneralization
        );
        if (!$parentNodeAggregate->coversDimensionSpacePoint($command->dimensionSpacePointToCover)) {
            throw ParentsNodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint::butWasSupposedTo(
                $command->nodeAggregateIdentifier,
                $command->dimensionSpacePointToCover,
                $command->contentStreamIdentifier
            );
        }

        $events = null;
        $this->getNodeAggregateEventPublisher()->withCommand(
            $command,
            function () use ($command, $parentNodeAggregate, $primaryGeneralization, &$events) {
                $events = DomainEvents::withSingleEvent(
                    DecoratedEvent::addIdentifier(
                        new NodeAggregateCoverageWasRestored(
                            $command->contentStreamIdentifier,
                            $command->nodeAggregateIdentifier,
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
                            $command->initiatingUserIdentifier
                        ),
                        Uuid::uuid4()->toString()
                    )
                );

                $this->getNodeAggregateEventPublisher()->publishMany(
                    ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
                        ->getEventStreamName(),
                    $events
                );
            }
        );
        /** @var DomainEvents $events */

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }
}

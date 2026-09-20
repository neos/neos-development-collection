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

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

/**
 * @internal implementation details of command handlers
 */
trait NodeVariationInternals
{
    use DimensionSpaceInternals;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    protected function createEventsForVariations(
        TargetLocationInSubgraph $targetLocation,
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ?NodeAggregate $parentNodeAggregateForCoverageFilter,
    ): Events {
        $coverage = $this->calculateEffectiveVisibility(
            $targetOrigin,
            $nodeAggregate,
            $parentNodeAggregateForCoverageFilter,
        );
        return match (
            $this->getInterDimensionalVariationGraph()->getVariantType(
                $targetOrigin->toDimensionSpacePoint(),
                $sourceOrigin->toDimensionSpacePoint()
            )
        ) {
            DimensionSpace\VariantType::TYPE_SPECIALIZATION => $this->handleCreateNodeSpecializationVariant(
                $targetLocation,
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $coverage,
            ),
            DimensionSpace\VariantType::TYPE_GENERALIZATION => $this->handleCreateNodeGeneralizationVariant(
                $targetLocation,
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $coverage,
            ),
            default => $this->handleCreateNodePeerVariant(
                $targetLocation,
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $coverage,
            ),
        };
    }

    protected function handleCreateNodeSpecializationVariant(
        TargetLocationInSubgraph $targetLocation,
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $coverage,
    ): Events {
        $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
            $targetLocation,
            $contentGraph,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $coverage,
            []
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return non-empty-array<int,EventInterface>
     */
    protected function collectNodeSpecializationVariantsThatWillHaveBeenCreated(
        TargetLocationInSubgraph $targetLocation,
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $coverage,
        array $events,
    ): array {
        $events[] = new NodeSpecializationVariantWasCreated(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            InterdimensionalSiblings::fromTargetLocationForDimensionSpacePoints(
                targetLocation: $targetLocation,
                dimensionSpacePoints: $coverage,
                sourceDimensionSpacePoint: $sourceOrigin->toDimensionSpacePoint(),
                contentGraph: $contentGraph,
            ),
        );

        foreach (
            $contentGraph->findTetheredChildNodeAggregates(
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $childTargetLocation = TargetLocationInSubgraph::createForTetheredChildNodeAggregate(
                parentNodeAggregateId: $nodeAggregate->nodeAggregateId,
                tetheredChildNodeAggregateId: $tetheredChildNodeAggregate->nodeAggregateId,
                sourceSubgraph: $contentGraph->getSubgraph($sourceOrigin->toDimensionSpacePoint(), VisibilityConstraints::createEmpty()),
            );
            $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
                targetLocation: $childTargetLocation,
                contentGraph: $contentGraph,
                sourceOrigin: $sourceOrigin,
                targetOrigin: $targetOrigin,
                nodeAggregate: $tetheredChildNodeAggregate,
                coverage: $coverage,
                events: $events
            );
        }

        return $events;
    }

    protected function handleCreateNodeGeneralizationVariant(
        TargetLocationInSubgraph $targetLocation,
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $coverage,
    ): Events {
        $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
            $targetLocation,
            $contentGraph,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $coverage,
            []
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return non-empty-array<int,EventInterface>
     */
    protected function collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
        TargetLocationInSubgraph $targetLocation,
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $coverage,
        array $events
    ): array {
        $events[] = new NodeGeneralizationVariantWasCreated(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            InterdimensionalSiblings::fromTargetLocationForDimensionSpacePoints(
                targetLocation: $targetLocation,
                dimensionSpacePoints: $coverage,
                sourceDimensionSpacePoint: $sourceOrigin->toDimensionSpacePoint(),
                contentGraph: $contentGraph,
            ),
        );

        foreach (
            $contentGraph->findTetheredChildNodeAggregates(
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $childTargetLocation = TargetLocationInSubgraph::createForTetheredChildNodeAggregate(
                parentNodeAggregateId: $nodeAggregate->nodeAggregateId,
                tetheredChildNodeAggregateId: $tetheredChildNodeAggregate->nodeAggregateId,
                sourceSubgraph: $contentGraph->getSubgraph($sourceOrigin->toDimensionSpacePoint(), VisibilityConstraints::createEmpty()),
            );
            $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
                $childTargetLocation,
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $coverage,
                $events
            );
        }

        return $events;
    }

    protected function handleCreateNodePeerVariant(
        TargetLocationInSubgraph $targetLocation,
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $coverage,
    ): Events {
        $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
            $targetLocation,
            $contentGraph,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $coverage,
            []
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return non-empty-array<int,EventInterface>
     */
    protected function collectNodePeerVariantsThatWillHaveBeenCreated(
        TargetLocationInSubgraph $targetLocation,
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $coverage,
        array $events
    ): array {
        $events[] = new NodePeerVariantWasCreated(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            InterdimensionalSiblings::fromTargetLocationForDimensionSpacePoints(
                $targetLocation,
                $coverage,
                $sourceOrigin->toDimensionSpacePoint(),
                $contentGraph,
            )
        );

        foreach (
            $contentGraph->findTetheredChildNodeAggregates(
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $childTargetLocation = TargetLocationInSubgraph::createForTetheredChildNodeAggregate(
                parentNodeAggregateId: $nodeAggregate->nodeAggregateId,
                tetheredChildNodeAggregateId: $tetheredChildNodeAggregate->nodeAggregateId,
                sourceSubgraph: $contentGraph->getSubgraph($sourceOrigin->toDimensionSpacePoint(), VisibilityConstraints::createEmpty()),
            );
            $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
                $childTargetLocation,
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $coverage,
                $events
            );
        }

        return $events;
    }

    private function calculateEffectiveVisibility(
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ?NodeAggregate $parentNodeAggregateForCoverageFilter,
    ): DimensionSpacePointSet {
        $specializations = $this->getInterDimensionalVariationGraph()
            ->getIndexedSpecializations($targetOrigin->toDimensionSpacePoint());
        $excludedSet = $parentNodeAggregateForCoverageFilter
            ? $specializations->getDifference($parentNodeAggregateForCoverageFilter->coveredDimensionSpacePoints)
            : DimensionSpacePointSet::fromArray([]);
        foreach (
            $specializations->getIntersection(
                $nodeAggregate->occupiedDimensionSpacePoints->toDimensionSpacePointSet()
            ) as $occupiedSpecialization
        ) {
            $excludedSet = $excludedSet->getUnion(
                $this->getInterDimensionalVariationGraph()->getSpecializationSet($occupiedSpecialization)
            );
        }
        return $this->getInterDimensionalVariationGraph()->getSpecializationSet(
            $targetOrigin->toDimensionSpacePoint(),
            true,
            $excludedSet
        );
    }
}

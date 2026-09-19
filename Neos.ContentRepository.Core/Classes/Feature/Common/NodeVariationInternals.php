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
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal implementation details of command handlers
 */
trait NodeVariationInternals
{
    use SiblingResolutionInternals;

    use DimensionSpaceInternals;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    protected function createEventsForVariations(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        NodeAggregate $nodeAggregate,
        ?NodeAggregate $parentNodeAggregate,
    ): Events {
        return match (
            $this->getInterDimensionalVariationGraph()->getVariantType(
                $targetOrigin->toDimensionSpacePoint(),
                $sourceOrigin->toDimensionSpacePoint()
            )
        ) {
            DimensionSpace\VariantType::TYPE_SPECIALIZATION => $this->handleCreateNodeSpecializationVariant(
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $precedingSiblingNodeAggregateId,
                $succeedingSiblingNodeAggregateId,
                $nodeAggregate,
                $parentNodeAggregate,
            ),
            DimensionSpace\VariantType::TYPE_GENERALIZATION => $this->handleCreateNodeGeneralizationVariant(
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $precedingSiblingNodeAggregateId,
                $succeedingSiblingNodeAggregateId,
                $nodeAggregate,
                $parentNodeAggregate,
            ),
            default => $this->handleCreateNodePeerVariant(
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $precedingSiblingNodeAggregateId,
                $succeedingSiblingNodeAggregateId,
                $nodeAggregate,
                $parentNodeAggregate,
            ),
        };
    }

    protected function handleCreateNodeSpecializationVariant(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        NodeAggregate $nodeAggregate,
        ?NodeAggregate $parentNodeAggregate,
    ): Events {
        $specializationVisibility = $this->calculateEffectiveVisibility(
            $targetOrigin,
            $nodeAggregate,
            $parentNodeAggregate,
        );
        $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
            $contentGraph,
            $sourceOrigin,
            $targetOrigin,
            $parentNodeAggregate->nodeAggregateId,
            $precedingSiblingNodeAggregateId,
            $succeedingSiblingNodeAggregateId,
            $nodeAggregate,
            $specializationVisibility,
            []
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return non-empty-array<int,EventInterface>
     */
    protected function collectNodeSpecializationVariantsThatWillHaveBeenCreated(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $specializationVisibility,
        array $events
    ): array {
        $events[] = new NodeSpecializationVariantWasCreated(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $parentNodeAggregateId,
            $this->resolveInterdimensionalSiblingsForMove(
                $contentGraph,
                $targetOrigin->toDimensionSpacePoint(),
                $specializationVisibility,
                $nodeAggregate->nodeAggregateId,
                $parentNodeAggregateId,
                $succeedingSiblingNodeAggregateId,
                $precedingSiblingNodeAggregateId,
                true,
            ),
        );

        $sourceSubgraph = $contentGraph->getSubgraph(
            $sourceOrigin->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );

        foreach (
            $contentGraph->findTetheredChildNodeAggregates(
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                null,
                $sourceSubgraph->findPrecedingSiblingNodes(
                    $tetheredChildNodeAggregate->nodeAggregateId,
                    FindPrecedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
                )->first()?->aggregateId,
                $sourceSubgraph->findSucceedingSiblingNodes(
                    $tetheredChildNodeAggregate->nodeAggregateId,
                    FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
                )->first()?->aggregateId,
                $tetheredChildNodeAggregate,
                $specializationVisibility,
                $events
            );
        }

        return $events;
    }

    protected function handleCreateNodeGeneralizationVariant(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        NodeAggregate $nodeAggregate,
        ?NodeAggregate $parentNodeAggregate,
    ): Events {
        $generalizationVisibility = $this->calculateEffectiveVisibility(
            $targetOrigin,
            $nodeAggregate,
            $parentNodeAggregate,
        );
        $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
            $contentGraph,
            $sourceOrigin,
            $targetOrigin,
            $parentNodeAggregate->nodeAggregateId,
            $precedingSiblingNodeAggregateId,
            $succeedingSiblingNodeAggregateId,
            $nodeAggregate,
            $generalizationVisibility,
            []
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return non-empty-array<int,EventInterface>
     */
    protected function collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $generalizationVisibility,
        array $events
    ): array {
        $events[] = new NodeGeneralizationVariantWasCreated(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $this->resolveInterdimensionalSiblingsForMove(
                $contentGraph,
                $targetOrigin->toDimensionSpacePoint(),
                $generalizationVisibility,
                $nodeAggregate->nodeAggregateId,
                $parentNodeAggregateId,
                $succeedingSiblingNodeAggregateId,
                $precedingSiblingNodeAggregateId,
                ($parentNodeAggregateId !== null)
                || (($succeedingSiblingNodeAggregateId === null) && ($precedingSiblingNodeAggregateId === null)),
            )
        );

        $sourceSubgraph = $contentGraph->getSubgraph(
            $sourceOrigin->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );

        foreach (
            $contentGraph->findTetheredChildNodeAggregates(
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                null,

                $sourceSubgraph->findPrecedingSiblingNodes(
                    $tetheredChildNodeAggregate->nodeAggregateId,
                    FindPrecedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
                )->first()?->aggregateId,
                $sourceSubgraph->findSucceedingSiblingNodes(
                    $tetheredChildNodeAggregate->nodeAggregateId,
                    FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
                )->first()?->aggregateId,
                $tetheredChildNodeAggregate,
                $generalizationVisibility,
                $events
            );
        }

        return $events;
    }

    protected function handleCreateNodePeerVariant(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        NodeAggregate $nodeAggregate,
        ?NodeAggregate $parentNodeAggregate,
    ): Events {
        $peerVisibility = $this->calculateEffectiveVisibility(
            $targetOrigin,
            $nodeAggregate,
            $parentNodeAggregate,
        );
        $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
            $contentGraph,
            $sourceOrigin,
            $targetOrigin,
            $parentNodeAggregate->nodeAggregateId,
            $precedingSiblingNodeAggregateId,
            $succeedingSiblingNodeAggregateId,
            $nodeAggregate,
            $peerVisibility,
            []
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return non-empty-array<int,EventInterface>
     */
    protected function collectNodePeerVariantsThatWillHaveBeenCreated(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $peerVisibility,
        array $events
    ): array {
        $events[] = new NodePeerVariantWasCreated(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $this->resolveInterdimensionalSiblingsForMove(
                $contentGraph,
                $targetOrigin->toDimensionSpacePoint(),
                $peerVisibility,
                $nodeAggregate->nodeAggregateId,
                $parentNodeAggregateId,
                $succeedingSiblingNodeAggregateId,
                $precedingSiblingNodeAggregateId,
                ($parentNodeAggregateId !== null)
                    || (($succeedingSiblingNodeAggregateId === null) && ($precedingSiblingNodeAggregateId === null)),
            ),
        );

        $sourceSubgraph = $contentGraph->getSubgraph(
            $sourceOrigin->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );

        foreach (
            $contentGraph->findTetheredChildNodeAggregates(
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                null,
                $sourceSubgraph->findPrecedingSiblingNodes(
                    $tetheredChildNodeAggregate->nodeAggregateId,
                    FindPrecedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
                )->first()?->aggregateId,
                $sourceSubgraph->findSucceedingSiblingNodes(
                    $tetheredChildNodeAggregate->nodeAggregateId,
                    FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
                )->first()?->aggregateId,
                $tetheredChildNodeAggregate,
                $peerVisibility,
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

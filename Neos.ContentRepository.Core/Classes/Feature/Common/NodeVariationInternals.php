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
    use InterdimensionalSiblingsProvider;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    protected function createEventsForVariations(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
    ): Events {
        return match (
            $this->getInterDimensionalVariationGraph()->getVariantType(
                $targetOrigin->toDimensionSpacePoint(),
                $sourceOrigin->toDimensionSpacePoint()
            )
        ) {
            DimensionSpace\VariantType::TYPE_SPECIALIZATION => $this->handleCreateNodeSpecializationVariant(
                contentGraph: $contentGraph,
                sourceOrigin: $sourceOrigin,
                targetOrigin: $targetOrigin,
                nodeAggregate: $nodeAggregate,
                parentNodeAggregateId: $parentNodeAggregateId,
                precedingSiblingNodeAggregateId: $precedingSiblingNodeAggregateId,
                succeedingSiblingNodeAggregateId: $succeedingSiblingNodeAggregateId,
            ),
            DimensionSpace\VariantType::TYPE_GENERALIZATION => $this->handleCreateNodeGeneralizationVariant(
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate
            ),
            default => $this->handleCreateNodePeerVariant(
                $contentGraph,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate
            ),
        };
    }

    protected function handleCreateNodeSpecializationVariant(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
    ): Events {
        $specializationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
            contentGraph: $contentGraph,
            sourceOrigin: $sourceOrigin,
            targetOrigin: $targetOrigin,
            nodeAggregate: $nodeAggregate,
            specializationVisibility: $specializationVisibility,
            parentNodeAggregateId: $parentNodeAggregateId,
            precedingSiblingNodeAggregateId: $precedingSiblingNodeAggregateId,
            succeedingSiblingNodeAggregateId: $succeedingSiblingNodeAggregateId,
            events: [],
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
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $specializationVisibility,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        array $events,
    ): array {
        if (!$parentNodeAggregateId && !$succeedingSiblingNodeAggregateId && !$precedingSiblingNodeAggregateId) {
            // preserve legacy behavior: this means the variant is to be created in place
            $originSubgraph = $contentGraph->getSubgraph(
                $sourceOrigin->toDimensionSpacePoint(),
                VisibilityConstraints::createEmpty()
            );
            $precedingSiblingNodeAggregateId = $originSubgraph->findPrecedingSiblingNodes(
                siblingNodeAggregateId: $nodeAggregate->nodeAggregateId,
                filter: FindPrecedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
            )->first()?->aggregateId;
            $succeedingSiblingNodeAggregateId = $originSubgraph->findSucceedingSiblingNodes(
                siblingNodeAggregateId: $nodeAggregate->nodeAggregateId,
                filter: FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
            )->first()?->aggregateId;
        }
        $events[] = new NodeSpecializationVariantWasCreated(
            workspaceName: $contentGraph->getWorkspaceName(),
            contentStreamId: $contentGraph->getContentStreamId(),
            nodeAggregateId: $nodeAggregate->nodeAggregateId,
            sourceOrigin: $sourceOrigin,
            specializationOrigin: $targetOrigin,
            specializationSiblings: $this->resolveInterdimensionalSiblings(
                contentGraph: $contentGraph,
                referenceDimensionSpacePoint: $targetOrigin->toDimensionSpacePoint(),
                affectedDimensionSpacePoints: $specializationVisibility,
                nodeAggregateId: $nodeAggregate->nodeAggregateId,
                parentNodeAggregateId: $parentNodeAggregateId,
                precedingSiblingNodeAggregateId: $precedingSiblingNodeAggregateId,
                succeedingSiblingNodeAggregateId: $succeedingSiblingNodeAggregateId,
                completeSet: true,
            ),
            parentNodeAggregateId: $parentNodeAggregateId,
        );

        foreach (
            $contentGraph->findTetheredChildNodeAggregates(
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $originSubgraph = $contentGraph->getSubgraph(
                $sourceOrigin->toDimensionSpacePoint(),
                VisibilityConstraints::createEmpty()
            );
            $originChildPrecedingSibling = $originSubgraph->findPrecedingSiblingNodes(
                siblingNodeAggregateId: $tetheredChildNodeAggregate->nodeAggregateId,
                filter: FindPrecedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
            )->first();
            $originChildSucceedingSibling = $originSubgraph->findSucceedingSiblingNodes(
                siblingNodeAggregateId: $tetheredChildNodeAggregate->nodeAggregateId,
                filter: FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0))
            )->first();
            $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
                contentGraph: $contentGraph,
                sourceOrigin: $sourceOrigin,
                targetOrigin: $targetOrigin,
                nodeAggregate: $tetheredChildNodeAggregate,
                specializationVisibility: $specializationVisibility,
                parentNodeAggregateId: null,
                precedingSiblingNodeAggregateId: $originChildPrecedingSibling?->aggregateId,
                succeedingSiblingNodeAggregateId: $originChildSucceedingSibling?->aggregateId,
                events: $events
            );
        }

        return $events;
    }

    protected function handleCreateNodeGeneralizationVariant(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate
    ): Events {
        $generalizationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
            $contentGraph,
            $sourceOrigin,
            $targetOrigin,
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
            $this->resolveInterdimensionalSiblingsForVariation(
                $contentGraph,
                $nodeAggregate->nodeAggregateId,
                $sourceOrigin,
                $generalizationVisibility
            )
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
        NodeAggregate $nodeAggregate
    ): Events {
        $peerVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
            $contentGraph,
            $sourceOrigin,
            $targetOrigin,
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
            $this->resolveInterdimensionalSiblingsForVariation(
                $contentGraph,
                $nodeAggregate->nodeAggregateId,
                $sourceOrigin,
                $peerVisibility
            ),
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
                $tetheredChildNodeAggregate,
                $peerVisibility,
                $events
            );
        }

        return $events;
    }

    private function calculateEffectiveVisibility(
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate
    ): DimensionSpacePointSet {
        $specializations = $this->getInterDimensionalVariationGraph()
            ->getIndexedSpecializations($targetOrigin->toDimensionSpacePoint());
        $excludedSet = new DimensionSpacePointSet([]);
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

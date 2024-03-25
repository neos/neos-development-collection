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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\InterdimensionalRelative;
use Neos\ContentRepository\Core\SharedModel\Node\InterdimensionalRelatives;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal implementation details of command handlers
 */
trait NodeVariationInternals
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    protected function createEventsForVariations(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ContentRepository $contentRepository
    ): Events {
        return match (
            $this->getInterDimensionalVariationGraph()->getVariantType(
                $targetOrigin->toDimensionSpacePoint(),
                $sourceOrigin->toDimensionSpacePoint()
            )
        ) {
            DimensionSpace\VariantType::TYPE_SPECIALIZATION => $this->handleCreateNodeSpecializationVariant(
                $contentStreamId,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $contentRepository
            ),
            DimensionSpace\VariantType::TYPE_GENERALIZATION => $this->handleCreateNodeGeneralizationVariant(
                $contentStreamId,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $contentRepository
            ),
            default => $this->handleCreateNodePeerVariant(
                $contentStreamId,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $contentRepository
            ),
        };
    }

    protected function handleCreateNodeSpecializationVariant(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ContentRepository $contentRepository
    ): Events {
        $specializationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
            $contentStreamId,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $specializationVisibility,
            [],
            $contentRepository
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return array<int,EventInterface>
     */
    protected function collectNodeSpecializationVariantsThatWillHaveBeenCreated(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $specializationVisibility,
        array $events,
        ContentRepository $contentRepository
    ): array {
        $events[] = new NodeSpecializationVariantWasCreated(
            $contentStreamId,
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $specializationVisibility,
        );

        foreach (
            $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $contentStreamId,
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
                $contentStreamId,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $specializationVisibility,
                $events,
                $contentRepository
            );
        }

        return $events;
    }

    protected function handleCreateNodeGeneralizationVariant(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ContentRepository $contentRepository
    ): Events {
        $generalizationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
            $contentStreamId,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $generalizationVisibility,
            [],
            $contentRepository
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return array<int,EventInterface>
     */
    protected function collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $generalizationVisibility,
        array $events,
        ContentRepository $contentRepository
    ): array {
        $events[] = new NodeGeneralizationVariantWasCreated(
            $contentStreamId,
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $generalizationVisibility,
        );

        foreach (
            $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $contentStreamId,
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
                $contentStreamId,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $generalizationVisibility,
                $events,
                $contentRepository
            );
        }

        return $events;
    }

    protected function handleCreateNodePeerVariant(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ContentRepository $contentRepository
    ): Events {
        $peerVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
            $contentStreamId,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $peerVisibility,
            [],
            $contentRepository
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return array<int,EventInterface>
     */
    protected function collectNodePeerVariantsThatWillHaveBeenCreated(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $peerVisibility,
        array $events,
        ContentRepository $contentRepository
    ): array {
        $originSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $contentStreamId,
            $sourceOrigin->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $originSiblings = $originSubgraph->findSucceedingSiblingNodes(
            $nodeAggregate->nodeAggregateId,
            FindSucceedingSiblingNodesFilter::create()
        );
        $interdimensionalSiblings = [];
        foreach ($peerVisibility as $peerDimensionSpacePoint) {
            $peerSubgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamId,
                $peerDimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
            $peerSibling = null;
            foreach ($originSiblings as $originSibling) {
                $peerSibling = $peerSubgraph->findNodeById($originSibling->nodeAggregateId);
                if ($peerSibling instanceof Node) {
                    break;
                }
            }
            $interdimensionalSiblings[] = new InterdimensionalSibling(
                $peerDimensionSpacePoint,
                $peerSibling?->nodeAggregateId,
            );
        }
        $events[] = new NodePeerVariantWasCreated(
            $contentStreamId,
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            new InterdimensionalSiblings(...$interdimensionalSiblings),
        );

        foreach (
            $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $contentStreamId,
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
                $contentStreamId,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $peerVisibility,
                $events,
                $contentRepository
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

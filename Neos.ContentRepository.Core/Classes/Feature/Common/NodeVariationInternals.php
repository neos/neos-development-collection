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
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal implementation details of command handlers
 */
trait NodeVariationInternals
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    protected function createEventsForVariations(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate
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
                $nodeAggregate
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
        NodeAggregate $nodeAggregate
    ): Events {
        $specializationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
            $contentGraph,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $specializationVisibility,
            []
        );

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @return array<int,EventInterface>
     */
    protected function collectNodeSpecializationVariantsThatWillHaveBeenCreated(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $specializationVisibility,
        array $events
    ): array {
        $events[] = new NodeSpecializationVariantWasCreated(
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $this->resolveInterdimensionalSiblings(
                $contentGraph,
                $nodeAggregate->nodeAggregateId,
                $sourceOrigin,
                $specializationVisibility
            ),
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
     * @return array<int,EventInterface>
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
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $this->resolveInterdimensionalSiblings(
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
     * @return array<int,EventInterface>
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
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $this->resolveInterdimensionalSiblings(
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

    /**
     * Resolves the succeeding siblings for the node variant to be created and all dimension space points the variant will cover.
     *
     * For each dimension space point in the variant coverage
     * a) All the succeeding siblings of the node aggregate in the source origin are checked
     * and the first one existing in this dimension space point is used
     * b) As fallback no succeeding sibling is specified
     *
     * Developers hint:
     * Similar to {@see NodeCreationInternals::resolveInterdimensionalSiblingsForCreation()}
     * except this operates on the to-be-varied node itself instead of an explicitly set succeeding sibling
     */
    private function resolveInterdimensionalSiblings(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $varyingNodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        DimensionSpacePointSet $variantCoverage,
    ): InterdimensionalSiblings {
        $originSiblings = $contentGraph
            ->getSubgraph($sourceOrigin->toDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions())
            ->findSucceedingSiblingNodes($varyingNodeAggregateId, FindSucceedingSiblingNodesFilter::create());

        $interdimensionalSiblings = [];
        foreach ($variantCoverage as $variantDimensionSpacePoint) {
            // check the siblings succeeding in the origin dimension space point
            foreach ($originSiblings as $originSibling) {
                $variantSibling = $contentGraph->getSubgraph($variantDimensionSpacePoint, VisibilityConstraints::withoutRestrictions())->findNodeById($originSibling->nodeAggregateId);
                if (!$variantSibling) {
                    continue;
                }
                // a) one of the further succeeding sibling exists in this dimension space point
                $interdimensionalSiblings[] = new InterdimensionalSibling(
                    $variantDimensionSpacePoint,
                    $variantSibling->nodeAggregateId,
                );
                continue 2;
            }

            // b) fallback; there is no succeeding sibling in this dimension space point
            $interdimensionalSiblings[] = new InterdimensionalSibling(
                $variantDimensionSpacePoint,
                null,
            );
        }

        return new InterdimensionalSiblings(...$interdimensionalSiblings);
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

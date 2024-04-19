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
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal implementation details of command handlers
 */
trait NodeVariationInternals
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    protected function createEventsForVariations(
        WorkspaceName $workspaceName,
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
                $workspaceName,
                $contentStreamId,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $contentRepository
            ),
            DimensionSpace\VariantType::TYPE_GENERALIZATION => $this->handleCreateNodeGeneralizationVariant(
                $workspaceName,
                $contentStreamId,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $contentRepository
            ),
            default => $this->handleCreateNodePeerVariant(
                $workspaceName,
                $contentStreamId,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $contentRepository
            ),
        };
    }

    protected function handleCreateNodeSpecializationVariant(
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ContentRepository $contentRepository
    ): Events {
        $specializationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
            $workspaceName,
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
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $specializationVisibility,
        array $events,
        ContentRepository $contentRepository
    ): array {
        $events[] = new NodeSpecializationVariantWasCreated(
            $workspaceName,
            $contentStreamId,
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $this->resolveInterdimensionalSiblings(
                $contentRepository,
                $contentStreamId,
                $nodeAggregate->nodeAggregateId,
                $sourceOrigin,
                $specializationVisibility
            ),
        );

        foreach (
            $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $contentStreamId,
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
                $workspaceName,
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
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ContentRepository $contentRepository
    ): Events {
        $generalizationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
            $workspaceName,
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
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $generalizationVisibility,
        array $events,
        ContentRepository $contentRepository
    ): array {
        $events[] = new NodeGeneralizationVariantWasCreated(
            $workspaceName,
            $contentStreamId,
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $this->resolveInterdimensionalSiblings(
                $contentRepository,
                $contentStreamId,
                $nodeAggregate->nodeAggregateId,
                $sourceOrigin,
                $generalizationVisibility
            )
        );

        foreach (
            $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $contentStreamId,
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
                $workspaceName,
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
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ContentRepository $contentRepository
    ): Events {
        $peerVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
            $workspaceName,
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
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $peerVisibility,
        array $events,
        ContentRepository $contentRepository
    ): array {
        $events[] = new NodePeerVariantWasCreated(
            $workspaceName,
            $contentStreamId,
            $nodeAggregate->nodeAggregateId,
            $sourceOrigin,
            $targetOrigin,
            $this->resolveInterdimensionalSiblings(
                $contentRepository,
                $contentStreamId,
                $nodeAggregate->nodeAggregateId,
                $sourceOrigin,
                $peerVisibility
            ),
        );

        foreach (
            $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $contentStreamId,
                $nodeAggregate->nodeAggregateId
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
                $workspaceName,
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
        ContentRepository $contentRepository,
        ContentStreamId $contentStreamId,
        NodeAggregateId $varyingNodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        DimensionSpacePointSet $variantCoverage,
    ): InterdimensionalSiblings {
        $originSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $contentStreamId,
            $sourceOrigin->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $originSiblings = $originSubgraph->findSucceedingSiblingNodes(
            $varyingNodeAggregateId,
            FindSucceedingSiblingNodesFilter::create()
        );

        $interdimensionalSiblings = [];
        foreach ($variantCoverage as $variantDimensionSpacePoint) {
            $variantSubgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamId,
                $variantDimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );

            // check the siblings succeeding in the origin dimension space point
            foreach ($originSiblings as $originSibling) {
                $variantSibling = $variantSubgraph->findNodeById($originSibling->nodeAggregateId);
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

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

namespace Neos\ContentRepository\Feature\Common;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * @internal implementation details of command handlers
 */
trait NodeVariationInternals
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    protected function createEventsForVariations(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier,
        ContentRepository $contentRepository
    ): Events {
        return match (
            $this->getInterDimensionalVariationGraph()->getVariantType(
                $targetOrigin->toDimensionSpacePoint(),
                $sourceOrigin->toDimensionSpacePoint()
            )
        ) {
            DimensionSpace\VariantType::TYPE_SPECIALIZATION => $this->handleCreateNodeSpecializationVariant(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $initiatingUserIdentifier,
                $contentRepository
            ),
            DimensionSpace\VariantType::TYPE_GENERALIZATION => $this->handleCreateNodeGeneralizationVariant(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $initiatingUserIdentifier,
                $contentRepository
            ),
            default => $this->handleCreateNodePeerVariant(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $initiatingUserIdentifier,
                $contentRepository
            ),
        };
    }

    protected function handleCreateNodeSpecializationVariant(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier,
        ContentRepository $contentRepository
    ): Events {
        $specializationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
            $contentStreamIdentifier,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $specializationVisibility,
            $initiatingUserIdentifier,
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
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $specializationVisibility,
        UserIdentifier $initiatingUserIdentifier,
        array $events,
        ContentRepository $contentRepository
    ): array {
        $events[] = new NodeSpecializationVariantWasCreated(
            $contentStreamIdentifier,
            $nodeAggregate->nodeAggregateIdentifier,
            $sourceOrigin,
            $targetOrigin,
            $specializationVisibility,
            $initiatingUserIdentifier
        );

        foreach (
            $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $contentStreamIdentifier,
                $nodeAggregate->nodeAggregateIdentifier
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $specializationVisibility,
                $initiatingUserIdentifier,
                $events,
                $contentRepository
            );
        }

        return $events;
    }

    protected function handleCreateNodeGeneralizationVariant(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier,
        ContentRepository $contentRepository
    ): Events {
        $generalizationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
            $contentStreamIdentifier,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $generalizationVisibility,
            $initiatingUserIdentifier,
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
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $generalizationVisibility,
        UserIdentifier $initiatingUserIdentifier,
        array $events,
        ContentRepository $contentRepository
    ): array {
        $events[] = new NodeGeneralizationVariantWasCreated(
            $contentStreamIdentifier,
            $nodeAggregate->nodeAggregateIdentifier,
            $sourceOrigin,
            $targetOrigin,
            $generalizationVisibility,
            $initiatingUserIdentifier
        );

        foreach (
            $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $contentStreamIdentifier,
                $nodeAggregate->nodeAggregateIdentifier
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $generalizationVisibility,
                $initiatingUserIdentifier,
                $events,
                $contentRepository
            );
        }

        return $events;
    }

    protected function handleCreateNodePeerVariant(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier,
        ContentRepository $contentRepository
    ): Events {
        $peerVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
            $contentStreamIdentifier,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $peerVisibility,
            $initiatingUserIdentifier,
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
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $peerVisibility,
        UserIdentifier $initiatingUserIdentifier,
        array $events,
        ContentRepository $contentRepository
    ): array {
        $events[] = new NodePeerVariantWasCreated(
            $contentStreamIdentifier,
            $nodeAggregate->nodeAggregateIdentifier,
            $sourceOrigin,
            $targetOrigin,
            $peerVisibility,
            $initiatingUserIdentifier
        );

        foreach (
            $contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $contentStreamIdentifier,
                $nodeAggregate->nodeAggregateIdentifier
            ) as $tetheredChildNodeAggregate
        ) {
            $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $peerVisibility,
                $initiatingUserIdentifier,
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

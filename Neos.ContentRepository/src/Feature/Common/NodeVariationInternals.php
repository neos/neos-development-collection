<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Feature\Common;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeVariationInternals
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function getContentGraph(): ContentGraphInterface;

    protected function createEventsForVariations(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEvents {
        return match ($this->getInterDimensionalVariationGraph()->getVariantType(
            $targetOrigin->toDimensionSpacePoint(),
            $sourceOrigin->toDimensionSpacePoint()
        )) {
            DimensionSpace\VariantType::TYPE_SPECIALIZATION => $this->handleCreateNodeSpecializationVariant(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $initiatingUserIdentifier
            ),
            DimensionSpace\VariantType::TYPE_GENERALIZATION => $this->handleCreateNodeGeneralizationVariant(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $initiatingUserIdentifier
            ),
            default => $this->handleCreateNodePeerVariant(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $nodeAggregate,
                $initiatingUserIdentifier
            ),
        };
    }

    protected function handleCreateNodeSpecializationVariant(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEvents {
        $specializationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
            $contentStreamIdentifier,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $specializationVisibility,
            $initiatingUserIdentifier,
            []
        );

        return DomainEvents::fromArray($events);
    }

    /**
     * @param array<int,DecoratedEvent> $events
     * @return array<int,DecoratedEvent>
     */
    protected function collectNodeSpecializationVariantsThatWillHaveBeenCreated(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $specializationVisibility,
        UserIdentifier $initiatingUserIdentifier,
        array $events
    ): array {
        $events[] = DecoratedEvent::addIdentifier(
            new NodeSpecializationVariantWasCreated(
                $contentStreamIdentifier,
                $nodeAggregate->getIdentifier(),
                $sourceOrigin,
                $targetOrigin,
                $specializationVisibility,
                $initiatingUserIdentifier
            ),
            Uuid::uuid4()->toString()
        );

        foreach ($this->getContentGraph()->findTetheredChildNodeAggregates(
            $contentStreamIdentifier,
            $nodeAggregate->getIdentifier()
        ) as $tetheredChildNodeAggregate) {
            $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $specializationVisibility,
                $initiatingUserIdentifier,
                $events
            );
        }

        return $events;
    }

    protected function handleCreateNodeGeneralizationVariant(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEvents {
        $generalizationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
            $contentStreamIdentifier,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $generalizationVisibility,
            $initiatingUserIdentifier,
            []
        );

        return DomainEvents::fromArray($events);
    }

    /**
     * @param array<int,DecoratedEvent> $events
     * @return array<int,DecoratedEvent>
     */
    protected function collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $generalizationVisibility,
        UserIdentifier $initiatingUserIdentifier,
        array $events
    ): array {
        $events[] = DecoratedEvent::addIdentifier(
            new NodeGeneralizationVariantWasCreated(
                $contentStreamIdentifier,
                $nodeAggregate->getIdentifier(),
                $sourceOrigin,
                $targetOrigin,
                $generalizationVisibility,
                $initiatingUserIdentifier
            ),
            Uuid::uuid4()->toString()
        );

        foreach ($this->getContentGraph()->findTetheredChildNodeAggregates(
            $contentStreamIdentifier,
            $nodeAggregate->getIdentifier()
        ) as $tetheredChildNodeAggregate) {
            $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $generalizationVisibility,
                $initiatingUserIdentifier,
                $events
            );
        }

        return $events;
    }

    protected function handleCreateNodePeerVariant(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEvents {
        $peerVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
            $contentStreamIdentifier,
            $sourceOrigin,
            $targetOrigin,
            $nodeAggregate,
            $peerVisibility,
            $initiatingUserIdentifier,
            []
        );

        return DomainEvents::fromArray($events);
    }

    /**
     * @param array<int,DecoratedEvent> $events
     * @return array<int,DecoratedEvent>
     */
    protected function collectNodePeerVariantsThatWillHaveBeenCreated(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $peerVisibility,
        UserIdentifier $initiatingUserIdentifier,
        array $events
    ): array {
        $events[] = DecoratedEvent::addIdentifier(
            new NodePeerVariantWasCreated(
                $contentStreamIdentifier,
                $nodeAggregate->getIdentifier(),
                $sourceOrigin,
                $targetOrigin,
                $peerVisibility,
                $initiatingUserIdentifier
            ),
            Uuid::uuid4()->toString()
        );

        foreach ($this->getContentGraph()->findTetheredChildNodeAggregates(
            $contentStreamIdentifier,
            $nodeAggregate->getIdentifier()
        ) as $tetheredChildNodeAggregate) {
            $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated(
                $contentStreamIdentifier,
                $sourceOrigin,
                $targetOrigin,
                $tetheredChildNodeAggregate,
                $peerVisibility,
                $initiatingUserIdentifier,
                $events
            );
        }

        return $events;
    }

    private function calculateEffectiveVisibility(
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate
    ): DimensionSpacePointSet {
        $specializations = $this->getInterDimensionalVariationGraph()
            ->getIndexedSpecializations($targetOrigin->toDimensionSpacePoint());
        $excludedSet = new DimensionSpacePointSet([]);
        foreach ($specializations->getIntersection(
            $nodeAggregate->getOccupiedDimensionSpacePoints()->toDimensionSpacePointSet()
        ) as $occupiedSpecialization) {
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

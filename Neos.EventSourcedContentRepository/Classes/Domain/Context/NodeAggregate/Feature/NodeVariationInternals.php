<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature;

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
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeGeneralizationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePeerVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeSpecializationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeVariationInternals
{
    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function getContentGraph(): ContentGraphInterface;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param OriginDimensionSpacePoint $sourceOrigin
     * @param OriginDimensionSpacePoint $targetOrigin
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return DomainEvents
     */
    private function createEventsForVariations(ContentStreamIdentifier $contentStreamIdentifier, OriginDimensionSpacePoint $sourceOrigin, OriginDimensionSpacePoint $targetOrigin, ReadableNodeAggregateInterface $nodeAggregate): DomainEvents
    {
        switch ($this->getInterDimensionalVariationGraph()->getVariantType($targetOrigin, $sourceOrigin)->getType()) {
            case DimensionSpace\VariantType::TYPE_SPECIALIZATION:
                $events = $this->handleCreateNodeSpecializationVariant($contentStreamIdentifier, $sourceOrigin, $targetOrigin, $nodeAggregate);
            break;
            case DimensionSpace\VariantType::TYPE_GENERALIZATION:
                $events = $this->handleCreateNodeGeneralizationVariant($contentStreamIdentifier, $sourceOrigin, $targetOrigin, $nodeAggregate);
            break;
            case DimensionSpace\VariantType::TYPE_PEER:
            default:
                $events = $this->handleCreateNodePeerVariant($contentStreamIdentifier, $sourceOrigin, $targetOrigin, $nodeAggregate);
        }
        return $events;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param OriginDimensionSpacePoint $sourceOrigin
     * @param OriginDimensionSpacePoint $targetOrigin
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return DomainEvents
     */
    protected function handleCreateNodeSpecializationVariant(ContentStreamIdentifier $contentStreamIdentifier, OriginDimensionSpacePoint $sourceOrigin, OriginDimensionSpacePoint $targetOrigin, ReadableNodeAggregateInterface $nodeAggregate): DomainEvents
    {
        $specializationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated($contentStreamIdentifier, $sourceOrigin, $targetOrigin, $nodeAggregate, $specializationVisibility, []);
        return DomainEvents::fromArray($events);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param OriginDimensionSpacePoint $sourceOrigin
     * @param OriginDimensionSpacePoint $targetOrigin
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePointSet $specializationVisibility
     * @param array $events
     * @return array|NodeSpecializationVariantWasCreated[]
     */
    protected function collectNodeSpecializationVariantsThatWillHaveBeenCreated(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $specializationVisibility,
        array $events
    ): array {
        $events[] = DecoratedEvent::addIdentifier(
            new NodeSpecializationVariantWasCreated(
                $contentStreamIdentifier,
                $nodeAggregate->getIdentifier(),
                $sourceOrigin,
                $targetOrigin,
                $specializationVisibility
            ),
            Uuid::uuid4()->toString()
        );

        foreach ($this->getContentGraph()->findTetheredChildNodeAggregates($contentStreamIdentifier, $nodeAggregate->getIdentifier()) as $tetheredChildNodeAggregate) {
            $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated($contentStreamIdentifier, $sourceOrigin, $targetOrigin, $tetheredChildNodeAggregate, $specializationVisibility, $events);
        }

        return $events;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param OriginDimensionSpacePoint $sourceOrigin
     * @param OriginDimensionSpacePoint $targetOrigin
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return DomainEvents
     */
    protected function handleCreateNodeGeneralizationVariant(ContentStreamIdentifier $contentStreamIdentifier, OriginDimensionSpacePoint $sourceOrigin, OriginDimensionSpacePoint $targetOrigin, ReadableNodeAggregateInterface $nodeAggregate): DomainEvents
    {
        $generalizationVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated($contentStreamIdentifier, $sourceOrigin, $targetOrigin, $nodeAggregate, $generalizationVisibility, []);
        return DomainEvents::fromArray($events);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param OriginDimensionSpacePoint $sourceOrigin
     * @param OriginDimensionSpacePoint $targetOrigin
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePointSet $generalizationVisibility
     * @param array $events
     * @return array|NodeSpecializationVariantWasCreated[]
     */
    protected function collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $generalizationVisibility,
        array $events
    ): array {
        $events[] = DecoratedEvent::addIdentifier(
            new NodeGeneralizationVariantWasCreated(
                $contentStreamIdentifier,
                $nodeAggregate->getIdentifier(),
                $sourceOrigin,
                $targetOrigin,
                $generalizationVisibility
            ),
            Uuid::uuid4()->toString()
        );

        foreach ($this->getContentGraph()->findTetheredChildNodeAggregates($contentStreamIdentifier, $nodeAggregate->getIdentifier()) as $tetheredChildNodeAggregate) {
            $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated($contentStreamIdentifier, $sourceOrigin, $targetOrigin, $tetheredChildNodeAggregate, $generalizationVisibility, $events);
        }

        return $events;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param OriginDimensionSpacePoint $sourceOrigin
     * @param OriginDimensionSpacePoint $targetOrigin
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return DomainEvents
     */
    protected function handleCreateNodePeerVariant(ContentStreamIdentifier $contentStreamIdentifier, OriginDimensionSpacePoint $sourceOrigin, OriginDimensionSpacePoint $targetOrigin, ReadableNodeAggregateInterface $nodeAggregate): DomainEvents
    {
        $peerVisibility = $this->calculateEffectiveVisibility($targetOrigin, $nodeAggregate);
        $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated($contentStreamIdentifier, $sourceOrigin, $targetOrigin, $nodeAggregate, $peerVisibility, []);
        return DomainEvents::fromArray($events);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param OriginDimensionSpacePoint $sourceOrigin
     * @param OriginDimensionSpacePoint $targetOrigin
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePointSet $peerVisibility
     * @param array $events
     * @return array|NodePeerVariantWasCreated[]
     */
    protected function collectNodePeerVariantsThatWillHaveBeenCreated(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $peerVisibility,
        array $events
    ): array {
        $events[] = DecoratedEvent::addIdentifier(
            new NodePeerVariantWasCreated(
                $contentStreamIdentifier,
                $nodeAggregate->getIdentifier(),
                $sourceOrigin,
                $targetOrigin,
                $peerVisibility
            ),
            Uuid::uuid4()->toString()
        );

        foreach ($this->getContentGraph()->findTetheredChildNodeAggregates($contentStreamIdentifier, $nodeAggregate->getIdentifier()) as $tetheredChildNodeAggregate) {
            $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated($contentStreamIdentifier, $sourceOrigin, $targetOrigin, $tetheredChildNodeAggregate, $peerVisibility, $events);
        }

        return $events;
    }

    /**
     * @param OriginDimensionSpacePoint $targetOrigin
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return DimensionSpacePointSet
     */
    private function calculateEffectiveVisibility(OriginDimensionSpacePoint $targetOrigin, ReadableNodeAggregateInterface $nodeAggregate): DimensionSpacePointSet
    {
        $specializations = $this->getInterDimensionalVariationGraph()->getIndexedSpecializations($targetOrigin);
        $excludedSet = new DimensionSpacePointSet([]);
        foreach ($specializations->getIntersection($nodeAggregate->getOccupiedDimensionSpacePoints()->toDimensionSpacePointSet()) as $occupiedSpecialization) {
            $excludedSet = $excludedSet->getUnion($this->getInterDimensionalVariationGraph()->getSpecializationSet($occupiedSpecialization));
        }
        return $this->getInterDimensionalVariationGraph()->getSpecializationSet(
            $targetOrigin,
            true,
            $excludedSet
        );
    }
}

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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeVariant;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeGeneralizationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePeerVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeSpecializationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\DimensionSpacePointIsNotYetOccupied;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregateCurrentlyExists;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeVariation
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph;

    abstract protected function getContentGraph(): ContentGraphInterface;

    abstract protected function areAncestorNodeTypeConstraintChecksEnabled(): bool;

    /**
     * @param CreateNodeVariant $command
     * @return CommandResult
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyExists
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws DimensionSpacePointIsNotYetOccupied
     * @throws DimensionSpacePointIsAlreadyOccupied
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint
     */
    public function handleCreateNodeVariant(CreateNodeVariant $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $nodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $this->requireDimensionSpacePointToExist($command->getSourceOrigin());
        $this->requireDimensionSpacePointToExist($command->getTargetOrigin());
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint($nodeAggregate, $command->getSourceOrigin());
        $this->requireNodeAggregateToNotOccupyDimensionSpacePoint($nodeAggregate, $command->getTargetOrigin());

        $parentNodeAggregate = $this->getContentGraph()->findParentNodeAggregateByChildOriginDimensionSpacePoint(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier(),
            $command->getSourceOrigin()
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint($parentNodeAggregate, $command->getTargetOrigin());

        switch ($this->getInterDimensionalVariationGraph()->getVariantType($command->getTargetOrigin(), $command->getSourceOrigin())->getType()) {
            case DimensionSpace\VariantType::TYPE_SPECIALIZATION:
                $events = $this->handleCreateNodeSpecializationVariant($command, $nodeAggregate);
                break;
            case DimensionSpace\VariantType::TYPE_GENERALIZATION:
                $events = $this->handleCreateNodeGeneralizationVariant($command, $nodeAggregate);
                break;
            case DimensionSpace\VariantType::TYPE_PEER:
            default:
                $events = $this->handleCreateNodePeerVariant($command, $nodeAggregate);
        }

        $publishedEvents = DomainEvents::createEmpty();
        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, $events, &$publishedEvents) {
            foreach ($events as $event) {
                $domainEvents = DomainEvents::withSingleEvent(
                    DecoratedEvent::addIdentifier(
                        $event,
                        Uuid::uuid4()->toString()
                    )
                );

                $streamName = ContentStream\ContentStreamEventStreamName::fromContentStreamIdentifier(
                    $event->getContentStreamIdentifier()
                );

                $this->getNodeAggregateEventPublisher()->publishMany($streamName->getEventStreamName(), $domainEvents);

                $publishedEvents = $publishedEvents->appendEvents($domainEvents);
            }
        });

        return CommandResult::fromPublishedEvents($publishedEvents);
    }

    /**
     * @param CreateNodeVariant $command
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return array|NodeSpecializationVariantWasCreated[]
     */
    protected function handleCreateNodeSpecializationVariant(CreateNodeVariant $command, ReadableNodeAggregateInterface $nodeAggregate): array
    {
        $specializations = $this->getInterDimensionalVariationGraph()->getIndexedSpecializations($command->getSourceOrigin());
        $excludedSet = new DimensionSpacePointSet([]);
        foreach ($specializations->getIntersection($nodeAggregate->getOccupiedDimensionSpacePoints()) as $occupiedSpecialization) {
            $excludedSet = $excludedSet->getUnion($this->getInterDimensionalVariationGraph()->getSpecializationSet($occupiedSpecialization));
        }
        $specializationVisibility = $this->getInterDimensionalVariationGraph()->getSpecializationSet(
            $command->getTargetOrigin(),
            true,
            $excludedSet
        );

        $events = [];

        return $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated($command, $nodeAggregate, $specializationVisibility, $events);
    }

    /**
     * @param CreateNodeVariant $command
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePointSet $specializationVisibility
     * @param array $events
     * @return array|NodeSpecializationVariantWasCreated[]
     */
    protected function collectNodeSpecializationVariantsThatWillHaveBeenCreated(
        CreateNodeVariant $command,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $specializationVisibility,
        array $events
    ): array {
        $events[] = new NodeSpecializationVariantWasCreated(
            $command->getContentStreamIdentifier(),
            $nodeAggregate->getIdentifier(),
            $command->getSourceOrigin(),
            $command->getTargetOrigin(),
            $specializationVisibility
        );

        foreach ($this->getContentGraph()->findTetheredChildNodeAggregates($command->getContentStreamIdentifier(), $nodeAggregate->getIdentifier()) as $tetheredChildNodeAggregate) {
            $events = $this->collectNodeSpecializationVariantsThatWillHaveBeenCreated($command, $tetheredChildNodeAggregate, $specializationVisibility, $events);
        }

        return $events;
    }

    /**
     * @param CreateNodeVariant $command
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return array|NodeGeneralizationVariantWasCreated[]
     */
    protected function handleCreateNodeGeneralizationVariant(CreateNodeVariant $command, ReadableNodeAggregateInterface $nodeAggregate): array
    {
        $specializations = $this->getInterDimensionalVariationGraph()->getIndexedSpecializations($command->getTargetOrigin());
        $excludedSet = new DimensionSpacePointSet([]);
        foreach ($specializations->getIntersection($nodeAggregate->getOccupiedDimensionSpacePoints()) as $occupiedSpecialization) {
            $excludedSet = $excludedSet->getUnion($this->getInterDimensionalVariationGraph()->getSpecializationSet($occupiedSpecialization));
        }
        $generalizationVisibility = $this->getInterDimensionalVariationGraph()->getSpecializationSet(
            $command->getTargetOrigin(),
            true,
            $excludedSet
        );
        $events = [];

        return $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated($command, $nodeAggregate, $generalizationVisibility, $events);
    }

    /**
     * @param CreateNodeVariant $command
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePointSet $generalizationVisibility
     * @param array $events
     * @return array|NodeSpecializationVariantWasCreated[]
     */
    protected function collectNodeGeneralizationVariantsThatWillHaveBeenCreated(
        CreateNodeVariant $command,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $generalizationVisibility,
        array $events
    ): array {
        $events[] = new NodeGeneralizationVariantWasCreated(
            $command->getContentStreamIdentifier(),
            $nodeAggregate->getIdentifier(),
            $command->getSourceOrigin(),
            $command->getTargetOrigin(),
            $generalizationVisibility
        );

        foreach ($this->getContentGraph()->findTetheredChildNodeAggregates($command->getContentStreamIdentifier(), $nodeAggregate->getIdentifier()) as $tetheredChildNodeAggregate) {
            $events = $this->collectNodeGeneralizationVariantsThatWillHaveBeenCreated($command, $tetheredChildNodeAggregate, $generalizationVisibility, $events);
        }

        return $events;
    }

    /**
     * @param CreateNodeVariant $command
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return array|NodePeerVariantWasCreated[]
     */
    protected function handleCreateNodePeerVariant(CreateNodeVariant $command, ReadableNodeAggregateInterface $nodeAggregate): array
    {
        $specializations = $this->getInterDimensionalVariationGraph()->getIndexedSpecializations($command->getTargetOrigin());
        $excludedSet = new DimensionSpacePointSet([]);
        foreach ($specializations->getIntersection($nodeAggregate->getOccupiedDimensionSpacePoints()) as $occupiedSpecialization) {
            $excludedSet = $excludedSet->getUnion($this->getInterDimensionalVariationGraph()->getSpecializationSet($occupiedSpecialization));
        }
        $peerVisibility = $this->getInterDimensionalVariationGraph()->getSpecializationSet(
            $command->getTargetOrigin(),
            true,
            $excludedSet
        );
        $events = [];

        return $this->collectNodePeerVariantsThatWillHaveBeenCreated($command, $nodeAggregate, $peerVisibility, $events);
    }

    /**
     * @param CreateNodeVariant $command
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePointSet $peerVisibility
     * @param array $events
     * @return array|NodePeerVariantWasCreated[]
     */
    protected function collectNodePeerVariantsThatWillHaveBeenCreated(
        CreateNodeVariant $command,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $peerVisibility,
        array $events
    ): array {
        $events[] = new NodePeerVariantWasCreated(
            $command->getContentStreamIdentifier(),
            $nodeAggregate->getIdentifier(),
            $command->getSourceOrigin(),
            $command->getTargetOrigin(),
            $peerVisibility
        );

        foreach ($this->getContentGraph()->findTetheredChildNodeAggregates($command->getContentStreamIdentifier(), $nodeAggregate->getIdentifier()) as $tetheredChildNodeAggregate) {
            $events = $this->collectNodePeerVariantsThatWillHaveBeenCreated($command, $tetheredChildNodeAggregate, $peerVisibility, $events);
        }

        return $events;
    }
}

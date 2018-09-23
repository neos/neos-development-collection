<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeEventPublisher;
use Neos\EventSourcedContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException;
use Neos\EventSourcing\EventStore\StreamNameFilter;

/**
 * The node aggregate
 *
 * Aggregates all nodes with a shared external identity that are varied across the Dimension Space.
 * An example would be a product node that is translated into different languages but uses a shared identifier,
 * e.g. MPN or GTIN
 *
 * The aggregate enforces that each dimension space point can only ever be occupied by one of its nodes.
 */
final class NodeAggregate
{
    /**
     * @var NodeAggregateIdentifier
     */
    protected $identifier;

    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var string
     */
    protected $streamName;

    /**
     * @var NodeEventPublisher
     */
    protected $nodeEventPublisher;


    public function __construct(NodeAggregateIdentifier $identifier, EventStore $eventStore, string $streamName, NodeEventPublisher $nodeEventPublisher)
    {
        $this->identifier = $identifier;
        $this->eventStore = $eventStore;
        $this->streamName = $streamName;
        $this->nodeEventPublisher = $nodeEventPublisher;
    }


    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws DimensionSpacePointIsNotYetOccupied
     */
    public function requireDimensionSpacePointToBeOccupied(DimensionSpacePoint $dimensionSpacePoint)
    {
        if (!$this->isDimensionSpacePointOccupied($dimensionSpacePoint)) {
            throw new DimensionSpacePointIsNotYetOccupied('The source dimension space point "' . $dimensionSpacePoint . '" is not yet occupied', 1521312039);
        }
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws DimensionSpacePointIsAlreadyOccupied
     */
    public function requireDimensionSpacePointToBeUnoccupied(DimensionSpacePoint $dimensionSpacePoint)
    {
        if ($this->isDimensionSpacePointOccupied($dimensionSpacePoint)) {
            throw new DimensionSpacePointIsAlreadyOccupied('The target dimension space point "' . $dimensionSpacePoint . '" is already occupied', 1521314881);
        }
    }

    public function getOccupiedDimensionSpacePoints(): DimensionSpacePointSet
    {
        $occupiedDimensionSpacePoints = [];

        $eventStream = $this->getEventStream();
        if ($eventStream) {
            foreach ($eventStream as $eventAndRawEvent) {
                $event = $eventAndRawEvent->getEvent();
                switch (get_class($event)) {
                    case NodeAggregateWithNodeWasCreated::class:
                        /** @var NodeAggregateWithNodeWasCreated $event */
                        $occupiedDimensionSpacePoints[$event->getDimensionSpacePoint()->getHash()] = $event->getDimensionSpacePoint();
                        break;
                    case Event\NodeSpecializationWasCreated::class:
                        /** @var Event\NodeSpecializationWasCreated $event */
                        $occupiedDimensionSpacePoints[$event->getSpecializationLocation()->getHash()] = $event->getSpecializationLocation();
                        break;
                    case Event\NodeGeneralizationWasCreated::class:
                        /** @var Event\NodeGeneralizationWasCreated $event */
                        $occupiedDimensionSpacePoints[$event->getGeneralizationLocation()->getHash()] = $event->getGeneralizationLocation();
                        break;
                    default:
                        continue;
                }
            }
        }

        return new DimensionSpacePointSet($occupiedDimensionSpacePoints);
    }

    public function getVisibleDimensionSpacePoints(): DimensionSpacePointSet
    {
        $visibleDimensionSpacePoints = [];

        $eventStream = $this->getEventStream();
        if ($eventStream) {
            foreach ($eventStream as $eventAndRawEvent) {
                $event = $eventAndRawEvent->getEvent();
                switch (get_class($event)) {
                    case NodeAggregateWithNodeWasCreated::class:
                        /** @var NodeAggregateWithNodeWasCreated $event */
                        foreach ($event->getVisibleDimensionSpacePoints()->getPoints() as $visibleDimensionSpacePoint) {
                            $visibleDimensionSpacePoints[$visibleDimensionSpacePoint->getHash()] = $visibleDimensionSpacePoint;
                        }
                        break;
                    case Event\NodeSpecializationWasCreated::class:
                        /** @var Event\NodeSpecializationWasCreated $event */
                        foreach ($event->getSpecializationVisibility()->getPoints() as $visibleDimensionSpacePoint) {
                            $visibleDimensionSpacePoints[$visibleDimensionSpacePoint->getHash()] = $visibleDimensionSpacePoint;
                        }
                        break;
                    case Event\NodeGeneralizationWasCreated::class:
                        /** @var Event\NodeGeneralizationWasCreated $event */
                        foreach ($event->getGeneralizationVisibility()->getPoints() as $visibleDimensionSpacePoint) {
                            $visibleDimensionSpacePoints[$visibleDimensionSpacePoint->getHash()] = $visibleDimensionSpacePoint;
                        }
                        break;
                    default:
                        continue;
                }
            }
        }

        return new DimensionSpacePointSet($visibleDimensionSpacePoints);
    }

    public function isDimensionSpacePointOccupied(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        $dimensionSpacePointOccupied = false;
        $eventStream = $this->getEventStream();
        if ($eventStream) {
            foreach ($eventStream as $eventAndRawEvent) {
                $event = $eventAndRawEvent->getEvent();
                switch (get_class($event)) {
                    case NodeAggregateWithNodeWasCreated::class:
                        /** @var NodeAggregateWithNodeWasCreated $event */
                        $dimensionSpacePointOccupied |= $event->getDimensionSpacePoint()->equals($dimensionSpacePoint);
                        break;
                    case Event\NodeSpecializationWasCreated::class:
                        /** @var Event\NodeSpecializationWasCreated $event */
                        $dimensionSpacePointOccupied |= $event->getSpecializationLocation()->equals($dimensionSpacePoint);
                        break;
                    case Event\NodeGeneralizationWasCreated::class:
                        /** @var Event\NodeGeneralizationWasCreated $event */
                        $dimensionSpacePointOccupied |= $event->getGeneralizationLocation()->equals($dimensionSpacePoint);
                        break;
                    default:
                        continue;
                }
            }
        }

        return $dimensionSpacePointOccupied;
    }

    public function getIdentifier(): NodeAggregateIdentifier
    {
        return $this->identifier;
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    protected function getEventStream(): ?EventStream
    {
        try {
            return $this->eventStore->get(new StreamNameFilter($this->streamName));
        } catch (EventStreamNotFoundException $eventStreamNotFound) {
            return null;
        }
    }
}

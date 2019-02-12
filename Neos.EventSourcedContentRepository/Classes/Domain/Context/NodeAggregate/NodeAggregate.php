<?php
declare(strict_types=1);
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

use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException;
use Neos\EventSourcing\EventStore\StreamName;

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
    private $identifier;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var StreamName
     */
    private $streamName;

    public function __construct(NodeAggregateIdentifier $identifier, EventStore $eventStore, StreamName $streamName)
    {
        $this->identifier = $identifier;
        $this->eventStore = $eventStore;
        $this->streamName = $streamName;
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
            foreach ($eventStream as $eventEnvelope) {
                $event = $eventEnvelope->getDomainEvent();
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
                        continue 2;
                }
            }
        }

        return new DimensionSpacePointSet($occupiedDimensionSpacePoints);
    }

    public function getVisibleInDimensionSpacePoints(): DimensionSpacePointSet
    {
        $visibleInDimensionSpacePoints = [];

        $eventStream = $this->getEventStream();
        if ($eventStream) {
            foreach ($eventStream as $eventEnvelope) {
                $event = $eventEnvelope->getDomainEvent();
                switch (get_class($event)) {
                    case NodeAggregateWithNodeWasCreated::class:
                        /** @var NodeAggregateWithNodeWasCreated $event */
                        foreach ($event->getVisibleInDimensionSpacePoints()->getPoints() as $visibleDimensionSpacePoint) {
                            $visibleInDimensionSpacePoints[$visibleDimensionSpacePoint->getHash()] = $visibleDimensionSpacePoint;
                        }
                        break;
                    case Event\NodeSpecializationWasCreated::class:
                        /** @var Event\NodeSpecializationWasCreated $event */
                        foreach ($event->getSpecializationVisibility()->getPoints() as $visibleDimensionSpacePoint) {
                            $visibleInDimensionSpacePoints[$visibleDimensionSpacePoint->getHash()] = $visibleDimensionSpacePoint;
                        }
                        break;
                    case Event\NodeGeneralizationWasCreated::class:
                        /** @var Event\NodeGeneralizationWasCreated $event */
                        foreach ($event->getGeneralizationVisibility()->getPoints() as $visibleDimensionSpacePoint) {
                            $visibleInDimensionSpacePoints[$visibleDimensionSpacePoint->getHash()] = $visibleDimensionSpacePoint;
                        }
                        break;
                    default:
                        continue 2;
                }
            }
        }

        return new DimensionSpacePointSet($visibleInDimensionSpacePoints);
    }

    public function isDimensionSpacePointOccupied(DimensionSpacePoint $dimensionSpacePoint): bool
    {
        $dimensionSpacePointOccupied = false;
        $eventStream = $this->getEventStream();
        if ($eventStream) {
            foreach ($eventStream as $eventEnvelope) {
                $event = $eventEnvelope->getDomainEvent();
                switch (get_class($event)) {
                    case NodeAggregateWithNodeWasCreated::class:
                        /** @var NodeAggregateWithNodeWasCreated $event */
                        $dimensionSpacePointOccupied = $dimensionSpacePointOccupied || $event->getDimensionSpacePoint()->equals($dimensionSpacePoint);
                        break;
                    case Event\NodeSpecializationWasCreated::class:
                        /** @var Event\NodeSpecializationWasCreated $event */
                        $dimensionSpacePointOccupied = $dimensionSpacePointOccupied || $event->getSpecializationLocation()->equals($dimensionSpacePoint);
                        break;
                    case Event\NodeGeneralizationWasCreated::class:
                        /** @var Event\NodeGeneralizationWasCreated $event */
                        $dimensionSpacePointOccupied = $dimensionSpacePointOccupied || $event->getGeneralizationLocation()->equals($dimensionSpacePoint);
                        break;
                    default:
                        continue 2;
                }
            }
        }

        return $dimensionSpacePointOccupied;
    }

    public function getIdentifier(): NodeAggregateIdentifier
    {
        return $this->identifier;
    }

    public function getStreamName(): StreamName
    {
        return $this->streamName;
    }

    private function getEventStream(): ?EventStream
    {
        try {
            return $this->eventStore->load($this->streamName);
        } catch (EventStreamNotFoundException $eventStreamNotFound) {
            return null;
        }
    }
}

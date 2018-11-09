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

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeWasMoved;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeEventPublisher;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\EventInterface;
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
     * @var EventStream
     */
    protected $eventStream;

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

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param DimensionSpacePointSet $visibleDimensionSpacePoints
     * @param UserIdentifier $initiatingUserIdentifier
     * @param NodeName|null $nodeName
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function createRootWithNode(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePointSet $visibleDimensionSpacePoints,
        UserIdentifier $initiatingUserIdentifier,
        NodeName $nodeName = null
    ): void {
        if ($this->existsCurrently()) {
            throw new NodeAggregateCurrentlyExists('Root node aggregate "' . $this->identifier . '" does currently exist and can thus not be created.', 1541781941);
        }

        $this->nodeEventPublisher->publish(
            $this->getStreamName(),
            new RootNodeAggregateWithNodeWasCreated(
                $contentStreamIdentifier,
                $this->identifier,
                $nodeTypeName,
                $visibleDimensionSpacePoints,
                $initiatingUserIdentifier,
                $nodeName
            )
        );
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param DimensionSpacePoint $originDimensionSpacePoint
     * @param DimensionSpacePointSet $visibleDimensionSpacePoints
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $nodeName
     * @param PropertyValues $initialPropertyValues
     * @param NodeAggregateIdentifier|null $precedingNodeAggregateIdentifier
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @throw NodeAggregateCurrentlyExists
     */
    public function createWithNode(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $visibleDimensionSpacePoints,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $nodeName,
        PropertyValues $initialPropertyValues,
        NodeAggregateIdentifier $precedingNodeAggregateIdentifier = null
    ): void {
        if ($this->existsCurrently()) {
            throw new NodeAggregateCurrentlyExists('Node aggregate "' . $this->identifier . '" does currently exist and can thus not be created.', 1541679244);
        }

        $this->nodeEventPublisher->publish(
            $this->getStreamName(),
            new NodeAggregateWithNodeWasCreated(
                $contentStreamIdentifier,
                $this->identifier,
                $nodeTypeName,
                $originDimensionSpacePoint,
                $visibleDimensionSpacePoints,
                $parentNodeAggregateIdentifier,
                $nodeName,
                $initialPropertyValues,
                $precedingNodeAggregateIdentifier
            )
        );
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param DimensionSpacePoint $originDimensionSpacePoint
     * @param DimensionSpacePointSet $visibleDimensionSpacePoints
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $nodeName
     * @param PropertyValues $initialPropertyValues
     * @param NodeAggregateIdentifier|null $precedingNodeAggregateIdentifier
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @throw NodeAggregateCurrentlyExists
     */
    public function autoCreateWithNode(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $visibleDimensionSpacePoints,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $nodeName,
        PropertyValues $initialPropertyValues,
        NodeAggregateIdentifier $precedingNodeAggregateIdentifier = null
    ): void {
        if ($this->existsCurrently()) {
            throw new NodeAggregateCurrentlyExists('Node aggregate "' . $this->identifier . '" does currently exist and can thus not be created.', 1541755683);
        }

        $this->nodeEventPublisher->publish(
            $this->getStreamName(),
            new NodeAggregateWithNodeWasCreated(
                $contentStreamIdentifier,
                $this->identifier,
                $nodeTypeName,
                $originDimensionSpacePoint,
                $visibleDimensionSpacePoints,
                $parentNodeAggregateIdentifier,
                $nodeName,
                $initialPropertyValues,
                $precedingNodeAggregateIdentifier
            )
        );
    }

    public function existsCurrently(): bool
    {
        $existsCurrently = false;

        $this->traverseEventStream(function(EventInterface $event) use(&$existsCurrently) {
            switch (get_class($event)) {
                case NodeAggregateWithNodeWasCreated::class:
                    $existsCurrently = true;
                    break;
                // @todo handle NodeWasDeleted for toggling to false
                default:
                    continue;
            }
        });

        return $existsCurrently;
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

    public function getVisibleInDimensionSpacePoints(): DimensionSpacePointSet
    {
        $visibleInDimensionSpacePoints = [];

        $eventStream = $this->getEventStream();
        if ($eventStream) {
            foreach ($eventStream as $eventAndRawEvent) {
                $event = $eventAndRawEvent->getEvent();
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
                        continue;
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

    /**
     * @return array|NodeAggregateIdentifier[]
     */
    public function getParentIdentifiers(): array
    {
        $parentIdentifiers = [];
        $this->traverseEventStream(function(EventInterface $event) use(&$parentIdentifiers) {
            switch (get_class($event)) {
                case NodeAggregateWithNodeWasCreated::class:
                    /** @var NodeAggregateWithNodeWasCreated $event */
                    foreach ($event->getVisibleInDimensionSpacePoints() as $dimensionSpacePoint) {
                        $parentIdentifiers[(string) $dimensionSpacePoint] = $event->getParentNodeAggregateIdentifier();
                    }
                    break;
                case NodeWasMoved::class:
                    // @todo implement me
                default:
                    continue;
            }
        });

        return $parentIdentifiers;
    }

    public function getNodeTypeName(): ?NodeTypeName
    {
        $nodeTypeName = null;
        $this->traverseEventStream(function(EventInterface $event) use(&$nodeTypeName) {
            switch (get_class($event)) {
                case NodeAggregateWithNodeWasCreated::class:
                    /** @var NodeAggregateWithNodeWasCreated $event */
                    $nodeTypeName = $event->getNodeTypeName();
                    break;
                // @todo handle NodeAggregateTypeWasChanged
                // @todo handle NodeWasDeleted for nulling
                default:
                    continue;
            }
        });

        return $nodeTypeName;
    }

    public function getNodeName(): ?NodeName
    {
        $nodeName = null;
        $this->traverseEventStream(function(EventInterface $event) use(&$nodeName) {
            switch (get_class($event)) {
                case NodeAggregateWithNodeWasCreated::class:
                    /** @var NodeAggregateWithNodeWasCreated $event */
                    $nodeName = $event->getNodeName();
                    break;
                // @todo handle NodeAggregateNameWasChanged
                // @todo handle NodeWasDeleted for nulling
                default:
                    continue;
            }
        });

        return $nodeName;
    }

    public function getIdentifier(): NodeAggregateIdentifier
    {
        return $this->identifier;
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    protected function traverseEventStream(callable $callback): void
    {
        foreach ($this->getEventStream() as $eventAndRawEvent) {
            $event = $eventAndRawEvent->getEvent();
            $callback($event);
        }
    }

    protected function getEventStream(): EventStream
    {
        if (is_null($this->eventStream)) {
            try {
                $this->eventStream = $this->eventStore->get(new StreamNameFilter($this->streamName));
            } catch (EventStreamNotFoundException $eventStreamNotFound) {
                $this->eventStream = new EventStream(new \ArrayIterator());
            }
        }

        return $this->eventStream;
    }
}

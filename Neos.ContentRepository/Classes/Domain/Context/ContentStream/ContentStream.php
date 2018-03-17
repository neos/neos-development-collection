<?php
namespace Neos\ContentRepository\Domain\Context\ContentStream;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Node\NodeEventPublisher;
use Neos\ContentRepository\Domain\Context\NodeAggregate;
use Neos\EventSourcing\EventStore;

/**
 * A content stream to write events into
 *
 * Content streams contain an arbitrary amount of node aggregates that can be retrieved by identifier
 */
final class ContentStream
{
    /**
     * @var ContentStreamIdentifier
     */
    protected $identifier;

    /**
     * @var EventStore\EventStoreManager
     */
    protected $eventStoreManager;

    /**
     * @var EventStore\EventStore
     */
    protected $eventStore;

    /**
     * @var NodeEventPublisher
     */
    protected $nodeEventPublisher;

    /**
     * The node aggregate registry
     *
     * Serves as a means to preserve object identity.
     *
     * @var array|NodeAggregate\NodeAggregate[]
     */
    protected $nodeAggregates;


    public function __construct(ContentStreamIdentifier $identifier, EventStore\EventStoreManager $eventStoreManager, NodeEventPublisher $nodeEventPublisher)
    {
        $this->identifier = $identifier;
        $this->eventStoreManager = $eventStoreManager;
        $this->eventStore = $this->eventStoreManager->getEventStoreForStreamName($this->getStreamName());
        $this->nodeEventPublisher = $nodeEventPublisher;
    }


    public function getNodeAggregate(NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAggregate\NodeAggregate
    {
        if (!isset($this->nodeAggregates[(string)$nodeAggregateIdentifier])) {
            $nodeAggregateStreamName = $this->getStreamName() . ':NodeAggregate:' . $nodeAggregateIdentifier;
            $eventStore = $this->eventStoreManager->getEventStoreForStreamName($nodeAggregateStreamName);
            $this->nodeAggregates[(string)$nodeAggregateIdentifier] = new NodeAggregate\NodeAggregate($nodeAggregateIdentifier, $eventStore, $nodeAggregateStreamName, $this->nodeEventPublisher);
        }

        return $this->nodeAggregates[(string)$nodeAggregateIdentifier];
    }


    public function getStreamName(): string
    {
        return 'Neos.ContentRepository:ContentStream:' . $this->identifier;
    }

    public function getIdentifier(): ContentStreamIdentifier
    {
        return $this->identifier;
    }

    public function getVersion(): int
    {
        return $this->eventStore->getStreamVersion(new EventStore\StreamNameFilter($this->getStreamName()));
    }

    public function __toString(): string
    {
        return $this->identifier->__toString();
    }
}

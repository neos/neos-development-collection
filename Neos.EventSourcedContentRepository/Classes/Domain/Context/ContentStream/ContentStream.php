<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\ContentStream;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventStreamName;
use Neos\EventSourcing\EventStore;
use Neos\EventSourcing\EventStore\StreamName;

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
    private $identifier;

    /**
     * @var StreamName
     */
    private $streamName;

    /**
     * @var EventStore\EventStoreManager
     */
    private $eventStoreManager;

    /**
     * @var NodeEventPublisher
     */
    private $nodeEventPublisher;

    /**
     * @var EventStore\EventStore
     */
    private $eventStore;

    /**
     * The node aggregate registry
     *
     * Serves as a means to preserve object identity.
     *
     * @var array|NodeAggregate[]
     */
    protected $nodeAggregates;


    public function __construct(ContentStreamIdentifier $identifier, EventStore\EventStoreManager $eventStoreManager, NodeEventPublisher $nodeEventPublisher)
    {
        $this->identifier = $identifier;
        $this->streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($identifier)->getEventStreamName();
        $this->eventStoreManager = $eventStoreManager;
        $this->eventStore = $this->eventStoreManager->getEventStoreForStreamName($this->getStreamName());
        $this->nodeEventPublisher = $nodeEventPublisher;
    }

    public function getNodeAggregate(NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAggregate
    {
        if (!isset($this->nodeAggregates[(string)$nodeAggregateIdentifier])) {
            $nodeAggregateEventStreamName = NodeAggregateEventStreamName::fromContentStreamIdentifierAndNodeAggregateIdentifier($this->identifier, $nodeAggregateIdentifier)->getEventStreamName();
            $eventStore = $this->eventStoreManager->getEventStoreForStreamName($nodeAggregateEventStreamName);
            $this->nodeAggregates[(string)$nodeAggregateIdentifier] = new NodeAggregate($nodeAggregateIdentifier, $nodeAggregateEventStreamName, $eventStore, $this->nodeEventPublisher);
        }

        return $this->nodeAggregates[(string)$nodeAggregateIdentifier];
    }

    public function getStreamName(): StreamName
    {
        return $this->streamName;
    }

    public function getIdentifier(): ContentStreamIdentifier
    {
        return $this->identifier;
    }

    public function getVersion(): int
    {
        // TODO hack!! The new Event Store does not have a getStreamVersion() method any longer - we should probably use the reconstitution version from an aggregate instead
        return count(iterator_to_array($this->eventStore->load($this->streamName))) - 1;
    }

    public function __toString(): string
    {
        return $this->identifier->__toString();
    }
}

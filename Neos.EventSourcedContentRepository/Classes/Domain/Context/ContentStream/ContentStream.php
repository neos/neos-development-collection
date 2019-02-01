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

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregate;
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


    public function __construct(ContentStreamIdentifier $identifier, EventStore\EventStoreManager $eventStoreManager)
    {
        $this->identifier = $identifier;
        $this->streamName = StreamName::fromString('Neos.ContentRepository:ContentStream:' . $this->identifier);
        $this->eventStoreManager = $eventStoreManager;
        $this->eventStore = $this->eventStoreManager->getEventStoreForStreamName($this->getStreamName());
    }


    public function getNodeAggregate(NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAggregate
    {
        if (!isset($this->nodeAggregates[(string)$nodeAggregateIdentifier])) {
            $nodeAggregateStreamName = StreamName::fromString($this->getStreamName() . ':NodeAggregate:' . $nodeAggregateIdentifier);
            $eventStore = $this->eventStoreManager->getEventStoreForStreamName($nodeAggregateStreamName);
            $this->nodeAggregates[(string)$nodeAggregateIdentifier] = new NodeAggregate($nodeAggregateIdentifier, $eventStore, $nodeAggregateStreamName);
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

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
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException;
use Neos\EventSourcing\EventStore\StreamNameFilter;

/**
 * A content stream to write events into
 *
 * Content streams contain an arbitrary amount of node aggregates that can be retrieved by identifier
 */
final class ContentStreamRepository
{
    /**
     * @var EventStoreManager
     */
    protected $eventStoreManager;

    /**
     * @var NodeEventPublisher
     */
    protected $nodeEventPublisher;

    /**
     * The content stream registry
     *
     * Serves as a means to preserve object identity.
     *
     * @var array|ContentStream[]
     */
    protected $contentStreams;


    public function __construct(EventStoreManager $eventStoreManager, NodeEventPublisher $nodeEventPublisher)
    {
        $this->eventStoreManager = $eventStoreManager;
        $this->nodeEventPublisher = $nodeEventPublisher;
    }


    public function findContentStream(ContentStreamIdentifier $contentStreamIdentifier): ?ContentStream
    {
        if (!isset($this->contentStreams[(string)$contentStreamIdentifier])) {
            $eventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);
            $eventStore = $this->eventStoreManager->getEventStoreForStreamName($eventStreamName);
            try {
                $eventStream = $eventStore->get(new StreamNameFilter($eventStreamName));
                $eventStream->rewind();
                if (!$eventStream->current()) {
                    // a content stream without events in its event stream does not exist yet
                    return null;
                }
            } catch (EventStreamNotFoundException $eventStreamNotFound) {
                return null;
            }

            $this->contentStreams[(string)$contentStreamIdentifier] = new ContentStream($contentStreamIdentifier, $this->eventStoreManager, $this->nodeEventPublisher);
        }

        return $this->contentStreams[(string)$contentStreamIdentifier];
    }
}

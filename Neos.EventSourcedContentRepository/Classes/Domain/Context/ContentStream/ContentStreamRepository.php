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
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException;

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
    private $eventStoreManager;

    /**
     * The content stream registry
     *
     * Serves as a means to preserve object identity.
     *
     * @var array|ContentStream[]
     */
    private $contentStreams;


    public function __construct(EventStoreManager $eventStoreManager)
    {
        $this->eventStoreManager = $eventStoreManager;
    }


    public function findContentStream(ContentStreamIdentifier $contentStreamIdentifier): ?ContentStream
    {
        if (!isset($this->contentStreams[(string)$contentStreamIdentifier])) {
            $eventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName();
            $eventStore = $this->eventStoreManager->getEventStoreForStreamName($eventStreamName);
            try {
                $eventStream = $eventStore->load($eventStreamName);
                $eventStream->rewind();
                if (!$eventStream->current()) {
                    // a content stream without events in its event stream does not exist yet
                    return null;
                }
            } catch (EventStreamNotFoundException $eventStreamNotFound) {
                return null;
            }

            $this->contentStreams[(string)$contentStreamIdentifier] = new ContentStream($contentStreamIdentifier, $this->eventStoreManager);
        }

        return $this->contentStreams[(string)$contentStreamIdentifier];
    }
}

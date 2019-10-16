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
use Neos\EventSourcing\EventStore\EventStore;

/**
 * A content stream to write events into
 *
 * Content streams contain an arbitrary amount of node aggregates that can be retrieved by identifier
 */
final class ContentStreamRepository
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * The content stream registry
     *
     * Serves as a means to preserve object identity.
     *
     * @var array|ContentStream[]
     */
    private $contentStreams;


    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }


    public function findContentStream(ContentStreamIdentifier $contentStreamIdentifier): ?ContentStream
    {
        if (!isset($this->contentStreams[(string)$contentStreamIdentifier])) {
            $eventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName();
            try {
                $eventStream = $this->eventStore->load($eventStreamName);
                $eventStream->rewind();
                if (!$eventStream->current()) {
                    // a content stream without events in its event stream does not exist yet
                    return null;
                }
            } catch (EventStreamNotFoundException $eventStreamNotFound) {
                return null;
            }

            $this->contentStreams[(string)$contentStreamIdentifier] = new ContentStream($contentStreamIdentifier, $this->eventStore);
        }

        return $this->contentStreams[(string)$contentStreamIdentifier];
    }
}

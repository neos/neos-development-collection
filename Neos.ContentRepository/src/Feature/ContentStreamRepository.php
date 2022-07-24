<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature;

use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;

/**
 * A content stream to write events into
 *
 * Content streams contain an arbitrary amount of node aggregates that can be retrieved by identifier
 *
 * @Flow\Scope("singleton")
 */
final class ContentStreamRepository
{
    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * The content stream registry
     *
     * Serves as a means to preserve object identity.
     *
     * NOTE: This must be PROTECTED; so that we can reset it from within the testcases.
     *
     * @var array|ContentStream[]
     */
    protected $contentStreams;


    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
    }


    public function findContentStream(ContentStreamIdentifier $contentStreamIdentifier): ?ContentStream
    {
        if (!isset($this->contentStreams[(string)$contentStreamIdentifier])) {
            $eventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)
                ->getEventStreamName();
            $eventStream = $this->eventStore->load($eventStreamName);
            $eventStream->rewind();
            if (!$eventStream->valid()) {
                // a content stream without events in its event stream does not exist yet
                return null;
            }

            $this->contentStreams[(string)$contentStreamIdentifier]
                = new ContentStream($contentStreamIdentifier, $this->eventStore);
        }

        return $this->contentStreams[(string)$contentStreamIdentifier];
    }
}

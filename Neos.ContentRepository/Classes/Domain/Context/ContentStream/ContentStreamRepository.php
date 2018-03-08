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


    public function getContentStream(ContentStreamIdentifier $contentStreamIdentifier): ContentStream
    {
        if (!isset($this->contentStreams[(string)$contentStreamIdentifier])) {
            $this->contentStreams[(string)$contentStreamIdentifier] = new ContentStream($contentStreamIdentifier, $this->eventStoreManager, $this->nodeEventPublisher);
        }

        return $this->contentStreams[(string)$contentStreamIdentifier];
    }
}

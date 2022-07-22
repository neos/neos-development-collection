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

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\EventSourcing\EventStore\EventStore;
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
     * @var EventStore
     */
    private $eventStore;


    public function __construct(ContentStreamIdentifier $identifier, EventStore $eventStore)
    {
        $this->identifier = $identifier;
        $this->streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($identifier)
            ->getEventStreamName();
        $this->eventStore = $eventStore;
    }

    public function getVersion(): int
    {
        // TODO !!! PLEASE CHANGE THIS!!!
        // TODO hack!! The new Event Store does not have a getStreamVersion() method any longer
        // - we should probably use the reconstitution version from an aggregate instead
        return count(iterator_to_array($this->eventStore->load($this->streamName))) - 1;
    }

    public function __toString(): string
    {
        return $this->identifier->__toString();
    }
}

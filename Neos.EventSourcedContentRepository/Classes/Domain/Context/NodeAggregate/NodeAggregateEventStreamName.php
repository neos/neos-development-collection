<?php
declare(strict_types=1);
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

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcing\EventStore\StreamName;

/**
 * A node aggregate's event stream name
 */
final class NodeAggregateEventStreamName
{
    /**
     * @var string
     */
    protected $eventStreamName;

    protected function __construct(string $eventStreamName)
    {
        $this->eventStreamName = $eventStreamName;
    }

    public static function fromContentStreamIdentifierAndNodeAggregateIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): self {
        $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);

        return new NodeAggregateEventStreamName($contentStreamEventStreamName . ':NodeAggregate:' . $nodeAggregateIdentifier);
    }

    public function getEventStreamName(): StreamName
    {
        return StreamName::fromString($this->eventStreamName);
    }

    public function __toString(): string
    {
        return $this->eventStreamName;
    }
}

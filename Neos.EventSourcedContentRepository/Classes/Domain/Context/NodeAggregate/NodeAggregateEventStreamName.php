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

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;

/**
 * A node aggregate's event stream name
 */
final class NodeAggregateEventStreamName
{
    /**
     * @var string
     */
    protected $eventStreamName;

    public function __construct(string $eventStreamName)
    {
        $this->eventStreamName = $eventStreamName;
    }

    public static function fromContentStreamIdentifierAndNodeAggregateIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): NodeAggregateEventStreamName {
        $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);

        return new NodeAggregateEventStreamName($contentStreamEventStreamName . ':NodeAggregate:' . $nodeAggregateIdentifier);
    }

    public function getEventStreamName(): string
    {
        return $this->eventStreamName;
    }

    public function __toString(): string
    {
        return $this->eventStreamName;
    }
}

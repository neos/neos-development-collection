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

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\StreamName;

/**
 * A content stream's event stream name
 *
 * @internal
 */
final class ContentStreamEventStreamName
{
    /**
     * @var string
     */
    protected $eventStreamName;

    public const EVENT_STREAM_NAME_PREFIX = 'ContentStream:';

    protected function __construct(string $eventStreamName)
    {
        $this->eventStreamName = $eventStreamName;
    }

    public static function fromContentStreamId(ContentStreamId $contentStreamId): self
    {
        return new ContentStreamEventStreamName(self::EVENT_STREAM_NAME_PREFIX . $contentStreamId);
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

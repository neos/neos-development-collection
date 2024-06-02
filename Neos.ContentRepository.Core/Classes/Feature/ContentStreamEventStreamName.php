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

use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\StreamName;

/**
 * A content stream's event stream name
 *
 * @internal
 */
final readonly class ContentStreamEventStreamName
{
    private const EVENT_STREAM_NAME_PREFIX = 'ContentStream:';

    private function __construct(
        public string $value
    ) {
    }

    public static function fromContentStreamId(ContentStreamId $contentStreamId): self
    {
        return new self(self::EVENT_STREAM_NAME_PREFIX . $contentStreamId->value);
    }

    public static function isContentStreamStreamName(StreamName $streamName): bool
    {
        return str_starts_with($streamName->value, self::EVENT_STREAM_NAME_PREFIX);
    }

    /** @todo rather use {@see EmbedsContentStreamId} instead!? */
    public static function extractContentStreamIdFromStreamName(StreamName $streamName): ContentStreamId
    {
        if (!self::isContentStreamStreamName($streamName)) {
            throw new \InvalidArgumentException(sprintf('Failed to extract content stream id from stream name "%s"', $streamName->value), 1716640692);
        }
        return ContentStreamId::fromString(substr($streamName->value, strlen(self::EVENT_STREAM_NAME_PREFIX)));
    }

    public function getEventStreamName(): StreamName
    {
        return StreamName::fromString($this->value);
    }
}

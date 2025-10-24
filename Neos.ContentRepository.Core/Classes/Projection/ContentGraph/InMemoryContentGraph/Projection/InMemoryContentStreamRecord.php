<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\Version;

/**
 * The active record for reading and writing content streams in-memory
 *
 * @internal
 */
final class InMemoryContentStreamRecord
{
    public function __construct(
        public readonly ContentStreamId $id,
        public Version $version,
        public ?ContentStreamId $sourceContentStreamId = null,
        public ?Version $sourceVersion = null,
        public bool $isClosed = false,
        public bool $hasChanges = false,
    ) {
    }
}

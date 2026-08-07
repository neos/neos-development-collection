<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment;

use Neos\ContentRepository\Core\EventStore\Events;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * TODO we only ever hardcode $expectedVersion to ExpectedVersion::ANY()
 * We should fully refactor the structure adjustments to only emit the events and the outer wrapping should retrieve the expected version for the live content stream.
 * see https://github.com/neos/neos-development-collection/issues/5058
 *
 * @internal publishing events outside the core is forbidden see @link https://github.com/neos/neos-development-collection/issues/4451
 */
final readonly class EventsForFix
{
    public function __construct(
        public StreamName $streamName,
        public Events $events,
        public ExpectedVersion $expectedVersion,
    ) {
    }
}

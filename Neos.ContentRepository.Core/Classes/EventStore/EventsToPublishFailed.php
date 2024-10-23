<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\EventStore;

use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal
 */
final readonly class EventsToPublishFailed
{
    public function __construct(
        public ExpectedVersion $expectedVersion,
        // FIXME add public ExpectedVersion $actualVersion, field probably to ConcurrencyException
        public ConcurrencyException $exception
    ) {
    }
}

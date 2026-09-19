<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\Shared;

use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\CausationId;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Event\EventData;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @internal CR upgrade internals
 */
final class EventEnvelopeFactory
{
    private function __construct()
    {
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function createFromArray(array $row): EventEnvelope
    {
        $recordedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['recordedat'], new \DateTimeZone('UTC'));
        if ($recordedAt === false) {
            throw new \RuntimeException(sprintf('Failed to parse "recordetat" value of "%s" in event "%s"', $row['recordedat'], $row['id']), 1651744355);
        }
        return new EventEnvelope(
            new Event(
                EventId::fromString($row['id']),
                EventType::fromString($row['type']),
                EventData::fromString($row['payload']),
                isset($row['metadata']) ? EventMetadata::fromJson($row['metadata']) : null,
                isset($row['causationid']) ? CausationId::fromString($row['causationid']) : null,
                isset($row['correlationid']) ? CorrelationId::fromString($row['correlationid']) : null,
            ),
            StreamName::fromString($row['stream']),
            Version::fromInteger((int)$row['version']),
            SequenceNumber::fromInteger((int)$row['sequencenumber']),
            $recordedAt
        );
    }
}

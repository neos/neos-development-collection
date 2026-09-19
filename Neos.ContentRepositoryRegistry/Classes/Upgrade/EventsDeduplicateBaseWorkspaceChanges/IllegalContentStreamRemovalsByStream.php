<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsDeduplicateBaseWorkspaceChanges;

use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\StreamName;

final readonly class IllegalContentStreamRemovalsByStream
{
    /**
     * @param list<SequenceNumber> $sequenceNumbers
     * @param list<CorrelationId> $correlationIds
     */
    public function __construct(
        public StreamName $stream,
        public array $sequenceNumbers,
        public SequenceNumber $lowestSequenceNumber,
        public SequenceNumber $highestSequenceNumber,
        public array $correlationIds,
        public int $removals
    ) {
        if (!ContentStreamEventStreamName::isContentStreamStreamName($this->stream)) {
            throw new \InvalidArgumentException(sprintf('Error found illegal content stream removal event on non content stream %s', $this->stream->value));
        }
    }

    /** @param array<string,string> $row */
    public static function fromRow(array $row): self
    {
        // We dont write "," into correlation ids
        /** @var list<CorrelationId> $correlationIds */
        $correlationIds = array_map(CorrelationId::fromString(...), explode(',', $row['correlationIds']));

        $sequenceNumbers = array_map(intval(...), explode(',', $row['sequenceNumbers']));
        $lowestSequenceNumber = SequenceNumber::fromInteger(min($sequenceNumbers));
        $highestSequenceNumber = SequenceNumber::fromInteger(max($sequenceNumbers));

        return new self(
            stream: StreamName::fromString($row['stream']),
            sequenceNumbers: array_map(SequenceNumber::fromInteger(...), $sequenceNumbers),
            lowestSequenceNumber: $lowestSequenceNumber,
            highestSequenceNumber: $highestSequenceNumber,
            correlationIds: $correlationIds,
            removals: (int)$row['removals'],
        );
    }

    public function toDebugString(): string
    {
        return join("\n    ", [
            '-',
            sprintf('stream: %s', $this->stream->value),
            sprintf('sequenceNumbers: %s', join(', ', array_column($this->sequenceNumbers, 'value'))),
            sprintf('correlationIds: %s', join(', ', array_column($this->correlationIds, 'value'))),
            sprintf('removals: %d', $this->removals)
        ]);
    }
}

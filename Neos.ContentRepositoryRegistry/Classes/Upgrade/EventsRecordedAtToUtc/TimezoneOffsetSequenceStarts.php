<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsRecordedAtToUtc;

use Neos\EventStore\Model\Event\SequenceNumber;

final readonly class TimezoneOffsetSequenceStarts implements \JsonSerializable
{
    public function __construct(
        public SequenceNumber $sequenceNumber,
        public string $tzOffset,
    ) {
    }

    /** @param array<int|string,mixed> $array */
    public static function fromArray(array $array): self
    {
        return new self(
            sequenceNumber: SequenceNumber::fromInteger((int)$array['sequenceNumber']),
            tzOffset: $array['tzoffset'],
        );
    }

    /**
     * @param non-empty-list<self> $sequences
     */
    public static function isOnlyUtc(array $sequences): bool
    {
        return count($sequences) === 1 && $sequences[0]->tzOffset === '+00:00';
    }

    /**
     * @param non-empty-list<self> $sequences
     * @return list<string>
     */
    public static function uniqueOffsets(array $sequences): array
    {
        return array_unique(array_column($sequences, 'tzOffset'));
    }

    /** @return array<int|string,mixed> */
    public function jsonSerialize(): mixed
    {
        return [
            'sequenceNumber' => $this->sequenceNumber->value,
            'tzoffset' => $this->tzOffset,
        ];
    }
}

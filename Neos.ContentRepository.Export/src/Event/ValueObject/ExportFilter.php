<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Event\ValueObject;

use Neos\EventSourcing\EventStore\StreamName;

final class ExportFilter
{

    private function __construct(
        public readonly StreamName $streamName,
        public readonly int $minimumSequenceNumber
    ) {}

    public static function default(): self
    {
        return new self(StreamName::all(), 0);
    }

    public function withStreamName(StreamName $streamName): self
    {
        return new self($streamName, $this->minimumSequenceNumber);
    }

    public function withMinimumSequenceNumber(int $minimumSequenceNumber): self
    {
        return new self($this->streamName, $minimumSequenceNumber);
    }

}

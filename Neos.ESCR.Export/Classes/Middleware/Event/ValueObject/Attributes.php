<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Event\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class Attributes
{

    private function __construct(
        public readonly bool $metadata,
        public readonly bool $streamName,
        public readonly bool $version,
        public readonly bool $sequenceNumber,
        public readonly bool $recordedAt
    ) {}

    public static function create(): self
    {
        return new self(false, false, false, false, false);
    }


    public static function default(): self
    {
        return new self(true, true, false, false, true);
    }

    public function withMetadata(): self
    {
        return new self(true, $this->streamName, $this->version, $this->sequenceNumber, $this->recordedAt);
    }

    public function withoutMetadata(): self
    {
        return new self(false, $this->streamName, $this->version, $this->sequenceNumber, $this->recordedAt);
    }

    public function withStreamName(): self
    {
        return new self($this->metadata, true, $this->version, $this->sequenceNumber, $this->recordedAt);
    }

    public function withoutStreamName(): self
    {
        return new self($this->metadata, false, $this->version, $this->sequenceNumber, $this->recordedAt);
    }

    public function withVersion(): self
    {
        return new self($this->metadata, $this->streamName, true, $this->sequenceNumber, $this->recordedAt);
    }

    public function withoutVersion(): self
    {
        return new self($this->metadata, $this->streamName, false, $this->sequenceNumber, $this->recordedAt);
    }

    public function withSequenceNumber(): self
    {
        return new self($this->metadata, $this->streamName, $this->version, true, $this->recordedAt);
    }

    public function withoutSequenceNumber(): self
    {
        return new self($this->metadata, $this->streamName, $this->version, false, $this->recordedAt);
    }

    public function withRecordedAt(): self
    {
        return new self($this->metadata, $this->streamName, $this->version, $this->sequenceNumber, true);
    }

    public function withoutRecordedAt(): self
    {
        return new self($this->metadata, $this->streamName, $this->version, $this->sequenceNumber, false);
    }

}

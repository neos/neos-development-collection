<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Event\ValueObject;

use DateTimeInterface;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class ExportedEvent implements \JsonSerializable
{
    /**
     * @param array<mixed> $payload
     * @param array<mixed> $metadata
     */
    private function __construct(
        public readonly string $identifier,
        public readonly string $type,
        public readonly array $payload,
        public readonly array $metadata,
        public readonly ?StreamName $streamName,
        public readonly ?int $version,
        public readonly ?int $sequenceNumber,
        public readonly ?DateTimeInterface $recordedAt,
    ) {}

    public static function fromRawEvent(RawEvent $event, Attributes $attributes): self
    {
        return new self(
            $event->getIdentifier(),
            $event->getType(),
            $event->getPayload(),
            $attributes->metadata ? $event->getMetadata() : [],
            $attributes->streamName ? $event->getStreamName() : null,
            $attributes->version ? $event->getVersion() : null,
            $attributes->sequenceNumber ? $event->getSequenceNumber() : null,
            $attributes->recordedAt ? $event->getRecordedAt() : null,
        );
    }

    public static function fromJson(string $json, Attributes $attributes): self
    {
        try {
            ///** @var array{identifier: string, type: string, payload: array<mixed>, metadata?: ?array<mixed>, streamName?: string, version?: int, sequenceNumber?: int, recordedAt?: string} $data */
            /** @var array<mixed> $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(sprintf('Failed to decode JSON "%s": %s', $json, $e->getMessage()), 1638432979, $e);
        }
        assert(isset($data['identifier']) && is_string($data['identifier']));
        assert(isset($data['type']) && is_string($data['type']));
        assert(isset($data['payload']) && is_array($data['payload']));
        $metadata = [];
        $streamName = null;
        $version = null;
        $sequenceNumber = null;
        $recordedAt = null;
        if ($attributes->metadata && isset($data['metadata'])) {
            assert(is_array($data['metadata']));
            $metadata = $data['metadata'];
        }
        if ($attributes->streamName && isset($data['streamName'])) {
            assert(is_string($data['streamName']));
            $streamName = self::parseStreamName($data['streamName']);
        }
        if ($attributes->version && isset($data['version'])) {
            assert(is_int($data['version']));
            $version = $data['version'];
        }
        if ($attributes->sequenceNumber && isset($data['sequenceNumber'])) {
            assert(is_int($data['sequenceNumber']));
            $sequenceNumber = $data['sequenceNumber'];
        }
        if ($attributes->recordedAt && isset($data['recordedAt'])) {
            assert(is_string($data['recordedAt']));
            $recordedAt = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $data['recordedAt']);
            if ($recordedAt === false) {
                throw new \InvalidArgumentException(sprintf('Failed to convert "%s" to a DateTime object', $data['recordedAt']), 1646328138);
            }
        }
        return new self(
            $data['identifier'],
            $data['type'],
            $data['payload'],
            $metadata,
            $streamName,
            $version,
            $sequenceNumber,
            $recordedAt,
        );
    }

    public function withIdentifier(string $identifier): self
    {
        return new self($identifier, $this->type, $this->payload, $this->metadata, $this->streamName, $this->version, $this->sequenceNumber, $this->recordedAt);
    }

    /**
     * @param \Closure(array<mixed>): array<mixed> $processor
     * @return $this
     */
    public function processPayload(\Closure $processor): self
    {
        return new self($this->identifier, $this->type, $processor($this->payload), $this->metadata, $this->streamName, $this->version, $this->sequenceNumber, $this->recordedAt);
    }

    /**
     * @param \Closure(array<mixed>): array<mixed> $processor
     * @return $this
     */
    public function processMetadata(\Closure $processor): self
    {
        return new self($this->identifier, $this->type, $this->payload, $processor($this->metadata), $this->streamName, $this->version, $this->sequenceNumber, $this->recordedAt);
    }

    public function toJson(): string
    {
        try {
            return json_encode($this, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to encode exported event to JSON: %s', $e->getMessage()), 1638432972, $e);
        }
    }

    /**
     * @return array{identifier: string, type: string, payload: array<mixed>, metadata?: ?array<mixed>, streamName?: string, version?: int, sequenceNumber?: int, recordedAt?: string}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'identifier' => $this->identifier,
            'type' => $this->type,
            'payload' => $this->payload,
        ];
        if ($this->metadata !== []) {
            $data['metadata'] = $this->metadata;
        }
        if ($this->streamName !== null) {
            $data['streamName'] = (string)$this->streamName;
        }
        if ($this->version !== null) {
            $data['version'] = $this->version;
        }
        if ($this->sequenceNumber !== null) {
            $data['sequenceNumber'] = $this->sequenceNumber;
        }
        if ($this->recordedAt !== null) {
            $data['recordedAt'] = $this->recordedAt->format(\DateTimeInterface::ATOM);
        }
        return $data;
    }

    // ------------------------------

    private static function parseStreamName(string $string): StreamName
    {
        if ($string === '$all') {
            return StreamName::all();
        }
        $virtualStreamMatch = preg_match('/\$([a-z]+)-([a-z-]+)/i', $string, $matches);
        if ($virtualStreamMatch === 0) {
            return StreamName::fromString($string);
        }
        return match($matches[1]) {
            'ce' => StreamName::forCategory($matches[2]),
            'correlation' => StreamName::forCorrelationId($matches[2]),
            default => throw new \RuntimeException(sprintf('Failed to parse stream name prefix "%s"', $matches[1]), 1646401922),
        };
    }
}

<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Event\ValueObject;

use Neos\EventStore\Model\Event;

final class ExportedEvent implements \JsonSerializable
{
    /**
     * @param array<mixed> $payload
     * @param array<mixed> $metadata
     */
    public function __construct(
        public readonly string $identifier,
        public readonly string $type,
        public readonly array $payload, // TODO: string
        public readonly array $metadata,
    ) {}

    public static function fromRawEvent(Event $event): self
    {
        return new self(
            $event->id->value,
            $event->type->value,
            \json_decode($event->data->value, true),
            $event->metadata->value,
        );
    }

    public static function fromJson(string $json): self
    {
        try {
            ///** @var array{identifier: string, type: string, payload: array<mixed>, metadata: array<mixed>} $data */
            /** @var array<mixed> $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(sprintf('Failed to decode JSON "%s": %s', $json, $e->getMessage()), 1638432979, $e);
        }
        assert(isset($data['identifier']) && is_string($data['identifier']));
        assert(isset($data['type']) && is_string($data['type']));
        assert(isset($data['payload']) && is_array($data['payload']));
        assert(isset($data['metadata']) && is_array($data['metadata']));
        return new self(
            $data['identifier'],
            $data['type'],
            $data['payload'],
            $data['metadata'],
        );
    }

    public function withIdentifier(string $identifier): self
    {
        return new self($identifier, $this->type, $this->payload, $this->metadata);
    }

    /**
     * @param \Closure(array<mixed>): array<mixed> $processor
     * @return $this
     */
    public function processPayload(\Closure $processor): self
    {
        return new self($this->identifier, $this->type, $processor($this->payload), $this->metadata);
    }

    /**
     * @param \Closure(array<mixed>): array<mixed> $processor
     * @return $this
     */
    public function processMetadata(\Closure $processor): self
    {
        return new self($this->identifier, $this->type, $this->payload, $processor($this->metadata));
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
        return get_object_vars($this);
    }
}

<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Event\ValueObject;

use Neos\EventStore\Model\Event;

final class ExportedEvent implements \JsonSerializable
{
    /**
     * @param array<mixed> $metadata
     */
    public function __construct(
        public readonly string $identifier,
        public readonly string $type,
        public readonly string $payload,
        public readonly array $metadata,
    ) {
    }

    public static function fromRawEvent(Event $event): self
    {
        return new self(
            $event->id->value,
            $event->type->value,
            $event->data->value,
            $event->metadata?->value ?? [],
        );
    }

    public static function fromJson(string $json): self
    {
        try {
            /** @var array{identifier: string, type: string, payload: string, metadata: array<mixed>} $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(sprintf('Failed to decode JSON "%s": %s', $json, $e->getMessage()), 1638432979, $e);
        }
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
    public function processPayloadAsArray(\Closure $processor): self
    {
        $payloadArray = \json_decode($this->payload, true, 512, JSON_THROW_ON_ERROR);
        $newPayloadString = json_encode($processor($payloadArray), JSON_THROW_ON_ERROR);
        return new self($this->identifier, $this->type, $newPayloadString, $this->metadata);
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

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}

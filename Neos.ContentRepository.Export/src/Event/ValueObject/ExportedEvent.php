<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Event\ValueObject;

use Neos\EventStore\Model\Event;

final readonly class ExportedEvent implements \JsonSerializable
{
    /**
     * The Neos.ContentRepository.Core's domain events require the payload to be a json array string
     * {@see \Neos\ContentRepository\Core\EventStore\EventInterface}
     * This exporter will enforce this as well (by using array as payload type).
     *
     * @param array<mixed> $payload
     * @param array<mixed> $metadata
     */
    public function __construct(
        public string $identifier,
        public string $type,
        public array $payload,
        public array $metadata,
    ) {
    }

    public static function fromRawEvent(Event $event): self
    {
        return new self(
            $event->id->value,
            $event->type->value,
            \json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR),
            $event->metadata?->value ?? [],
        );
    }

    public static function fromJsonString(string $jsonString): self
    {
        try {
            /** @var array{identifier: string, type: string, payload: array<mixed>, metadata: array<mixed>} $data */
            $data = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(sprintf('Failed to JSON-decode "%s" for %s instance: %s', $jsonString, self::class, $e->getMessage()), 1716574888, $e);
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
            throw new \RuntimeException(sprintf('Failed to JSON-encode instance of %s: %s', self::class, $e->getMessage()), 1638432972, $e);
        }
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}

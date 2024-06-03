<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Event\ValueObject;

final readonly class EventStoreIdentifier
{

    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}

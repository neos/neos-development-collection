<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Event\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class EventStoreIdentifier
{

    private function __construct(
        public readonly string $value,
    ) {}

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}

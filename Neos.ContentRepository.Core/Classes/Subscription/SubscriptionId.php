<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

/**
 * @api identifier for a registered subscription
 */
final class SubscriptionId
{
    public const MAX_LENGTH = 250;

    private function __construct(public readonly string $value)
    {
        if (preg_match('/^[a-zA-Z0-9-_.:]{1,' . self::MAX_LENGTH . '}$/', $value) !== 1) {
            throw new \InvalidArgumentException(sprintf('The value "%s" is not a valid subscription id.', $value), 1729679513);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }
}

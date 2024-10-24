<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription;

/**
 * @internal
 */
final class SubscriptionGroup
{
    private function __construct(public readonly string $value)
    {
        if (preg_match('/^[a-z0-9-]{1,50}$/', $value) !== 1) {
            throw new \InvalidArgumentException(sprintf('The value "%s" is not a valid subscription group name.', $value), 1729679285);
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

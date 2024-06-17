<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * Globally unique identifier of a Neos user
 *
 * @api
 */
final readonly class UserId implements \JsonSerializable
{
    public function __construct(
        public string $value
    ) {
        if (!preg_match('/^([a-z0-9\-]{1,40})$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Invalid user id "%s" (a user id must only contain lowercase characters, numbers and the "-" sign).', 1718293224));
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

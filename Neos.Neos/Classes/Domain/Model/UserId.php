<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * Globally unique identifier of a Neos user
 *
 * @api
 */
final readonly class UserId implements ProvidesSchema, \JsonSerializable
{
    public function __construct(
        public string $value
    ) {
        $validationResult = self::schema()->validate($value);
        if (!$validationResult->valid) {
            throw new \InvalidArgumentException(sprintf('Invalid user id "%s": %s', $value, $validationResult->issues), 1718293224);
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

    public static function schema(): StringSchema
    {
        static $schema = null;
        return $schema ??= StringSchema::create(
            description: 'Globally unique identifier of a Neos user',
            examples: ['a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d'],
            minLength: 1,
            maxLength: 40,
            pattern: '^[a-z0-9\-]+$',
        );
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

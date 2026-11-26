<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * Human-Readable title of a workspace
 *
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceTitle implements ProvidesSchema, \JsonSerializable
{
    public function __construct(
        public string $value
    ) {
        $validationResult = self::schema()->validate($this->value);
        if (!$validationResult->valid) {
            throw new \InvalidArgumentException(sprintf('Invalid workspace title "%s": %s', $this->value, $validationResult->issues), 1791889845);
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
            description: 'Human-readable title of a workspace',
            examples: ['Live', 'John Doe\'s workspace'],
            minLength: 1,
            maxLength: 200,
            pattern: '^[\p{L}\p{P}\d .]*$',
        );
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

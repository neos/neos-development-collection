<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * Description for a workspace
 *
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceDescription implements ProvidesSchema, \JsonSerializable
{
    public function __construct(
        public string $value,
    ) {
        $validationResult = self::schema()->validate($this->value);
        if (!$validationResult->valid) {
            throw new \InvalidArgumentException(sprintf('Invalid workspace description "%s": %s', $this->value, $validationResult->issues), 1791889846);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function createEmpty(): self
    {
        return new self('');
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function schema(): StringSchema
    {
        static $schema = null;
        return $schema ??= StringSchema::create(
            description: 'Description of a workspace',
            examples: ['', 'Workspace for the summer campaign.'],
            maxLength: 500,
            pattern: '^[\p{L}\p{P}\d .]*$',
        );
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

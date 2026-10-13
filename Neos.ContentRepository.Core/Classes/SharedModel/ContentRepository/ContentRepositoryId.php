<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\ContentRepository;

use InvalidArgumentException;
use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * Identifies a given content repository
 *
 * @api
 */
final readonly class ContentRepositoryId implements ProvidesSchema, \JsonSerializable
{
    private function __construct(
        public string $value
    ) {
    }

    public static function fromString(string $value): self
    {
        $validationResult = self::schema()->validate($value);
        if (!$validationResult->valid) {
            throw new InvalidArgumentException(sprintf('Content Repository Identifier "%s": %s', $value, $validationResult->issues), 1791889842);
        }
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    public static function schema(): StringSchema
    {
        static $schema = null;
        return $schema ??= StringSchema::create(
            description: 'Identifier of a content repository',
            examples: ['default'],
            minLength: 1,
            maxLength: 15,
            pattern: '^[a-z][a-z\d_]*[a-z]$',
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

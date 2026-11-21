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

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;
use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * The NodeAggregateId supersedes the Node Identifier from Neos <= 9.x.
 *
 * @api
 */
final readonly class NodeAggregateId implements ProvidesSchema, \JsonSerializable
{
    private function __construct(
        public string $value
    ) {
    }

    public static function create(): self
    {
        return new self(UuidFactory::create());
    }

    public static function fromString(string $value): self
    {
        $validationResult = self::schema()->validate($value);
        if (!$validationResult->valid) {
            throw new \InvalidArgumentException(sprintf('Invalid node aggregate identifier "%s": %s', $value, $validationResult->issues), 1791889843);
        }
        return new self($value);
    }

    public static function tryFromString(string $value): ?self
    {
        return self::hasValidFormat($value) ? new self($value) : null;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    private static function hasValidFormat(string $value): bool
    {
        return self::schema()->validate($value)->valid;
    }

    public static function schema(): StringSchema
    {
        static $schema = null;
        return $schema ??= StringSchema::create(
            description: 'Identifier of a node aggregate',
            examples: ['a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d', 'sites'],
            minLength: 1,
            maxLength: 64,
            pattern: '^[a-z0-9\-]+$',
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

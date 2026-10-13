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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;
use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * The ContentStreamId is the identifier for a Content Stream, which is
 * a central concept in the Event-Sourced CR introduced with Neos 5.0.
 *
 * @api
 */
final class ContentStreamId implements ProvidesSchema, \JsonSerializable
{
    /**
     * A preg pattern to match against content stream identifiers
     */
    public const PATTERN = '/^([a-z0-9\-]{1,40})$/';

    /**
     * @var array<string,self>
     */
    private static array $instances = [];

    private function __construct(
        public readonly string $value
    ) {
        $validationResult = self::schema()->validate($value);
        if (!$validationResult->valid) {
            throw new \InvalidArgumentException(sprintf('Invalid content stream identifier "%s": %s', $value, $validationResult->issues), 1791889844);
        }
    }

    private static function instance(string $value): self
    {
        return self::$instances[$value] ??= new self($value);
    }

    public static function fromString(string $value): self
    {
        return self::instance($value);
    }

    public static function create(): self
    {
        return self::instance(UuidFactory::create());
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function schema(): StringSchema
    {
        static $schema = null;
        return $schema ??= StringSchema::create(
            description: 'Identifier of a content stream',
            examples: ['a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d'],
            minLength: 1,
            maxLength: 40,
            pattern: '^[a-z0-9\-]+$',
        );
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

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

namespace Neos\ContentRepository\Core\NodeType;

use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * Name of a Node Type; e.g. "Neos.Neos:Content"
 *
 * @api
 */
final class NodeTypeName implements ProvidesSchema, \JsonSerializable
{
    public const ROOT_NODE_TYPE_NAME = 'Neos.ContentRepository:Root';

    /**
     * @var array<string,self>
     */
    private static array $instances = [];

    private function __construct(
        public readonly string $value
    ) {
        $validationResult = self::schema()->validate($value);
        if (!$validationResult->valid) {
            throw new \InvalidArgumentException(sprintf('Invalid node type name "%s": %s', $value, $validationResult->issues), 1505835958);
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

    public function equals(self $other): bool
    {
        return $this === $other;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function schema(): StringSchema
    {
        static $schema = null;
        return $schema ??= StringSchema::create(
            description: 'Name of a node type',
            examples: ['Neos.Neos:Document', 'Vendor.Site:Content.Text'],
            minLength: 1,
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

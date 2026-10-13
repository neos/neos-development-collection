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

use Neos\JsonSchema\ProvidesSchema;
use Neos\JsonSchema\StringSchema;

/**
 * @api
 */
final readonly class PropertyName implements ProvidesSchema, \JsonSerializable
{
    private function __construct(
        public string $value
    ) {
        $validationResult = self::schema()->validate($value);
        if (!$validationResult->valid) {
            throw new \InvalidArgumentException(sprintf('Invalid property name "%s": %s', $value, $validationResult->issues), 1519745994);
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
            description: 'Name of a node property as declared in the node type',
            examples: ['title'],
            minLength: 1,
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

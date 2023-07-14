<?php

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Property;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\PropertyTypeIsInvalid;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Psr\Http\Message\UriInterface;

/**
 * The property type value object as declared in a NodeType
 *
 * Only for use on the write side to enforce constraints
 *
 * @internal
 */
final class PropertyType
{
    public const TYPE_BOOL = 'boolean';
    public const TYPE_INT = 'integer';
    public const TYPE_FLOAT = 'float';
    public const TYPE_STRING = 'string';
    public const TYPE_ARRAY = 'array';
    public const TYPE_DATE = 'DateTimeImmutable';

    public const PATTERN_ARRAY_OF = '/array<[^>]+>/';

    /** only set if {@see sef:: isArrayOf()} */
    private self $arrayOfType;

    private function __construct(
        public readonly string $value
    ) {
        if ($this->isArrayOf()) {
            $arrayOfType = self::tryFromString($this->getArrayOf());
            if (!$arrayOfType && !$arrayOfType->isArray()) {
                throw new \DomainException(sprintf(
                    'Array declaration "%s" has invalid subType. Expected either class string or int',
                    $this->value
                ));
            }
            $this->arrayOfType = $arrayOfType;
        }
    }

    /**
     * @throws PropertyTypeIsInvalid
     */
    public static function fromNodeTypeDeclaration(
        string $declaration,
        PropertyName $propertyName,
        NodeTypeName $nodeTypeName
    ): self {
        if ($declaration === 'reference' || $declaration === 'references') {
            throw PropertyTypeIsInvalid::becauseItIsReference($propertyName, $nodeTypeName);
        }
        $type = self::tryFromString($declaration);
        if (!$type) {
            throw PropertyTypeIsInvalid::becauseItIsUndefined($propertyName, $declaration, $nodeTypeName);
        }
        return $type;
    }

    private static function tryFromString(string $declaration): ?self
    {
        if ($declaration === 'reference' || $declaration === 'references') {
            return null;
        }
        if ($declaration === 'bool' || $declaration === 'boolean') {
            return self::bool();
        }
        if ($declaration === 'int' || $declaration === 'integer') {
            return self::int();
        }
        if ($declaration === 'float' || $declaration === 'double') {
            return self::float();
        }
        if (
            in_array($declaration, [
                'DateTime',
                '\DateTime',
                'DateTimeImmutable',
                '\DateTimeImmutable',
                'DateTimeInterface',
                '\DateTimeInterface'
            ])
        ) {
            return self::date();
        }
        if ($declaration === 'Uri' || $declaration === Uri::class || $declaration === UriInterface::class) {
            $declaration = Uri::class;
        }
        $className = $declaration[0] != '\\'
            ? '\\' . $declaration
            : $declaration;
        if (
            $declaration !== self::TYPE_FLOAT
            && $declaration !== self::TYPE_STRING
            && $declaration !== self::TYPE_ARRAY
            && !class_exists($className)
            && !interface_exists($className)
            && !preg_match(self::PATTERN_ARRAY_OF, $declaration)
        ) {
            return null;
        }

        return new self($declaration);
    }

    public static function bool(): self
    {
        return new self(self::TYPE_BOOL);
    }

    public static function int(): self
    {
        return new self(self::TYPE_INT);
    }

    public static function string(): self
    {
        return new self(self::TYPE_STRING);
    }

    public static function float(): self
    {
        return new self(self::TYPE_FLOAT);
    }

    public static function date(): self
    {
        return new self(self::TYPE_DATE);
    }

    public function isBool(): bool
    {
        return $this->value === self::TYPE_BOOL;
    }

    public function isInt(): bool
    {
        return $this->value === self::TYPE_INT;
    }

    public function isFloat(): bool
    {
        return $this->value === self::TYPE_FLOAT;
    }

    public function isString(): bool
    {
        return $this->value === self::TYPE_STRING;
    }

    public function isArray(): bool
    {
        return $this->value === self::TYPE_ARRAY;
    }

    public function isArrayOf(): bool
    {
        return (bool)preg_match(self::PATTERN_ARRAY_OF, $this->value);
    }

    public function isDate(): bool
    {
        return $this->value === self::TYPE_DATE;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function getArrayOf(): string
    {
        return \mb_substr($this->value, 6, -1);
    }

    public function isMatchedBy(mixed $propertyValue): bool
    {
        if (is_null($propertyValue)) {
            return true;
        }
        if ($this->isBool()) {
            return is_bool($propertyValue);
        }
        if ($this->isInt()) {
            return is_int($propertyValue);
        }
        if ($this->isFloat()) {
            return is_float($propertyValue);
        }
        if ($this->isString()) {
            return is_string($propertyValue);
        }
        if ($this->isArray()) {
            return is_array($propertyValue);
        }
        if ($this->isDate()) {
            return $propertyValue instanceof \DateTimeInterface;
        }
        if ($this->isArrayOf()) {
            if (!is_array($propertyValue)) {
                return false;
            }
            foreach ($propertyValue as $value) {
                if (!$this->arrayOfType->isMatchedBy($value)) {
                    return false;
                }
            }
            return true;
        }

        $className = $this->value[0] != '\\'
            ? '\\' . $this->value
            : $this->value;

        return (class_exists($className) || interface_exists($className)) && $propertyValue instanceof $className;
    }

    public function getSerializationType(): string
    {
        return $this->value;
    }
}

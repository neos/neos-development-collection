<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\Exception\ReferenceTypeNameIsInvalid;

/**
 * @api
 */
final class ReferenceTypeName implements \JsonSerializable
{
    private const string PATTERN = '/^([a-zA-Z][a-zA-Z0-9:.]{0,254})$/';

    /**
     * @var array<string,\WeakReference<self>>
     */
    private static array $references = [];

    private function __construct(
        public readonly string $value
    ) {
    }

    private static function instance(string $value): self
    {
        return (self::$references[$value] ??= \WeakReference::create($_ = new self($value)))->get();
    }

    public static function fromString(string $value): self
    {
        if (!self::hasValidFormat($value)) {
            throw ReferenceTypeNameIsInvalid::becauseItDoesNotMatchTheRequiredPattern($value);
        }
        return self::instance($value);
    }

    private static function hasValidFormat(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __destruct()
    {
        unset(self::$references[$this->value]);
    }
}

<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria;

use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * Criteria that matches if a property is equal to the specified value
 *     "stringProp = 'foo' OR intProp = 123 OR floatProp = 123.45 OR boolProp = true"
 *
 * Criteria that matches if a property is equal to "foo" ignoring the case (e.g. "Foo" and "FOO" would match as well)
 *     "stringProp =~ 'foo'"
 *
 * @see PropertyValueCriteriaParser
 * @api
 */
final class PropertyValueEquals implements PropertyValueCriteriaInterface
{
    private function __construct(
        public readonly PropertyName $propertyName,
        public readonly string|bool|int|float $value,
        public readonly bool $caseSensitive,
    ) {
    }

    public static function create(PropertyName $propertyName, string|bool|int|float $value, bool $caseSensitive): self
    {
        return new self($propertyName, $value, $caseSensitive);
    }
}

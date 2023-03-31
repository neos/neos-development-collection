<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * Criteria that matches if a property is less than the specified value
 *     "stringProp< < 'foo' OR intProp < 123 OR floatProp < 123.45"
 *
 * @see PropertyValueCriteriaParser
 * @api
 */
final class PropertyValueLessThan implements PropertyValueCriteriaInterface
{
    private function __construct(
        public readonly PropertyName $propertyName,
        public readonly string|int|float $value,
    ) {
    }

    public static function create(PropertyName $propertyName, string|int|float $value): self
    {
        return new self($propertyName, $value);
    }
}

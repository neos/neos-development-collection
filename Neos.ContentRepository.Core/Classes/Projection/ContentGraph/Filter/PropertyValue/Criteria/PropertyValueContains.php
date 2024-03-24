<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria;

use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * Criteria that matches if a property contains the specified string (case-sensitive)
 *     "prop1 *= 'foo'"
 *
 * Criteria that matches if a property contains the specified string (case-insensitive)
 *      "prop1 *=~ 'foo'"
 *
 * @see PropertyValueCriteriaParser
 * @api
 */
final readonly class PropertyValueContains implements PropertyValueCriteriaInterface
{
    private function __construct(
        public PropertyName $propertyName,
        public string $value,
        public bool $caseSensitive,
    ) {
    }

    public static function create(PropertyName $propertyName, string $value, bool $caseSensitive): self
    {
        return new self($propertyName, $value, $caseSensitive);
    }
}

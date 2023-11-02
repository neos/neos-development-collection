<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\AndCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\NegateCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\OrCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueContains;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueCriteriaInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEndsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueGreaterThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThan;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueLessThanOrEqual;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueStartsWith;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;

/**
 * Performs property checks against a given set of constraints
 *
 * @internal
 */
final class PropertyValueCriteriaMatcher
{
    public static function matchesPropertyCollection(PropertyCollection $propertyCollection, PropertyValueCriteriaInterface $propertyValueCriteria): bool
    {
        switch (true) {
            case $propertyValueCriteria instanceof AndCriteria:
                return self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria1) && self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria2);
            case $propertyValueCriteria instanceof OrCriteria:
                return self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria1) || self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria2);
            case $propertyValueCriteria instanceof NegateCriteria:
                return !self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria);
            case $propertyValueCriteria instanceof PropertyValueContains:
                $propertyValue = $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value;
                if ($propertyValueCriteria->caseSensitive) {
                    return is_string($propertyValue)
                        ? str_contains($propertyValue, $propertyValueCriteria->value)
                        : false;
                } else {
                    return is_string($propertyValue)
                        ? str_contains(mb_strtolower($propertyValue), mb_strtolower($propertyValueCriteria->value))
                        : false;
                }
            case $propertyValueCriteria instanceof PropertyValueEndsWith:
                $propertyValue = $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value;
                if ($propertyValueCriteria->caseSensitive) {
                    return is_string($propertyValue)
                        ? str_ends_with($propertyValue, $propertyValueCriteria->value)
                        : false;
                } else {
                    return is_string($propertyValue)
                        ? str_ends_with(mb_strtolower($propertyValue), mb_strtolower($propertyValueCriteria->value))
                        : false;
                }
            case $propertyValueCriteria instanceof PropertyValueStartsWith:
                $propertyValue = $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value;
                if ($propertyValueCriteria->caseSensitive) {
                    return is_string($propertyValue)
                        ? str_starts_with($propertyValue, $propertyValueCriteria->value)
                        : false;
                } else {
                    return is_string($propertyValue)
                        ? str_starts_with(mb_strtolower($propertyValue), mb_strtolower($propertyValueCriteria->value))
                        : false;
                }
            case $propertyValueCriteria instanceof PropertyValueEquals:
                if (!$propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)) {
                    return false;
                }
                $propertyValue = $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value;
                if ($propertyValueCriteria->caseSensitive) {
                    return $propertyValue == $propertyValueCriteria->value;
                } else {
                    return (is_string($propertyValue) ? mb_strtolower($propertyValue) : $propertyValue)
                        == (is_string($propertyValueCriteria->value) ? mb_strtolower($propertyValueCriteria->value) : $propertyValueCriteria->value);
                }
            case $propertyValueCriteria instanceof PropertyValueGreaterThan:
                return $propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)
                    && $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value > $propertyValueCriteria->value;
            case $propertyValueCriteria instanceof PropertyValueGreaterThanOrEqual:
                return $propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)
                    && $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value >= $propertyValueCriteria->value;
            case $propertyValueCriteria instanceof PropertyValueLessThan:
                return $propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)
                    && $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value < $propertyValueCriteria->value;
            case $propertyValueCriteria instanceof PropertyValueLessThanOrEqual:
                return $propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)
                    && $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value <= $propertyValueCriteria->value;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid/unsupported property value criteria "%s"', get_debug_type($propertyValueCriteria)), 1679561073);
        }
    }

    public static function matchesNode(Node $node, PropertyValueCriteriaInterface $propertyValueCriteria): bool
    {
        return static::matchesPropertyCollection($node->properties, $propertyValueCriteria);
    }
}

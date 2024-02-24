<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
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
    public static function matchesNode(Node $node, PropertyValueCriteriaInterface $propertyValueCriteria): bool
    {
        return static::matchesSerializedPropertyValues($node->properties->serialized(), $propertyValueCriteria);
    }

    public static function matchesPropertyCollection(PropertyCollection $propertyCollection, PropertyValueCriteriaInterface $propertyValueCriteria): bool
    {
        return static::matchesSerializedPropertyValues($propertyCollection->serialized(), $propertyValueCriteria);
    }

    public static function matchesSerializedPropertyValues(SerializedPropertyValues $serializedPropertyValues, PropertyValueCriteriaInterface $propertyValueCriteria): bool
    {
        return match ($propertyValueCriteria::class) {
            AndCriteria::class => self::matchesSerializedPropertyValues($serializedPropertyValues, $propertyValueCriteria->criteria1)
                && self::matchesSerializedPropertyValues($serializedPropertyValues, $propertyValueCriteria->criteria2),
            OrCriteria::class => self::matchesSerializedPropertyValues($serializedPropertyValues, $propertyValueCriteria->criteria1)
                || self::matchesSerializedPropertyValues($serializedPropertyValues, $propertyValueCriteria->criteria2),
            NegateCriteria::class => !self::matchesSerializedPropertyValues($serializedPropertyValues, $propertyValueCriteria->criteria),
            PropertyValueContains::class => self::propertyValueContains($serializedPropertyValues, $propertyValueCriteria),
            PropertyValueEndsWith::class => self::propertyValueEndsWith($serializedPropertyValues, $propertyValueCriteria),
            PropertyValueStartsWith::class => self::propertyValueStartsWith($serializedPropertyValues, $propertyValueCriteria),
            PropertyValueEquals::class => self::propertyValueEquals($serializedPropertyValues, $propertyValueCriteria),
            PropertyValueGreaterThan::class => $serializedPropertyValues->propertyExists($propertyValueCriteria->propertyName->value)
                && $serializedPropertyValues->getProperty($propertyValueCriteria->propertyName->value)?->value > $propertyValueCriteria->value,
            PropertyValueGreaterThanOrEqual::class => $serializedPropertyValues->propertyExists($propertyValueCriteria->propertyName->value)
                && $serializedPropertyValues->getProperty($propertyValueCriteria->propertyName->value)?->value >= $propertyValueCriteria->value,
            PropertyValueLessThan::class => $serializedPropertyValues->propertyExists($propertyValueCriteria->propertyName->value)
                && $serializedPropertyValues->getProperty($propertyValueCriteria->propertyName->value)?->value < $propertyValueCriteria->value,
            PropertyValueLessThanOrEqual::class => $serializedPropertyValues->propertyExists($propertyValueCriteria->propertyName->value)
                && $serializedPropertyValues->getProperty($propertyValueCriteria->propertyName->value)?->value <= $propertyValueCriteria->value,
            default => throw new \InvalidArgumentException(sprintf('Invalid/unsupported property value criteria "%s"', get_debug_type($propertyValueCriteria)), 1679561073),
        };
    }

    private static function propertyValueContains(SerializedPropertyValues $serializedPropertyValues, PropertyValueContains $propertyValueCriteria): bool
    {
        $propertyValue = $serializedPropertyValues->getProperty($propertyValueCriteria->propertyName->value)?->value;
        if ($propertyValueCriteria->caseSensitive) {
            return is_string($propertyValue) ? str_contains($propertyValue, $propertyValueCriteria->value) : false;
        } else {
            return is_string($propertyValue) ? str_contains(mb_strtolower($propertyValue), mb_strtolower($propertyValueCriteria->value)) : false;
        }
    }

    private static function propertyValueStartsWith(SerializedPropertyValues $serializedPropertyValues, PropertyValueStartsWith $propertyValueCriteria): bool
    {
        $propertyValue = $serializedPropertyValues->getProperty($propertyValueCriteria->propertyName->value)?->value;
        if ($propertyValueCriteria->caseSensitive) {
            return is_string($propertyValue) ? str_starts_with($propertyValue, $propertyValueCriteria->value) : false;
        } else {
            return is_string($propertyValue) ? str_starts_with(mb_strtolower($propertyValue), mb_strtolower($propertyValueCriteria->value)) : false;
        }
    }

    private static function propertyValueEndsWith(SerializedPropertyValues $serializedPropertyValues, PropertyValueEndsWith $propertyValueCriteria): bool
    {
        $propertyValue = $serializedPropertyValues->getProperty($propertyValueCriteria->propertyName->value)?->value;
        if ($propertyValueCriteria->caseSensitive) {
            return is_string($propertyValue) ? str_ends_with($propertyValue, $propertyValueCriteria->value) : false;
        } else {
            return is_string($propertyValue) ? str_ends_with(mb_strtolower($propertyValue), mb_strtolower($propertyValueCriteria->value)) : false;
        }
    }

    private static function propertyValueEquals(SerializedPropertyValues $serializedPropertyValues, PropertyValueEquals $propertyValueCriteria): bool
    {
        if (!$serializedPropertyValues->propertyExists($propertyValueCriteria->propertyName->value)) {
            return false;
        }
        $propertyValue = $serializedPropertyValues->getProperty($propertyValueCriteria->propertyName->value)?->value;
        if ($propertyValueCriteria->caseSensitive) {
            return $propertyValue === $propertyValueCriteria->value;
        }
        return (is_string($propertyValue) ? mb_strtolower($propertyValue) : $propertyValue) === (is_string($propertyValueCriteria->value) ? mb_strtolower($propertyValueCriteria->value) : $propertyValueCriteria->value);
    }
}

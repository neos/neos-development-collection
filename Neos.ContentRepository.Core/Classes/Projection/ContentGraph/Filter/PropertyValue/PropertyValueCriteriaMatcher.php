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
        return match ($propertyValueCriteria::class) {
            AndCriteria::class => self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria1)
                && self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria2),
            OrCriteria::class => self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria1)
                || self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria2),
            NegateCriteria::class => !self::matchesPropertyCollection($propertyCollection, $propertyValueCriteria->criteria),
            PropertyValueContains::class => self::propertyValueContains($propertyCollection, $propertyValueCriteria),
            PropertyValueEndsWith::class => self::propertyValueEndsWith($propertyCollection, $propertyValueCriteria),
            PropertyValueStartsWith::class => self::propertyValueStartsWith($propertyCollection, $propertyValueCriteria),
            PropertyValueEquals::class => self::propertyValueEquals($propertyCollection, $propertyValueCriteria),
            PropertyValueGreaterThan::class => $propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)
                && $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value > $propertyValueCriteria->value,
            PropertyValueGreaterThanOrEqual::class => $propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)
                && $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value >= $propertyValueCriteria->value,
            PropertyValueLessThan::class => $propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)
                && $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value < $propertyValueCriteria->value,
            PropertyValueLessThanOrEqual::class => $propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)
                && $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value <= $propertyValueCriteria->value,
            default => throw new \InvalidArgumentException(sprintf('Invalid/unsupported property value criteria "%s"', get_debug_type($propertyValueCriteria)), 1679561073),
        };
    }

    private static function propertyValueContains(PropertyCollection $propertyCollection, PropertyValueContains $propertyValueCriteria): bool
    {
        $propertyValue = $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value;
        if ($propertyValueCriteria->caseSensitive) {
            return is_string($propertyValue) ? str_contains($propertyValue, $propertyValueCriteria->value) : false;
        } else {
            return is_string($propertyValue) ? str_contains(mb_strtolower($propertyValue), mb_strtolower($propertyValueCriteria->value)) : false;
        }
    }

    private static function propertyValueStartsWith(PropertyCollection $propertyCollection, PropertyValueStartsWith $propertyValueCriteria): bool
    {
        $propertyValue = $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value;
        if ($propertyValueCriteria->caseSensitive) {
            return is_string($propertyValue) ? str_starts_with($propertyValue, $propertyValueCriteria->value) : false;
        } else {
            return is_string($propertyValue) ? str_starts_with(mb_strtolower($propertyValue), mb_strtolower($propertyValueCriteria->value)) : false;
        }
    }

    private static function propertyValueEndsWith(PropertyCollection $propertyCollection, PropertyValueEndsWith $propertyValueCriteria): bool
    {
        $propertyValue = $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value;
        if ($propertyValueCriteria->caseSensitive) {
            return is_string($propertyValue) ? str_ends_with($propertyValue, $propertyValueCriteria->value) : false;
        } else {
            return is_string($propertyValue) ? str_ends_with(mb_strtolower($propertyValue), mb_strtolower($propertyValueCriteria->value)) : false;
        }
    }

    private static function propertyValueEquals(PropertyCollection $propertyCollection, PropertyValueEquals $propertyValueCriteria): bool
    {
        if (!$propertyCollection->serialized()->propertyExists($propertyValueCriteria->propertyName->value)) {
            return false;
        }
        $propertyValue = $propertyCollection->serialized()->getProperty($propertyValueCriteria->propertyName->value)?->value;
        if ($propertyValueCriteria->caseSensitive) {
            return $propertyValue === $propertyValueCriteria->value;
        }
        return (is_string($propertyValue) ? mb_strtolower($propertyValue) : $propertyValue) === (is_string($propertyValueCriteria->value) ? mb_strtolower($propertyValueCriteria->value) : $propertyValueCriteria->value);
    }

    public static function matchesNode(Node $node, PropertyValueCriteriaInterface $propertyValueCriteria): bool
    {
        return static::matchesPropertyCollection($node->properties, $propertyValueCriteria);
    }
}

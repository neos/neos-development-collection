<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * Performs search term check against the nodes properties
 *
 * @internal
 */
class SearchTermMatcher
{
    public static function matchesNode(Node $node, SearchTerm $searchTerm): bool
    {
        return static::matchesSerializedPropertyValues($node->properties->serialized(), $searchTerm, $node->aggregateId);
    }

    public static function matchesSerializedPropertyValues(SerializedPropertyValues $serializedPropertyValues, SearchTerm $searchTerm, ?NodeAggregateId $nodeAggregateId = null): bool
    {
        if ($searchTerm->term === '') {
            return true;
        }
        foreach ($serializedPropertyValues as $propertyName => $serializedPropertyValue) {
            if (self::matchesValue($serializedPropertyValue->value, $searchTerm, $propertyName, $nodeAggregateId)) {
                return true;
            }
        }
        return false;
    }

    private static function matchesValue(mixed $value, SearchTerm $searchTerm, string $propertyName, ?NodeAggregateId $nodeAggregateId): bool
    {
        if (is_array($value) || $value instanceof \ArrayObject) {
            foreach ($value as $subValue) {
                if (self::matchesValue($subValue, $searchTerm, $propertyName, $nodeAggregateId)) {
                    return true;
                }
            }
            return false;
        }

        return match (true) {
            $value === null => false,
            is_string($value) => mb_stripos($value, $searchTerm->term) !== false,
            // the following behaviour might seem odd, but is implemented after how the doctrine adapter filtering is currently implemented
            is_int($value),
            is_float($value) => str_contains((string)$value, $searchTerm->term),
            $value === true => str_contains('true', $searchTerm->term),
            $value === false => str_contains('false', $searchTerm->term),
            default => throw new \InvalidArgumentException(sprintf(
                'Handling for type %s within property "%s" of node "%s" is not implemented.',
                get_debug_type($value),
                $propertyName,
                $nodeAggregateId?->value ?: 'unknown'
            )),
        };
    }
}

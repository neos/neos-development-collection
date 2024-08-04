<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValue;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * Performs search term check against the nodes properties
 *
 * @internal
 */
class SearchTermMatcher
{
    public static function matchesNode(Node $node, SearchTerm $searchTerm): bool
    {
        return static::matchesSerializedPropertyValues($node->properties->serialized(), $searchTerm);
    }

    public static function matchesSerializedPropertyValues(SerializedPropertyValues $serializedPropertyValues, SearchTerm $searchTerm): bool
    {
        foreach ($serializedPropertyValues as $serializedPropertyValue) {
            if (self::matchesSerializedPropertyValue($serializedPropertyValue, $searchTerm)) {
                return true;
            }
        }
        return false;
    }

    private static function matchesSerializedPropertyValue(SerializedPropertyValue $serializedPropertyValue, SearchTerm $searchTerm): bool
    {
        return match (true) {
            is_string($serializedPropertyValue->value) => mb_stripos($serializedPropertyValue->value, $searchTerm->term) !== false,
            // the following behaviour might seem odd, but is implemented after how the database filtering should behave
            is_int($serializedPropertyValue->value),
            is_float($serializedPropertyValue->value) => str_contains((string)$serializedPropertyValue->value, $searchTerm->term),
            $serializedPropertyValue->value === true => $searchTerm->term === 'true',
            $serializedPropertyValue->value === false => $searchTerm->term === 'false'
        };
    }
}

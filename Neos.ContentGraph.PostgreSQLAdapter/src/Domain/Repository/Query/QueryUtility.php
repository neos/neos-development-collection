<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\TimestampField;
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
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\SearchTerm\SearchTerm;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * @internal
 */
final class QueryUtility
{
    // Postgresadapter:
    //   optimizedSubtreeTags: ['requiredRole']

    public static function parseDateTimeString(string $string): \DateTimeImmutable
    {
        $result = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $string);
        if ($result === false) {
            throw new \RuntimeException(sprintf('Failed to parse "%s" into a valid DateTime', $string), 1678902055);
        }
        return $result;
    }


    /**
     * @param array<string,mixed> $parameters
     * @param array<string,int|string> $types
     */
    public static function getRestrictionClause(
        VisibilityConstraints $visibilityConstraints,
        ContentGraphTableNames $tableNames,
        string $tableAlias = '',
        array &$parameters = [],
        array &$types = [],
    ): string {
        $excludedSubtreeTags = $visibilityConstraints->excludedSubtreeTags->toStringArray();
        if (count($excludedSubtreeTags) === 0) {
            return '';
        }

        $nodeAlias = $tableAlias . 'n';
        $hierarchyAlias = $tableAlias . 'h';
        $paramName = 'excludedSubtreeTags' . ($tableAlias !== '' ? '_' . $tableAlias : '');

        $parameters[$paramName] = $excludedSubtreeTags;
        $types[$paramName] = Connection::PARAM_STR_ARRAY;

        // Check if the node's entry in the per-anchor-keyed JSONB has any excluded tag.
        // The subtreetags column format: {"<anchor>": {"<tag>": true/null}, ...}
        // We use jsonb_exists_any() instead of ?| to avoid conflict with Doctrine DBAL's ? placeholder.
        return '
            AND NOT jsonb_exists_any(COALESCE(' . $hierarchyAlias . '.subtreetags->(' . $nodeAlias . '.relationanchorpoint::text), \'{}\'), ARRAY[:' . $paramName . ']::text[])';
    }

    /**
     * @param ExpandedNodeTypeCriteria $nodeTypeCriteria
     * @param string $tableAlias
     * @param array<string,mixed> $parameters
     * @param array<string,int|string> $types
     * @return string
     */
    public static function getNodeTypeCriteriaClause(
        ExpandedNodeTypeCriteria $nodeTypeCriteria,
        string $tableAlias,
        array &$parameters,
        array &$types,
    ): string {
        $query = '';
        $parameters['allowedNodeTypeNames'] = $nodeTypeCriteria->explicitlyAllowedNodeTypeNames->toStringArray();
        $parameters['disallowedNodeTypeNames'] = $nodeTypeCriteria->explicitlyDisallowedNodeTypeNames->toStringArray();
        $types['allowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
        $types['disallowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
        if (!$nodeTypeCriteria->explicitlyAllowedNodeTypeNames->isEmpty()) {
            if (!$nodeTypeCriteria->explicitlyDisallowedNodeTypeNames->isEmpty()) {
                if ($nodeTypeCriteria->isWildCardAllowed) {
                    $query .= '
            AND ' . $tableAlias . '.nodetypename NOT IN (:disallowedNodeTypeNames)
            OR ' . $tableAlias . '.nodetypename IN (:allowedNodeTypeNames)';
                } else {
                    // FIXME what is the usecase here?
                    $query .= '
            AND ' . $tableAlias . '.nodetypename IN (:allowedNodeTypeNames)
            AND ' . $tableAlias . '.nodetypename NOT IN (:disallowedNodeTypeNames)';
                }
            } else {
                if (!$nodeTypeCriteria->isWildCardAllowed) {
                    $query .= '
            AND ' . $tableAlias . '.nodetypename IN (:allowedNodeTypeNames)';
                }
            }
        } elseif (!$nodeTypeCriteria->explicitlyDisallowedNodeTypeNames->isEmpty()) {
            $query .= '
            AND ' . $tableAlias . '.nodetypename NOT IN (:disallowedNodeTypeNames)';
        }
        return $query;
    }

    public static function getOrderingClause(Ordering $ordering, string $nodeTableAlias): string
    {
        $orderings = [];
        foreach ($ordering as $orderingField) {
            // MySQL sorts NULLs first for ASC and last for DESC. PostgreSQL does the opposite.
            // To match MySQL behavior we add NULLS FIRST/NULLS LAST explicitly.
            $directionWithNulls = match ($orderingField->direction) {
                OrderingDirection::ASCENDING => 'ASC NULLS FIRST',
                OrderingDirection::DESCENDING => 'DESC NULLS LAST',
            };
            if ($orderingField->field instanceof PropertyName) {
                $orderings[] = self::extractPropertyValue($orderingField->field, $nodeTableAlias) . ' ' . $directionWithNulls;
            } else {
                $columnName = match ($orderingField->field) {
                    TimestampField::CREATED => 'created',
                    TimestampField::ORIGINAL_CREATED => 'originalcreated',
                    TimestampField::LAST_MODIFIED => 'lastmodified',
                    TimestampField::ORIGINAL_LAST_MODIFIED => 'originallastmodified',
                };
                $orderings[] = $nodeTableAlias . '.' . $columnName . ' ' . $directionWithNulls;
            }
        }
        return '
            ORDER BY ' . implode(', ', $orderings);
    }

    private static int $uniqueParameterCounter = 0;

    private static function createUniqueParameterName(): string
    {
        return 'param_' . (++self::$uniqueParameterCounter);
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public static function getSearchTermConstraintClause(
        SearchTerm $searchTerm,
        string $nodeTableAlias,
        array &$parameters,
    ): string {
        if ($searchTerm->term === '') {
            return '';
        }

        $paramName = self::createUniqueParameterName();
        $parameters[$paramName] = '%' . $searchTerm->term . '%';

        // Search across all property values in the JSONB properties column.
        // The properties structure is: {"propName": {"value": "...", "type": "..."}, ...}
        // We extract all "value" entries and check if any matches the search term (case-insensitive).
        return '
            AND EXISTS (
                SELECT 1 FROM jsonb_each(CASE WHEN jsonb_typeof(' . $nodeTableAlias . '.properties) = \'object\' THEN ' . $nodeTableAlias . '.properties ELSE \'{}\'::jsonb END) AS props(key, val)
                WHERE props.val->>\'value\' ILIKE :' . $paramName . '
            )';
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public static function getPropertyValueConstraintClause(
        PropertyValueCriteriaInterface $propertyValue,
        string $nodeTableAlias,
        array &$parameters,
    ): string {
        return '
            AND ' . self::propertyValueConstraintExpression($propertyValue, $nodeTableAlias, $parameters);
    }

    /**
     * @param array<string,mixed> $parameters
     */
    private static function propertyValueConstraintExpression(
        PropertyValueCriteriaInterface $propertyValue,
        string $nodeTableAlias,
        array &$parameters,
    ): string {
        return match ($propertyValue::class) {
            AndCriteria::class => '(' . self::propertyValueConstraintExpression($propertyValue->criteria1, $nodeTableAlias, $parameters) . ' AND ' . self::propertyValueConstraintExpression($propertyValue->criteria2, $nodeTableAlias, $parameters) . ')',
            NegateCriteria::class => 'NOT (' . self::propertyValueConstraintExpression($propertyValue->criteria, $nodeTableAlias, $parameters) . ')',
            OrCriteria::class => '(' . self::propertyValueConstraintExpression($propertyValue->criteria1, $nodeTableAlias, $parameters) . ' OR ' . self::propertyValueConstraintExpression($propertyValue->criteria2, $nodeTableAlias, $parameters) . ')',
            PropertyValueContains::class => self::searchPropertyValueExpression($propertyValue->propertyName, '%' . $propertyValue->value . '%', $nodeTableAlias, $propertyValue->caseSensitive, $parameters),
            PropertyValueEndsWith::class => self::searchPropertyValueExpression($propertyValue->propertyName, '%' . $propertyValue->value, $nodeTableAlias, $propertyValue->caseSensitive, $parameters),
            PropertyValueEquals::class => is_string($propertyValue->value) ? self::searchPropertyValueExpression($propertyValue->propertyName, $propertyValue->value, $nodeTableAlias, $propertyValue->caseSensitive, $parameters) : self::comparePropertyValueExpression($propertyValue->propertyName, $propertyValue->value, '=', $nodeTableAlias, $parameters),
            PropertyValueGreaterThan::class => self::comparePropertyValueExpression($propertyValue->propertyName, $propertyValue->value, '>', $nodeTableAlias, $parameters),
            PropertyValueGreaterThanOrEqual::class => self::comparePropertyValueExpression($propertyValue->propertyName, $propertyValue->value, '>=', $nodeTableAlias, $parameters),
            PropertyValueLessThan::class => self::comparePropertyValueExpression($propertyValue->propertyName, $propertyValue->value, '<', $nodeTableAlias, $parameters),
            PropertyValueLessThanOrEqual::class => self::comparePropertyValueExpression($propertyValue->propertyName, $propertyValue->value, '<=', $nodeTableAlias, $parameters),
            PropertyValueStartsWith::class => self::searchPropertyValueExpression($propertyValue->propertyName, $propertyValue->value . '%', $nodeTableAlias, $propertyValue->caseSensitive, $parameters),
            default => throw new \InvalidArgumentException(sprintf('Invalid/unsupported property value criteria "%s"', get_debug_type($propertyValue)), 1679561062),
        };
    }

    private static function extractPropertyValue(PropertyName $propertyName, string $nodeTableAlias): string
    {
        // PostgreSQL JSONB -> operator requires single-quoted string keys
        $escapedPropertyName = str_replace("'", "''", $propertyName->value);
        return $nodeTableAlias . '.properties->\'' . $escapedPropertyName . '\'->>\'value\'';
    }

    private static function extractPropertyValueJsonb(PropertyName $propertyName, string $nodeTableAlias): string
    {
        $escapedPropertyName = str_replace("'", "''", $propertyName->value);
        return $nodeTableAlias . '.properties->\'' . $escapedPropertyName . '\'->\'value\'';
    }

    /**
     * @param array<string,mixed> $parameters
     */
    private static function searchPropertyValueExpression(
        PropertyName $propertyName,
        string $value,
        string $nodeTableAlias,
        bool $caseSensitive,
        array &$parameters,
    ): string {
        $paramName = self::createUniqueParameterName();
        $parameters[$paramName] = $value;
        $extractedValue = self::extractPropertyValue($propertyName, $nodeTableAlias);

        if ($caseSensitive) {
            return $extractedValue . ' LIKE :' . $paramName;
        }

        return $extractedValue . ' ILIKE :' . $paramName;
    }

    /**
     * @param array<string,mixed> $parameters
     */
    private static function comparePropertyValueExpression(
        PropertyName $propertyName,
        string|int|float|bool $value,
        string $operator,
        string $nodeTableAlias,
        array &$parameters,
    ): string {
        if (is_bool($value)) {
            // Compare JSONB value directly: properties->'name'->'value' = 'true'::jsonb
            return self::extractPropertyValueJsonb($propertyName, $nodeTableAlias) . ' ' . $operator . ' \'' . ($value ? 'true' : 'false') . '\'::jsonb';
        }

        if (is_int($value) || is_float($value)) {
            // Cast the text extraction to numeric for comparison
            return '(' . self::extractPropertyValue($propertyName, $nodeTableAlias) . ')::numeric ' . $operator . ' ' . $value;
        }

        $paramName = self::createUniqueParameterName();
        $parameters[$paramName] = $value;
        return self::extractPropertyValue($propertyName, $nodeTableAlias) . ' ' . $operator . ' :' . $paramName;
    }
}

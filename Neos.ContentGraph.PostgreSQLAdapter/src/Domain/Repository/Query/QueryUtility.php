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
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

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
        $stAlias = 'st_restriction' . ($tableAlias !== '' ? '_' . $tableAlias : '');
        $paramName = 'excludedSubtreeTags' . ($tableAlias !== '' ? '_' . $tableAlias : '');

        $parameters[$paramName] = $excludedSubtreeTags;
        $types[$paramName] = Connection::PARAM_STR_ARRAY;

        return '
            AND NOT EXISTS(
                SELECT 1
                FROM ' . $tableNames->subTreeRelation() . ' ' . $stAlias . '
                WHERE ' . $nodeAlias . '.nodeaggregateid = ANY(' . $stAlias . '.affected_nodeaggregateids)
                  AND ' . $stAlias . '.dimensionspacepointhash = ' . $hierarchyAlias . '.dimensionspacepointhash
                  AND ' . $stAlias . '.contentstreamid = ' . $hierarchyAlias . '.contentstreamid
                  AND ' . $stAlias . '.subtreetags && ARRAY[:' . $paramName . ']::varchar(36)[]
            )';
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
}

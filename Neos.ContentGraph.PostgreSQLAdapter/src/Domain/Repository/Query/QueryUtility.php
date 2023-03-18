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
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraintsWithSubNodeTypes;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

/**
 * @internal
 */
final class QueryUtility
{
    public static function getRestrictionClause(
        VisibilityConstraints $visibilityConstraints,
        string $tableNamePrefix,
        string $prefix = ''
    ): string {
        if ($visibilityConstraints->isDisabledContentShown()) {
            return '';
        }

        return '
            AND NOT EXISTS (
                SELECT 1
                FROM ' . $tableNamePrefix . '_restrictionhyperrelation rest
                WHERE rest.contentstreamid = ' . $prefix . 'h.contentstreamid
                    AND rest.dimensionspacepointhash = ' . $prefix . 'h.dimensionspacepointhash
                    AND ' . $prefix . 'n.nodeaggregateid = ANY(rest.affectednodeaggregateids)
            )';
    }

    /**
     * @param NodeTypeConstraintsWithSubNodeTypes $nodeTypeConstraints
     * @param string $prefix
     * @param array<string,mixed> $parameters
     * @param array<string,int|string> $types
     * @return string
     */
    public static function getNodeTypeConstraintsClause(
        NodeTypeConstraintsWithSubNodeTypes $nodeTypeConstraints,
        string $prefix,
        array &$parameters,
        array &$types,
    ): string {
        $query = '';
        $parameters['allowedNodeTypeNames'] = $nodeTypeConstraints->explicitlyAllowedNodeTypeNames->toStringArray();
        $parameters['disallowedNodeTypeNames'] = $nodeTypeConstraints->explicitlyDisallowedNodeTypeNames->toStringArray();
        $types['allowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
        $types['disallowedNodeTypeNames'] = Connection::PARAM_STR_ARRAY;
        if (!$nodeTypeConstraints->explicitlyAllowedNodeTypeNames->isEmpty()) {
            if (!$nodeTypeConstraints->explicitlyDisallowedNodeTypeNames->isEmpty()) {
                if ($nodeTypeConstraints->isWildCardAllowed) {
                    $query .= '
            AND ' . $prefix . '.nodetypename NOT IN (:disallowedNodeTypeNames)
            OR ' . $prefix . '.nodetypename IN (:allowedNodeTypeNames)';
                } else {
                    $query .= '
            AND ' . $prefix . '.nodetypename IN (:allowedNodeTypeNames)
            AND ' . $prefix . '.nodetypename NOT IN (:disallowedNodeTypeNames)';
                }
            } else {
                if (!$nodeTypeConstraints->isWildCardAllowed) {
                    $query .= '
            AND ' . $prefix . '.nodetypename IN (:allowedNodeTypeNames)';
                }
            }
        } elseif (!$nodeTypeConstraints->explicitlyDisallowedNodeTypeNames->isEmpty()) {
            $query .= '
            AND ' . $prefix . '.nodetypename NOT IN (:disallowedNodeTypeNames)';
        }
        return $query;
    }
}

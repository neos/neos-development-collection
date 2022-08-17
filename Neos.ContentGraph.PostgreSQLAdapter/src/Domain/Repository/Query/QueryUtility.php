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

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\RestrictionHyperrelationRecord;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
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
                WHERE rest.contentstreamidentifier = ' . $prefix . 'h.contentstreamidentifier
                    AND rest.dimensionspacepointhash = ' . $prefix . 'h.dimensionspacepointhash
                    AND ' . $prefix . 'n.nodeaggregateidentifier = ANY(rest.affectednodeaggregateidentifiers)
            )';
    }
}

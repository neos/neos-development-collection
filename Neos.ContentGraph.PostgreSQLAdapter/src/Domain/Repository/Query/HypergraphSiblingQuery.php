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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

/**
 * @internal
 */
final class HypergraphSiblingQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    public static function create(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId,
        HypergraphSiblingQueryMode $queryMode,
        string $tableNamePrefix
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT sn.*, sh.contentstreamid, sh.dimensionspacepoint, ordinality, childnodeanchor
    FROM ' . $tableNamePrefix . '_node n
        JOIN ' . $tableNamePrefix . '_hierarchyhyperrelation sh ON n.relationanchorpoint = ANY(sh.childnodeanchors),
            unnest(sh.childnodeanchors) WITH ORDINALITY childnodeanchor
        JOIN ' . $tableNamePrefix . '_node sn ON childnodeanchor = sn.relationanchorpoint
    WHERE sh.contentstreamid = :contentStreamId
        AND sh.dimensionspacepointhash = :dimensionSpacePointHash
        AND n.nodeaggregateid = :nodeAggregateId
        AND childnodeanchor != n.relationanchorpoint'
                . $queryMode->renderCondition();

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'nodeAggregateId' => (string)$nodeAggregateId
        ];

        return new self($query, $parameters, $tableNamePrefix);
    }

    public function withRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, $this->tableNamePrefix, 's');

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }

    public function withOrdinalityOrdering(bool $reverse): self
    {
        $query = $this->query . '
    ORDER BY ordinality ' . ($reverse ? 'DESC' : 'ASC');

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }
}

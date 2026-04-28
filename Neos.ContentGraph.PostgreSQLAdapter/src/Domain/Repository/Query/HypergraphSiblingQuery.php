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

use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

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
        ContentGraphTableNames $tableNames
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT sn.*, sh.contentstreamid, sh.dimensionspacepoint, ordinality, childnodeanchor,
                sh.subtreetags->(sn.relationanchorpoint::text) as subtreetags
    FROM ' . $tableNames->node() . ' n
        JOIN ' . $tableNames->hierarchyRelation() . ' sh ON n.relationanchorpoint = ANY(sh.childnodeanchors),
            unnest(sh.childnodeanchors) WITH ORDINALITY childnodeanchor
        JOIN ' . $tableNames->node() . ' sn ON childnodeanchor = sn.relationanchorpoint
    WHERE sh.contentstreamid = :contentStreamId
        AND sh.dimensionspacepointhash = :dimensionSpacePointHash
        AND n.nodeaggregateid = :nodeAggregateId
        AND childnodeanchor != n.relationanchorpoint'
                . $queryMode->renderCondition();

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'nodeAggregateId' => $nodeAggregateId->value
        ];

        return new self($query, $parameters, $tableNames, []);
    }

    public function withRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $parameters = $this->parameters;
        $types = $this->types;
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, $this->tableNames, 's', $parameters, $types);

        return new self($query, $parameters, $this->tableNames, $types);
    }

    public function withOrdinalityOrdering(bool $reverse): self
    {
        $direction = $reverse ? 'DESC' : 'ASC';
        // If an ORDER BY already exists (from withOrdering), append ordinality as secondary sort
        if (stripos($this->query, 'ORDER BY') !== false) {
            $query = $this->query . ', ordinality ' . $direction;
        } else {
            $query = $this->query . ' ORDER BY ordinality ' . $direction;
        }

        return new self($query, $this->parameters, $this->tableNames, $this->types);
    }
}

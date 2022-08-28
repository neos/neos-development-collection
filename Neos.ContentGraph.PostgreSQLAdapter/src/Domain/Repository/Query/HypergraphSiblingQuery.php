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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

/**
 * @internal
 */
final class HypergraphSiblingQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    public static function create(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        HypergraphSiblingQueryMode $queryMode,
        string $tableNamePrefix
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT sn.*, sh.contentstreamidentifier, sh.dimensionspacepoint, ordinality, childnodeanchor
    FROM ' . $tableNamePrefix . '_node n
        JOIN ' . $tableNamePrefix . '_hierarchyhyperrelation sh ON n.relationanchorpoint = ANY(sh.childnodeanchors),
            unnest(sh.childnodeanchors) WITH ORDINALITY childnodeanchor
        JOIN ' . $tableNamePrefix . '_node sn ON childnodeanchor = sn.relationanchorpoint
    WHERE sh.contentstreamidentifier = :contentStreamIdentifier
        AND sh.dimensionspacepointhash = :dimensionSpacePointHash
        AND n.nodeaggregateidentifier = :nodeAggregateIdentifier
        AND childnodeanchor != n.relationanchorpoint'
                . $queryMode->renderCondition();

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier
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

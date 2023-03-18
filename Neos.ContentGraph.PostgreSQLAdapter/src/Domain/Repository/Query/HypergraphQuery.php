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

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\RestrictionHyperrelationRecord;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

/**
 * @internal
 */
final class HypergraphQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    public static function create(
        ContentStreamId $contentStreamId,
        string $tableNamePrefix,
        bool $joinRestrictionRelations = false
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT n.origindimensionspacepoint, n.nodeaggregateid,
                n.nodetypename, n.classification, n.properties, n.nodename,
                h.contentstreamid, h.dimensionspacepoint' . ($joinRestrictionRelations ? ',
                r.dimensionspacepointhash AS disabledDimensionSpacePointHash' : '') . '
            FROM ' . $tableNamePrefix . '_hierarchyhyperrelation h
            JOIN ' . $tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(h.childnodeanchors)'
            . ($joinRestrictionRelations
                ? '
            LEFT JOIN ' . $tableNamePrefix . '_restrictionhyperrelation r
                ON n.nodeaggregateid = r.originnodeaggregateid
                AND r.contentstreamid = h.contentstreamid
                AND r.dimensionspacepointhash = h.dimensionspacepointhash'
                : '')
            . '
            WHERE h.contentstreamid = :contentStreamId';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId
        ];

        return new self($query, $parameters, $tableNamePrefix);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->hash;

        return new self($query, $parameters, $this->tableNamePrefix);
    }

    public function withOriginDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND n.origindimensionspacepointhash = :originDimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['originDimensionSpacePointHash'] = $originDimensionSpacePoint->hash;

        return new self($query, $parameters, $this->tableNamePrefix);
    }

    public function withNodeAggregateId(NodeAggregateId $nodeAggregateId): self
    {
        $query = $this->query .= '
            AND n.nodeaggregateid = :nodeAggregateId';

        $parameters = $this->parameters;
        $parameters['nodeAggregateId'] = (string)$nodeAggregateId;

        return new self($query, $parameters, $this->tableNamePrefix);
    }

    public function withRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, $this->tableNamePrefix, '');

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }
}

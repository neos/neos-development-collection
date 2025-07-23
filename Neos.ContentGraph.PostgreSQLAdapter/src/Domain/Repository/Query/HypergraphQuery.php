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
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal
 */
final class HypergraphQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    public static function create(
        ContentStreamId $contentStreamId,
        ContentGraphTableNames $tableNames,
        bool $joinSubTreeTags = false
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT n.origindimensionspacepoint, n.nodeaggregateid,
                n.nodetypename, n.classification, n.properties, n.nodename,
                h.contentstreamid, h.dimensionspacepoint' . ($joinSubTreeTags ? ',
                r.dimensionspacepointhash AS disabledDimensionSpacePointHash' : '') . '
            FROM ' . $tableNames->hierarchyRelation() . ' h
            JOIN ' . $tableNames->node() . ' n ON n.relationanchorpoint = ANY(h.childnodeanchors)'
            . ($joinSubTreeTags
                ? '
            LEFT JOIN ' . $tableNames->subTreeTagsRelation() . ' r
                ON n.nodeaggregateid = r.originnodeaggregateid
                AND r.contentstreamid = h.contentstreamid
                AND r.dimensionspacepointhash = h.dimensionspacepointhash'
                : '')
            . '
            WHERE h.contentstreamid = :contentStreamId';

        $parameters = [
            'contentStreamId' => $contentStreamId->value
        ];

        return new self($query, $parameters, $tableNames);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->hash;

        return new self($query, $parameters, $this->tableNames);
    }

    public function withOriginDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND n.origindimensionspacepointhash = :originDimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['originDimensionSpacePointHash'] = $originDimensionSpacePoint->hash;

        return new self($query, $parameters, $this->tableNames);
    }

    public function withNodeAggregateId(NodeAggregateId $nodeAggregateId): self
    {
        $query = $this->query .= '
            AND n.nodeaggregateid = :nodeAggregateId';

        $parameters = $this->parameters;
        $parameters['nodeAggregateId'] = $nodeAggregateId->value;

        return new self($query, $parameters, $this->tableNames);
    }

    public function withNodeTypeName(NodeTypeName $nodeTypeName): self
    {
        $query = $this->query .= '
            AND n.nodetypename = :nodeTypeName';

        $parameters = $this->parameters;
        $parameters['nodeTypeName'] = $nodeTypeName->value;

        return new self($query, $parameters, $this->tableNames);
    }

    public function withClassification(NodeAggregateClassification $nodeAggregateClassification): self
    {
        $query = $this->query .= '
            AND n.classification = :nodeAggregateClassification';

        $parameters = $this->parameters;
        $parameters['nodeAggregateClassification'] = $nodeAggregateClassification->value;

        return new self($query, $parameters, $this->tableNames);
    }

    public function withRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, $this->tableNames, '');

        return new self($query, $this->parameters, $this->tableNames, $this->types);
    }
}

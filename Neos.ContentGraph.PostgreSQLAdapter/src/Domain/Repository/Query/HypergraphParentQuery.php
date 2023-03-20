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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal
 */
final class HypergraphParentQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    /**
     * @param array<int,string>|null $fieldsToFetch
     */
    public static function create(
        ContentStreamId $contentStreamId,
        string $tableNamePrefix,
        ?array $fieldsToFetch = null
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT ' . ($fieldsToFetch
                ? implode(', ', $fieldsToFetch)
                : 'pn.origindimensionspacepoint, pn.nodeaggregateid, pn.nodetypename,
                    pn.classification, pn.properties, pn.nodename,
                    ph.contentstreamid, ph.dimensionspacepoint') . '
            FROM ' . $tableNamePrefix . '_hierarchyhyperrelation ph
            JOIN ' . $tableNamePrefix . '_node pn ON pn.relationanchorpoint = ANY(ph.childnodeanchors)
            JOIN ' . $tableNamePrefix . '_hierarchyhyperrelation ch ON ch.parentnodeanchor = pn.relationanchorpoint
            JOIN ' . $tableNamePrefix . '_node cn ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
            WHERE ph.contentstreamid = :contentStreamId
                AND ch.contentstreamid = :contentStreamId';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId
        ];

        return new self($query, $parameters, $tableNamePrefix);
    }

    public function withChildNodeAggregateId(NodeAggregateId $nodeAggregateId): self
    {
        $query = $this->query .= '
            AND cn.nodeaggregateid = :nodeAggregateId';

        $parameters = $this->parameters;
        $parameters['nodeAggregateId'] = (string)$nodeAggregateId;

        return new self($query, $parameters, $this->tableNamePrefix);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND ph.dimensionspacepointhash = :dimensionSpacePointHash
            AND ch.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->hash;

        return new self($query, $parameters, $this->tableNamePrefix);
    }

    public function withRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, $this->tableNamePrefix, 'c');

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }
}

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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

/**
 * @internal
 */
final class HypergraphChildQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    /**
     * @param array<int,string>|null $fieldsToFetch
     */
    public static function create(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        string $tableNamePrefix,
        ?array $fieldsToFetch = null
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT ' . ($fieldsToFetch
                ? implode(', ', $fieldsToFetch)
                : 'cn.origindimensionspacepoint, cn.nodeaggregateid, cn.nodetypename,
                    cn.classification, cn.properties, cn.nodename,
                    ch.contentstreamid, ch.dimensionspacepoint') . '
            FROM ' . $tableNamePrefix . '_node pn
            JOIN (
                SELECT *, unnest(childnodeanchors) AS childnodeanchor
                FROM ' . $tableNamePrefix . '_hierarchyhyperrelation
            ) ch ON ch.parentnodeanchor = pn.relationanchorpoint
            JOIN ' . $tableNamePrefix . '_node cn ON cn.relationanchorpoint = ch.childnodeanchor
            WHERE ch.contentstreamid = :contentStreamId
                AND pn.nodeaggregateid = :parentNodeAggregateId';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'parentNodeAggregateId' => (string)$parentNodeAggregateId
        ];

        return new self($query, $parameters, $tableNamePrefix);
    }

    public function withOriginDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND pn.origindimensionspacepointhash = :originDimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['originDimensionSpacePointHash'] = $originDimensionSpacePoint->hash;

        return new self($query, $parameters, $this->tableNamePrefix);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND ch.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->hash;

        return new self($query, $parameters, $this->tableNamePrefix);
    }

    public function withDimensionSpacePoints(DimensionSpacePointSet $dimensionSpacePoints): self
    {
        $query = $this->query .= '
            AND ch.dimensionspacepointhash IN (:dimensionSpacePointHashes)';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHashes'] = $dimensionSpacePoints->getPointHashes();
        $types = $this->types;
        $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;

        return new self($query, $parameters, $this->tableNamePrefix, $types);
    }

    public function withChildNodeName(NodeName $childNodeName): self
    {
        $query = $this->query . '
            AND cn.nodename = :childNodeName';

        $parameters = $this->parameters;
        $parameters['childNodeName'] = (string)$childNodeName;

        return new self($query, $parameters, $this->tableNamePrefix, $this->types);
    }

    public function withRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, $this->tableNamePrefix, 'c');

        return new self($query, $this->parameters, $this->tableNamePrefix, $this->types);
    }

    public function withOnlyTethered(): self
    {
        $query = $this->query . '
            AND cn.classification = :classification';

        $parameters = $this->parameters;
        $parameters['classification'] = NodeAggregateClassification::CLASSIFICATION_TETHERED->value;

        return new self($query, $parameters, $this->tableNamePrefix, $this->types);
    }
}

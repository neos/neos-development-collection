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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

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
        ContentStreamIdentifier $contentStreamIdentifier,
        string $tableNamePrefix,
        ?array $fieldsToFetch = null
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT ' . ($fieldsToFetch
                ? implode(', ', $fieldsToFetch)
                : 'pn.origindimensionspacepoint, pn.nodeaggregateidentifier, pn.nodetypename,
                    pn.classification, pn.properties, pn.nodename,
                    ph.contentstreamidentifier, ph.dimensionspacepoint') . '
            FROM ' . $tableNamePrefix . '_hierarchyhyperrelation ph
            JOIN ' . $tableNamePrefix . '_node pn ON pn.relationanchorpoint = ANY(ph.childnodeanchors)
            JOIN ' . $tableNamePrefix . '_hierarchyhyperrelation ch ON ch.parentnodeanchor = pn.relationanchorpoint
            JOIN ' . $tableNamePrefix . '_node cn ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
            WHERE ph.contentstreamidentifier = :contentStreamIdentifier
                AND ch.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        return new self($query, $parameters, $tableNamePrefix);
    }

    public function withChildNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        $query = $this->query .= '
            AND cn.nodeaggregateidentifier = :nodeAggregateIdentifier';

        $parameters = $this->parameters;
        $parameters['nodeAggregateIdentifier'] = (string)$nodeAggregateIdentifier;

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
}

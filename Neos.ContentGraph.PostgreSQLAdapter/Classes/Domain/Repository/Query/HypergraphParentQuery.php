<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class HypergraphParentQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    /**
     * @param array<int,string>|null $fieldsToFetch
     */
    public static function create(ContentStreamIdentifier $contentStreamIdentifier, ?array $fieldsToFetch = null): self
    {
        $query = /** @lang PostgreSQL */
            'SELECT ' . ($fieldsToFetch
                ? implode(', ', $fieldsToFetch)
                : 'pn.origindimensionspacepoint, pn.nodeaggregateidentifier, pn.nodetypename,
                    pn.classification, pn.properties, pn.nodename,
                    ph.contentstreamidentifier, ph.dimensionspacepoint') . '
            FROM ' . HierarchyHyperrelationRecord::TABLE_NAME . ' ph
            JOIN ' . NodeRecord::TABLE_NAME . ' pn ON pn.relationanchorpoint = ANY(ph.childnodeanchors)
            JOIN ' . HierarchyHyperrelationRecord::TABLE_NAME . ' ch ON ch.parentnodeanchor = pn.relationanchorpoint
            JOIN ' . NodeRecord::TABLE_NAME . ' cn ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
            WHERE ph.contentstreamidentifier = :contentStreamIdentifier
                AND ch.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        return new self($query, $parameters);
    }

    public function withChildNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        $query = $this->query .= '
            AND cn.nodeaggregateidentifier = :nodeAggregateIdentifier';

        $parameters = $this->parameters;
        $parameters['nodeAggregateIdentifier'] = (string)$nodeAggregateIdentifier;

        return new self($query, $parameters);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND ph.dimensionspacepointhash = :dimensionSpacePointHash
            AND ch.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->hash;

        return new self($query, $parameters);
    }
}

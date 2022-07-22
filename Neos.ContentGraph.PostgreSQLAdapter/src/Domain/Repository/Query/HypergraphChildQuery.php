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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class HypergraphChildQuery implements HypergraphQueryInterface
{
    use CommonGraphQueryOperations;

    /**
     * @param array<int,string>|null $fieldsToFetch
     */
    public static function create(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        ?array $fieldsToFetch = null
    ): self {
        $query = /** @lang PostgreSQL */
            'SELECT ' . ($fieldsToFetch
                ? implode(', ', $fieldsToFetch)
                : 'cn.origindimensionspacepoint, cn.nodeaggregateidentifier, cn.nodetypename,
                    cn.classification, cn.properties, cn.nodename,
                    ch.contentstreamidentifier, ch.dimensionspacepoint') . '
            FROM ' . NodeRecord::TABLE_NAME . ' pn
            JOIN (
                SELECT *, unnest(childnodeanchors) AS childnodeanchor
                FROM ' . HierarchyHyperrelationRecord::TABLE_NAME . '
            ) ch ON ch.parentnodeanchor = pn.relationanchorpoint
            JOIN ' . NodeRecord::TABLE_NAME . ' cn ON cn.relationanchorpoint = ch.childnodeanchor
            WHERE ch.contentstreamidentifier = :contentStreamIdentifier
                AND pn.nodeaggregateidentifier = :parentNodeAggregateIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier
        ];

        return new self($query, $parameters);
    }

    public function withOriginDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND pn.origindimensionspacepointhash = :originDimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['originDimensionSpacePointHash'] = $originDimensionSpacePoint->hash;

        return new self($query, $parameters);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND ch.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->hash;

        return new self($query, $parameters);
    }

    public function withDimensionSpacePoints(DimensionSpacePointSet $dimensionSpacePoints): self
    {
        $query = $this->query .= '
            AND ch.dimensionspacepointhash IN (:dimensionSpacePointHashes)';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHashes'] = $dimensionSpacePoints->getPointHashes();
        $types = $this->types;
        $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;

        return new self($query, $parameters, $types);
    }

    public function withChildNodeName(NodeName $childNodeName): self
    {
        $query = $this->query . '
            AND cn.nodename = :childNodeName';

        $parameters = $this->parameters;
        $parameters['childNodeName'] = (string)$childNodeName;

        return new self($query, $parameters, $this->types);
    }

    public function withRestriction(VisibilityConstraints $visibilityConstraints): self
    {
        $query = $this->query . QueryUtility::getRestrictionClause($visibilityConstraints, 'c');

        return new self($query, $this->parameters, $this->types);
    }

    public function withOnlyTethered(): self
    {
        $query = $this->query . '
            AND cn.classification = :classification';

        $parameters = $this->parameters;
        $parameters['classification'] = NodeAggregateClassification::CLASSIFICATION_TETHERED;

        return new self($query, $parameters, $this->types);
    }
}

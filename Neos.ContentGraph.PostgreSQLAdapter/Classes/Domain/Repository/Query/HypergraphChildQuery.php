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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Types\Types;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class HypergraphChildQuery implements HypergraphQueryInterface
{
    private string $query;

    private array $parameters;

    private array $types;

    private function __construct($query, $parameters, $types = [])
    {
        $this->query = $query;
        $this->parameters = $parameters;
        $this->types = $types;
    }

    public static function create(ContentStreamIdentifier $contentStreamIdentifier, array $fieldsToFetch = null): self
    {
        $query = /** @lang PostgreSQL */
            'SELECT ' . ($fieldsToFetch
                ? implode(', ', $fieldsToFetch)
                : 'cn.origindimensionspacepoint, cn.nodeaggregateidentifier, cn.nodetypename, cn.classification, cn.properties, cn.nodename,
                    ch.contentstreamidentifier, ch.dimensionspacepoint') . '
            FROM ' . HierarchyHyperrelationRecord::TABLE_NAME .' ph
            JOIN ' . NodeRecord::TABLE_NAME .' pn ON ph.childnodeanchors @> jsonb_build_array(pn.relationanchorpoint)::jsonb
            JOIN ' . HierarchyHyperrelationRecord::TABLE_NAME .' ch ON ch.parentnodeanchor = pn.relationanchorpoint
            JOIN ' . NodeRecord::TABLE_NAME .' cn ON ch.childnodeanchors @> jsonb_build_array(cn.relationanchorpoint)::jsonb
            WHERE ph.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        return new self($query, $parameters);
    }

    public function withNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        $query = $this->query .= '
            AND pn.nodeaggregateidentifier = :nodeAggregateIdentifier';

        $parameters = $this->parameters;
        $parameters['nodeAggregateIdentifier'] = (string)$nodeAggregateIdentifier;

        return new self($query, $parameters);
    }

    public function withOriginDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND pn.origindimensionspacepointhash = :originDimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['originDimensionSpacePointHash'] = $originDimensionSpacePoint->getHash();

        return new self($query, $parameters);
    }

    public function withNodeName(NodeName $nodeName): self
    {
        $query = $this->query .= '
            AND cn.nodename = :nodeName';

        $parameters = $this->parameters;
        $parameters['nodeName'] = (string)$nodeName;

        return new self($query, $parameters);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND ch.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->getHash();

        return new self($query, $parameters);
    }

    public function withDimensionSpacePoints(DimensionSpacePointSet $dimensionSpacePoints): self
    {
        $query = $this->query .= '
            AND ch.dimensionspacepointhash IN (:dimensionSpacePointHashes)';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHashes'] = $dimensionSpacePoints->getPointHashes();
        $types = $this->types;
        $types['dimensionSpacePointHashes'] = Types::SIMPLE_ARRAY;

        return new self($query, $parameters);
    }

    public function execute(Connection $databaseConnection): ResultStatement
    {
        return $databaseConnection->executeQuery($this->query, $this->parameters);
    }
}

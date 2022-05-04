<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Query;

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
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class ProjectionHypergraphQuery implements ProjectionHypergraphQueryInterface
{
    private string $query;

    /**
     * @var array<string,mixed>
     */
    private array $parameters;

    /**
     * @var array<string,string>
     */
    private array $types;

    /**
     * @param array<string,mixed> $parameters
     * @param array<string,string> $types
     */
    private function __construct(string $query, array $parameters, array $types)
    {
        $this->query = $query;
        $this->parameters = $parameters;
        $this->types = $types;
    }

    public static function create(ContentStreamIdentifier $contentStreamIdentifier): self
    {
        $query = /** @lang PostgreSQL */
            'SELECT n.*
            FROM ' . HierarchyHyperrelationRecord::TABLE_NAME .' h
            JOIN ' . NodeRecord::TABLE_NAME .' n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        return new self($query, $parameters, []);
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHash'] = $dimensionSpacePoint->hash;

        return new self($query, $parameters, $this->types);
    }

    public function withDimensionSpacePoints(DimensionSpacePointSet $dimensionSpacePoints): self
    {
        $query = $this->query .= '
            AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';

        $parameters = $this->parameters;
        $parameters['dimensionSpacePointHashes'] = $dimensionSpacePoints->getPointHashes();
        $types = $this->types;
        $types['dimensionSpacePointHashes'] = Types::SIMPLE_ARRAY;

        return new self($query, $parameters, $types);
    }

    public function withOriginDimensionSpacePoint(OriginDimensionSpacePoint $originDimensionSpacePoint): self
    {
        $query = $this->query .= '
            AND n.origindimensionspacepointhash = :originDimensionSpacePointHash';

        $parameters = $this->parameters;
        $parameters['originDimensionSpacePointHash'] = $originDimensionSpacePoint->hash;

        return new self($query, $parameters, $this->types);
    }

    public function withNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        $query = $this->query .= '
            AND n.nodeaggregateidentifier = :nodeAggregateIdentifier';

        $parameters = $this->parameters;
        $parameters['nodeAggregateIdentifier'] = (string)$nodeAggregateIdentifier;

        return new self($query, $parameters, $this->types);
    }

    /**
     * @return ResultStatement<int,mixed>
     */
    public function execute(Connection $databaseConnection): ResultStatement
    {
        return $databaseConnection->executeQuery($this->query, $this->parameters, $this->types);
    }
}

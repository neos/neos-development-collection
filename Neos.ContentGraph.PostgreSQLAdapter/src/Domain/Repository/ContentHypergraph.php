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

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphChildQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphParentQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Projection\ContentGraph\Node;

/**
 * The PostgreSQL adapter content hypergraph
 *
 * To be used as a read-only source of subhypergraphs, node aggregates and nodes
 *
 * @api
 */
final class ContentHypergraph implements ContentGraphInterface
{
    private PostgresDbalClientInterface $databaseClient;

    private NodeFactory $nodeFactory;

    /**
     * @var array|ContentSubhypergraph[]
     */
    private array $subhypergraphs;

    public function __construct(
        PostgresDbalClientInterface $databaseClient,
        NodeFactory $nodeFactory,
        private readonly string $tableNamePrefix
    ) {
        $this->databaseClient = $databaseClient;
        $this->nodeFactory = $nodeFactory;
    }

    public function getSubgraph(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $contentStreamIdentifier . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subhypergraphs[$index])) {
            $this->subhypergraphs[$index] = new ContentSubhypergraph(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $visibilityConstraints,
                $this->databaseClient,
                $this->nodeFactory,
                $this->tableNamePrefix
            );
        }

        return $this->subhypergraphs[$index];
    }

    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?Node {
        $query = HypergraphQuery::create($contentStreamIdentifier, $this->tableNamePrefix);
        $query = $query->withOriginDimensionSpacePoint($originDimensionSpacePoint);
        $query = $query->withNodeAggregateIdentifier($nodeAggregateIdentifier);

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            VisibilityConstraints::withoutRestrictions(),
            $originDimensionSpacePoint->toDimensionSpacePoint()
        ) : null;
    }

    public function findRootNodeAggregateByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        throw new \BadMethodCallException('method findRootNodeAggregateByType is not implemented yet.', 1645782874);
    }

    /**
     * @return \Iterator<int,NodeAggregate>
     */
    public function findNodeAggregatesByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): \Iterator {
        return new \Generator();
    }

    public function findNodeAggregateByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ?NodeAggregate {
        $query = HypergraphQuery::create($contentStreamIdentifier, $this->tableNamePrefix, true);
        $query = $query->withNodeAggregateIdentifier($nodeAggregateIdentifier);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $query = /** @lang PostgreSQL */ '
            SELECT n.origindimensionspacepoint, n.nodeaggregateidentifier, n.nodetypename,
                   n.classification, n.properties, n.nodename, ph.contentstreamidentifier, ph.dimensionspacepoint
                FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation ph
                JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(ph.childnodeanchors)
            WHERE ph.contentstreamidentifier = :contentStreamIdentifier
                AND n.nodeaggregateidentifier = (
                    SELECT pn.nodeaggregateidentifier
                        FROM ' . $this->tableNamePrefix . '_node pn
                        JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation ch
                            ON pn.relationanchorpoint = ch.parentnodeanchor
                        JOIN ' . $this->tableNamePrefix . '_node cn ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
                    WHERE cn.nodeaggregateidentifier = :childNodeAggregateIdentifier
                        AND cn.origindimensionspacepointhash = :childOriginDimensionSpacePointHash
                        AND ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash
                        AND ch.contentstreamidentifier = :contentStreamIdentifier
                )';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateIdentifier,
            'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash
        ];

        $nodeRows = $this->getDatabaseConnection()->executeQuery(
            $query,
            $parameters
        )->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier
    ): iterable {
        $query = HypergraphParentQuery::create($contentStreamIdentifier, $this->tableNamePrefix);
        $query = $query->withChildNodeAggregateIdentifier($childNodeAggregateIdentifier);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): iterable {
        $query = HypergraphChildQuery::create(
            $contentStreamIdentifier,
            $parentNodeAggregateIdentifier,
            $this->tableNamePrefix
        );

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregatesByName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $name
    ): iterable {
        $query = HypergraphChildQuery::create(
            $contentStreamIdentifier,
            $parentNodeAggregateIdentifier,
            $this->tableNamePrefix
        );
        $query = $query->withChildNodeName($name);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     */
    public function findTetheredChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): iterable {
        $query = HypergraphChildQuery::create(
            $contentStreamIdentifier,
            $parentNodeAggregateIdentifier,
            $this->tableNamePrefix
        );
        $query = $query->withOnlyTethered();

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows, VisibilityConstraints::withoutRestrictions());
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        $query = HypergraphChildQuery::create(
            $contentStreamIdentifier,
            $parentNodeAggregateIdentifier,
            $this->tableNamePrefix,
            ['ch.dimensionspacepoint, ch.dimensionspacepointhash']
        );
        $query = $query->withChildNodeName($nodeName)
            ->withOriginDimensionSpacePoint($parentNodeOriginDimensionSpacePoint)
            ->withDimensionSpacePoints($dimensionSpacePointsToCheck);

        $occupiedDimensionSpacePoints = [];
        foreach ($query->execute($this->getDatabaseConnection())->fetchAllAssociative() as $row) {
            $occupiedDimensionSpacePoints[$row['dimensionspacepointhash']]
                = DimensionSpacePoint::fromJsonString($row['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($occupiedDimensionSpacePoints);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function countNodes(): int
    {
        $query = 'SELECT COUNT(*) FROM ' . $this->tableNamePrefix . '_node';

        return $this->getDatabaseConnection()->executeQuery($query)->fetchOne();
    }

    /**
     * @return iterable<int,NodeTypeName>
     */
    public function findUsedNodeTypeNames(): iterable
    {
        return [];
    }

    private function getDatabaseConnection(): DatabaseConnection
    {
        return $this->databaseClient->getConnection();
    }
}

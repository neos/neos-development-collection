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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * The PostgreSQL adapter content hypergraph
 *
 * To be used as a read-only source of subhypergraphs, node aggregates and nodes
 *
 * @internal but the parent {@see ContentGraphInterface} is API
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
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
        $this->databaseClient = $databaseClient;
        $this->nodeFactory = $nodeFactory;
    }

    public function getSubgraph(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $contentStreamId . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subhypergraphs[$index])) {
            $this->subhypergraphs[$index] = new ContentSubhypergraph(
                $contentStreamId,
                $dimensionSpacePoint,
                $visibilityConstraints,
                $this->databaseClient,
                $this->nodeFactory,
                $this->nodeTypeManager,
                $this->tableNamePrefix
            );
        }

        return $this->subhypergraphs[$index];
    }

    public function findNodeByIdAndOriginDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?Node {
        $query = HypergraphQuery::create($contentStreamId, $this->tableNamePrefix);
        $query = $query->withOriginDimensionSpacePoint($originDimensionSpacePoint);
        $query = $query->withNodeAggregateId($nodeAggregateId);

        $nodeRow = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            VisibilityConstraints::withoutRestrictions(),
            $originDimensionSpacePoint->toDimensionSpacePoint()
        ) : null;
    }

    public function findRootNodeAggregateByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        $rootNodeAggregates = $this->findRootNodeAggregates(
            $contentStreamId,
            FindRootNodeAggregatesFilter::nodeTypeName($nodeTypeName)
        );

        if ($rootNodeAggregates->count() > 1) {
            $ids = [];
            foreach ($rootNodeAggregates as $rootNodeAggregate) {
                $ids[] = $rootNodeAggregate->nodeAggregateId->value;
            }
            throw new \RuntimeException(sprintf(
                'More than one root node aggregate of type "%s" found (IDs: %s).',
                $nodeTypeName->value,
                implode(', ', $ids)
            ));
        }

        $rootNodeAggregate = $rootNodeAggregates->first();

        if ($rootNodeAggregate === null) {
            throw new \RuntimeException('Root Node Aggregate not found');
        }

        return $rootNodeAggregate;
    }

    public function findRootNodeAggregates(
        ContentStreamId $contentStreamId,
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        throw new \BadMethodCallException('method findRootNodeAggregates is not implemented yet.', 1645782874);
    }

    /**
     * @return \Iterator<int,NodeAggregate>
     */
    public function findNodeAggregatesByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): \Iterator {
        return new \Generator();
    }

    public function findNodeAggregateById(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $query = HypergraphQuery::create($contentStreamId, $this->tableNamePrefix, true);
        $query = $query->withNodeAggregateId($nodeAggregateId);

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $query = /** @lang PostgreSQL */ '
            SELECT n.origindimensionspacepoint, n.nodeaggregateid, n.nodetypename,
                   n.classification, n.properties, n.nodename, ph.contentstreamid, ph.dimensionspacepoint
                FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation ph
                JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(ph.childnodeanchors)
            WHERE ph.contentstreamid = :contentStreamId
                AND n.nodeaggregateid = (
                    SELECT pn.nodeaggregateid
                        FROM ' . $this->tableNamePrefix . '_node pn
                        JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation ch
                            ON pn.relationanchorpoint = ch.parentnodeanchor
                        JOIN ' . $this->tableNamePrefix . '_node cn ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
                    WHERE cn.nodeaggregateid = :childNodeAggregateId
                        AND cn.origindimensionspacepointhash = :childOriginDimensionSpacePointHash
                        AND ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash
                        AND ch.contentstreamid = :contentStreamId
                )';
        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'childNodeAggregateId' => (string)$childNodeAggregateId,
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
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId
    ): iterable {
        $query = HypergraphParentQuery::create($contentStreamId, $this->tableNamePrefix);
        $query = $query->withChildNodeAggregateId($childNodeAggregateId);

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
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $query = HypergraphChildQuery::create(
            $contentStreamId,
            $parentNodeAggregateId,
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
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): iterable {
        $query = HypergraphChildQuery::create(
            $contentStreamId,
            $parentNodeAggregateId,
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
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $query = HypergraphChildQuery::create(
            $contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix
        );
        $query = $query->withOnlyTethered();

        $nodeRows = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows, VisibilityConstraints::withoutRestrictions());
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamId $contentStreamId,
        NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        $query = HypergraphChildQuery::create(
            $contentStreamId,
            $parentNodeAggregateId,
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

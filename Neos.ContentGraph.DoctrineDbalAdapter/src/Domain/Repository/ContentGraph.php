<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @internal the parent interface {@see ContentGraphInterface} is API
 */
final class ContentGraph implements ContentGraphInterface
{
    /**
     * @var array<string,ContentSubgraph>
     */
    private array $subgraphs = [];

    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix
    ) {
    }

    final public function getSubgraph(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $contentStreamId . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraph(
                $contentStreamId,
                $dimensionSpacePoint,
                $visibilityConstraints,
                $this->client,
                $this->nodeFactory,
                $this->nodeTypeManager,
                $this->tableNamePrefix
            );
        }

        return $this->subgraphs[$index];
    }

    /**
     * @throws DBALException
     * @throws NodeTypeNotFoundException
     */
    public function findNodeByIdAndOriginDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?Node {
        $connection = $this->client->getConnection();

        // HINT: we check the ContentStreamId on the EDGE;
        // as this is where we actually find out whether the node exists in the content stream
        $nodeRow = $connection->executeQuery(
            'SELECT n.*, h.contentstreamid, h.name FROM ' . $this->tableNamePrefix . '_node n
                  INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                      ON h.childnodeanchor = n.relationanchorpoint
                  WHERE n.nodeaggregateid = :nodeAggregateId
                  AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
                  AND h.contentstreamid = :contentStreamId',
            [
                'nodeAggregateId' => (string)$nodeAggregateId,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'contentStreamId' => (string)$contentStreamId
            ]
        )->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $originDimensionSpacePoint->toDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        ) : null;
    }

    /**
     * @throws \Exception
     */
    public function findRootNodeAggregateByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamid, h.name, h.dimensionspacepoint AS covereddimensionspacepoint
                    FROM ' . $this->tableNamePrefix . '_node n
                        JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                            ON h.childnodeanchor = n.relationanchorpoint
                    WHERE h.contentstreamid = :contentStreamId
                        AND h.parentnodeanchor = :rootEdgeParentAnchorId
                        AND n.nodetypename = :nodeTypeName';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'rootEdgeParentAnchorId' => (string)NodeRelationAnchorPoint::forRootEdge(),
            'nodeTypeName' => (string)$nodeTypeName,
        ];

        $nodeRow = $connection->executeQuery($query, $parameters)->fetchAssociative();

        if (!is_array($nodeRow)) {
            throw new \RuntimeException('Root Node Aggregate not found');
        }

        /** @var NodeAggregate $nodeAggregate The factory will return a NodeAggregate since the array is not empty */
        $nodeAggregate = $this->nodeFactory->mapNodeRowsToNodeAggregate(
            [$nodeRow],
            VisibilityConstraints::withoutRestrictions()
        );

        return $nodeAggregate;
    }

    public function findNodeAggregatesByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): iterable {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.contentstreamid, h.name, h.dimensionspacepoint AS covereddimensionspacepoint
                FROM ' . $this->tableNamePrefix . '_node n
                    JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint
                WHERE h.contentstreamid = :contentStreamId
                    AND n.nodetypename = :nodeTypeName';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'nodeTypeName' => (string)$nodeTypeName,
        ];

        $resultStatement = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $resultStatement,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @throws DBALException
     * @throws \Exception
     */
    public function findNodeAggregateById(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*,
                      h.name, h.contentstreamid, h.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                  FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                      JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = h.childnodeanchor
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateid = n.nodeaggregateid
                          AND r.contentstreamid = h.contentstreamid
                          AND r.affectednodeaggregateid = n.nodeaggregateid
                          AND r.dimensionspacepointhash = h.dimensionspacepointhash
                      WHERE n.nodeaggregateid = :nodeAggregateId
                      AND h.contentstreamid = :contentStreamId';
        $parameters = [
            'nodeAggregateId' => (string)$nodeAggregateId,
            'contentStreamId' => (string)$contentStreamId
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException
     * @throws \Exception
     */
    public function findParentNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId
    ): iterable {
        $connection = $this->client->getConnection();

        $query = 'SELECT p.*,
                      ph.name, ph.contentstreamid, ph.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_node p
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
                        ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                        ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_node c ON ch.childnodeanchor = c.relationanchorpoint
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateid = p.nodeaggregateid
                          AND r.contentstreamid = ph.contentstreamid
                          AND r.affectednodeaggregateid = p.nodeaggregateid
                          AND r.dimensionspacepointhash = ph.dimensionspacepointhash
                      WHERE c.nodeaggregateid = :nodeAggregateId
                      AND ph.contentstreamid = :contentStreamId
                      AND ch.contentstreamid = :contentStreamId';
        $parameters = [
            'nodeAggregateId' => (string)$childNodeAggregateId,
            'contentStreamId' => (string)$contentStreamId
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @throws DBALException
     * @throws \Exception
     */
    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*,
                      h.name, h.contentstreamid, h.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_node n
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                          ON h.childnodeanchor = n.relationanchorpoint
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateid = n.nodeaggregateid
                          AND r.contentstreamid = h.contentstreamid
                          AND r.affectednodeaggregateid = n.nodeaggregateid
                          AND r.dimensionspacepointhash = h.dimensionspacepointhash
                      WHERE n.nodeaggregateid = (
                          SELECT p.nodeaggregateid FROM ' . $this->tableNamePrefix . '_node p
                          INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                              ON ch.parentnodeanchor = p.relationanchorpoint
                          INNER JOIN ' . $this->tableNamePrefix . '_node c
                              ON ch.childnodeanchor = c.relationanchorpoint
                          WHERE ch.contentstreamid = :contentStreamId
                          AND ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash
                          AND c.nodeaggregateid = :childNodeAggregateId
                          AND c.origindimensionspacepointhash = :childOriginDimensionSpacePointHash
                      )
                      AND h.contentstreamid = :contentStreamId';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'childNodeAggregateId' => (string)$childNodeAggregateId,
            'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash,
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findParentNodeAggregateByChildDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        DimensionSpacePoint $childDimensionSpacePoint
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*,
                      h.name, h.contentstreamidentifier, h.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM neos_contentgraph_node n
                      JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                      LEFT JOIN neos_contentgraph_restrictionrelation r
                          ON r.originnodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.contentstreamidentifier = h.contentstreamidentifier
                          AND r.affectednodeaggregateidentifier = n.nodeaggregateidentifier
                          AND r.dimensionspacepointhash = h.dimensionspacepointhash
                      WHERE n.nodeaggregateidentifier = (
                          SELECT p.nodeaggregateidentifier FROM neos_contentgraph_node p
                          INNER JOIN neos_contentgraph_hierarchyrelation ch
                              ON ch.parentnodeanchor = p.relationanchorpoint
                          INNER JOIN neos_contentgraph_node c ON ch.childnodeanchor = c.relationanchorpoint
                          WHERE ch.contentstreamidentifier = :contentStreamId
                          AND ch.dimensionspacepointhash = :childDimensionSpacePointHash
                          AND c.nodeaggregateidentifier = :childNodeAggregateId
                      )
                      AND h.contentstreamidentifier = :contentStreamId';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'childNodeAggregateId' => (string)$childNodeAggregateId,
            'childDimensionSpacePointHash' => $childDimensionSpacePoint->hash,
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException|\Exception
     */
    public function findChildNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery();

        $parameters = [
            'parentNodeAggregateId' => (string) $parentNodeAggregateId,
            'contentStreamId' => (string) $contentStreamId
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException|NodeTypeNotFoundException
     */
    public function findChildNodeAggregatesByName(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery() . '
                      AND ch.name = :relationName';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'parentNodeAggregateId' => (string)$parentNodeAggregateId,
            'relationName' => (string)$name
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    /**
     * @return iterable<NodeAggregate>
     * @throws DBALException|NodeTypeNotFoundException
     */
    public function findTetheredChildNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId
    ): iterable {
        $connection = $this->client->getConnection();

        $query = $this->createChildNodeAggregateQuery() . '
                      AND c.classification = :tetheredClassification';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'parentNodeAggregateId' => (string)$parentNodeAggregateId,
            'tetheredClassification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    private function createChildNodeAggregateQuery(): string
    {
        return 'SELECT c.*,
                      ch.name, ch.contentstreamid, ch.dimensionspacepoint AS covereddimensionspacepoint,
                      r.dimensionspacepointhash AS disableddimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_node p
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
                          ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch
                          ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN ' . $this->tableNamePrefix . '_node c
                          ON ch.childnodeanchor = c.relationanchorpoint
                      LEFT JOIN ' . $this->tableNamePrefix . '_restrictionrelation r
                          ON r.originnodeaggregateid = p.nodeaggregateid
                          AND r.contentstreamid = ph.contentstreamid
                          AND r.affectednodeaggregateid = p.nodeaggregateid
                          AND r.dimensionspacepointhash = ph.dimensionspacepointhash
                      WHERE p.nodeaggregateid = :parentNodeAggregateId
                      AND ph.contentstreamid = :contentStreamId
                      AND ch.contentstreamid = :contentStreamId';
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeName $nodeName
     * @param NodeAggregateId $parentNodeAggregateId
     * @param OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint
     * @param DimensionSpacePointSet $dimensionSpacePointsToCheck
     * @return DimensionSpacePointSet
     * @throws DBALException
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamId $contentStreamId,
        NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        $connection = $this->client->getConnection();

        $query = 'SELECT h.dimensionspacepoint, h.dimensionspacepointhash
                      FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                      INNER JOIN ' . $this->tableNamePrefix . '_node n
                          ON h.parentnodeanchor = n.relationanchorpoint
                      INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph
                          ON ph.childnodeanchor = n.relationanchorpoint
                      WHERE n.nodeaggregateid = :parentNodeAggregateId
                      AND n.origindimensionspacepointhash = :parentNodeOriginDimensionSpacePointHash
                      AND ph.contentstreamid = :contentStreamId
                      AND h.contentstreamid = :contentStreamId
                      AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)
                      AND h.name = :nodeName';
        $parameters = [
            'parentNodeAggregateId' => (string)$parentNodeAggregateId,
            'parentNodeOriginDimensionSpacePointHash' => $parentNodeOriginDimensionSpacePoint->hash,
            'contentStreamId' => (string) $contentStreamId,
            'dimensionSpacePointHashes' => $dimensionSpacePointsToCheck->getPointHashes(),
            'nodeName' => (string) $nodeName
        ];
        $types = [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];
        $dimensionSpacePoints = [];
        foreach (
            $connection->executeQuery($query, $parameters, $types)
                ->fetchAllAssociative() as $hierarchyRelationData
        ) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']]
                = DimensionSpacePoint::fromJsonString($hierarchyRelationData['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    public function countNodes(): int
    {
        $connection = $this->client->getConnection();
        $query = 'SELECT COUNT(*) FROM ' . $this->tableNamePrefix . '_node';

        $row = $connection->executeQuery($query)->fetchAssociative();

        return $row ? (int)$row['COUNT(*)'] : 0;
    }

    public function findUsedNodeTypeNames(): iterable
    {
        $connection = $this->client->getConnection();

        $rows = $connection->executeQuery('SELECT DISTINCT nodetypename FROM ' . $this->tableNamePrefix . '_node')
            ->fetchAllAssociative();

        return array_map(function (array $row) {
            return NodeTypeName::fromString($row['nodetypename']);
        }, $rows);
    }

    /**
     * @return ContentSubgraph[]
     * @internal only used for {@see DoctrineDbalContentGraphProjection}
     */
    public function getSubgraphs(): array
    {
        return $this->subgraphs;
    }
}

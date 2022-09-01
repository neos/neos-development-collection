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
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * The read only content graph for use by the {@see GraphProjector}. This is the class for low-level operations
 * within the projector, where implementation details of the graph structure are known.
 *
 * This is NO PUBLIC API in any way.
 *
 * @internal
 */
class ProjectionContentGraph
{
    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly string $tableNamePrefix
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $childNodeAggregateId
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint
     * @return NodeRecord|null
     * @throws DBALException
     * @throws \Exception
     */
    public function findParentNode(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeRecord {
        $params = [
            'contentStreamId' => (string)$contentStreamId,
            'childNodeAggregateId' => (string)$childNodeAggregateId,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash
        ];
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT p.*, ph.contentstreamid, ph.name FROM ' . $this->tableNamePrefix . '_node p
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ph ON ph.childnodeanchor = p.relationanchorpoint
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation ch ON ch.parentnodeanchor = p.relationanchorpoint
 INNER JOIN ' . $this->tableNamePrefix . '_node c ON ch.childnodeanchor = c.relationanchorpoint
 WHERE c.nodeaggregateid = :childNodeAggregateId
 AND c.origindimensionspacepointhash = :originDimensionSpacePointHash
 AND ph.contentstreamid = :contentStreamId
 AND ch.contentstreamid = :contentStreamId
 AND ph.dimensionspacepointhash = :originDimensionSpacePointHash
 AND ch.dimensionspacepointhash = :originDimensionSpacePointHash',
            $params
        )->fetchAssociative();

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $nodeAggregateId
     * @param DimensionSpacePoint $coveredDimensionSpacePoint
     * @return NodeRecord|null
     * @throws DBALException
     * @throws \Exception
     */
    public function findNodeInAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $coveredDimensionSpacePoint
    ): ?NodeRecord {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name FROM ' . $this->tableNamePrefix . '_node n
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateid = :nodeAggregateId
 AND h.contentstreamid = :contentStreamId
 AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'contentStreamId' => (string)$contentStreamId,
                'nodeAggregateId' => (string)$nodeAggregateId,
                'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash
            ]
        )->fetchAssociative();

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $nodeAggregateId
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint
     * @return NodeRecord|null
     * @throws \Exception
     */
    public function findNodeByIds(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeRecord {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.*, h.name FROM ' . $this->tableNamePrefix . '_node n
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateid = :nodeAggregateId
 AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
 AND h.contentstreamid = :contentStreamId
 AND h.dimensionspacepointhash = :originDimensionSpacePointHash',
            [
                'contentStreamId' => (string)$contentStreamId,
                'nodeAggregateId' => (string)$nodeAggregateId,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash
            ]
        )->fetchAssociative();

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param NodeAggregateId $nodeAggregateId
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint
     * @param ContentStreamId $contentStreamId
     * @return NodeRelationAnchorPoint|null
     * @throws DBALException
     */
    public function getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        ContentStreamId $contentStreamId
    ): ?NodeRelationAnchorPoint {
        $rows = $this->getDatabaseConnection()->executeQuery(
            'SELECT DISTINCT n.relationanchorpoint FROM ' . $this->tableNamePrefix . '_node n
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateid = :nodeAggregateId
 AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
 AND h.contentstreamid = :contentStreamId',
            [
                'nodeAggregateId' => (string)$nodeAggregateId,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'contentStreamId' => (string)$contentStreamId,
            ]
        )->fetchAllAssociative();

        if (count($rows) > 1) {
            throw new \Exception(
                'TODO: I believe this shall not happen; but we need to think this through in detail if it does!!!'
            );
        }

        if (count($rows) === 1) {
            return NodeRelationAnchorPoint::fromString($rows[0]['relationanchorpoint']);
        } else {
            return null;
        }
    }

    /**
     * @param NodeAggregateId $nodeAggregateId
     * @param ContentStreamId $contentStreamId
     * @return NodeRelationAnchorPoint[]
     * @throws DBALException
     */
    public function getAnchorPointsForNodeAggregateInContentStream(
        NodeAggregateId $nodeAggregateId,
        ContentStreamId $contentStreamId
    ): iterable {
        $rows = $this->getDatabaseConnection()->executeQuery(
            'SELECT DISTINCT n.relationanchorpoint FROM ' . $this->tableNamePrefix . '_node n
 INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateid = :nodeAggregateId
 AND h.contentstreamid = :contentStreamId',
            [
                'nodeAggregateId' => (string)$nodeAggregateId,
                'contentStreamId' => (string)$contentStreamId,
            ]
        )->fetchAllAssociative();

        return array_map(
            fn ($row) => NodeRelationAnchorPoint::fromString($row['relationanchorpoint']),
            $rows
        );
    }

    /**
     * @param NodeRelationAnchorPoint $nodeRelationAnchorPoint
     * @return NodeRecord|null
     * @throws DBALException
     */
    public function getNodeByAnchorPoint(NodeRelationAnchorPoint $nodeRelationAnchorPoint): ?NodeRecord
    {
        $nodeRow = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.* FROM ' . $this->tableNamePrefix . '_node n
 WHERE n.relationanchorpoint = :relationAnchorPoint',
            [
                'relationAnchorPoint' => (string)$nodeRelationAnchorPoint,
            ]
        )->fetchAssociative();

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    /**
     * @param NodeRelationAnchorPoint|null $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $childAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingAnchorPoint
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     * @throws DBALException
     */
    public function determineHierarchyRelationPosition(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint
    ): int {
        if (!$parentAnchorPoint && !$childAnchorPoint) {
            throw new \InvalidArgumentException(
                'You must specify either parent or child node anchor to determine a hierarchy relation position',
                1519847447
            );
        }
        if ($succeedingSiblingAnchorPoint) {
            /** @var array<string,mixed> $succeedingSiblingRelation */
            $succeedingSiblingRelation = $this->getDatabaseConnection()->executeQuery(
                'SELECT h.* FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                          WHERE h.childnodeanchor = :succeedingSiblingAnchorPoint
                          AND h.contentstreamid = :contentStreamId
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'succeedingSiblingAnchorPoint' => (string)$succeedingSiblingAnchorPoint,
                    'contentStreamId' => (string)$contentStreamId,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                ]
            )->fetchAssociative();

            $succeedingSiblingPosition = (int)$succeedingSiblingRelation['position'];
            $parentAnchorPoint = $succeedingSiblingRelation['parentnodeanchor'];

            $precedingSiblingData = $this->getDatabaseConnection()->executeQuery(
                'SELECT MAX(h.position) AS position FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                          WHERE h.parentnodeanchor = :anchorPoint
                          AND h.contentstreamid = :contentStreamId
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash
                          AND h.position < :position',
                [
                    'anchorPoint' => $parentAnchorPoint,
                    'contentStreamId' => (string)$contentStreamId,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                    'position' => $succeedingSiblingPosition
                ]
            )->fetchAssociative();
            $precedingSiblingPosition = $precedingSiblingData ? ($precedingSiblingData['position'] ?? null) : null;
            if (!is_null($precedingSiblingPosition)) {
                $precedingSiblingPosition = (int) $precedingSiblingPosition;
            }

            if (is_null($precedingSiblingPosition)) {
                $position = $succeedingSiblingPosition - DoctrineDbalContentGraphProjection::RELATION_DEFAULT_OFFSET;
            } else {
                $position = ($succeedingSiblingPosition + $precedingSiblingPosition) / 2;
            }
        } else {
            if (!$parentAnchorPoint) {
                /** @var array<string,mixed> $childHierarchyRelationData */
                $childHierarchyRelationData = $this->getDatabaseConnection()->executeQuery(
                    'SELECT h.parentnodeanchor FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                      WHERE h.childnodeanchor = :childAnchorPoint
                      AND h.contentstreamid = :contentStreamId
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                    [
                        'childAnchorPoint' => $childAnchorPoint,
                        'contentStreamId' => (string)$contentStreamId,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]
                )->fetchAssociative();
                $parentAnchorPoint = NodeRelationAnchorPoint::fromString(
                    $childHierarchyRelationData['parentnodeanchor']
                );
            }
            $rightmostSucceedingSiblingRelationData = $this->getDatabaseConnection()->executeQuery(
                'SELECT MAX(h.position) AS position FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                      WHERE h.parentnodeanchor = :parentAnchorPoint
                      AND h.contentstreamid = :contentStreamId
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'parentAnchorPoint' => $parentAnchorPoint,
                    'contentStreamId' => (string)$contentStreamId,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                ]
            )->fetchAssociative();

            if ($rightmostSucceedingSiblingRelationData) {
                $position = ((int)$rightmostSucceedingSiblingRelationData['position'])
                    + DoctrineDbalContentGraphProjection::RELATION_DEFAULT_OFFSET;
            } else {
                $position = 0;
            }
        }

        return $position;
    }

    /**
     * @param NodeRelationAnchorPoint $parentAnchorPoint
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return HierarchyRelation[]
     * @throws DBALException
     */
    public function getOutgoingHierarchyRelationsForNodeAndSubgraph(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint
    ): array {
        $relations = [];
        foreach (
            $this->getDatabaseConnection()->executeQuery(
                'SELECT h.* FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                          WHERE h.parentnodeanchor = :parentAnchorPoint
                          AND h.contentstreamid = :contentStreamId
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'parentAnchorPoint' => (string)$parentAnchorPoint,
                    'contentStreamId' => (string)$contentStreamId,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                ]
            )->fetchAllAssociative() as $relationData
        ) {
            $relations[] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param NodeRelationAnchorPoint $childAnchorPoint
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return HierarchyRelation[]
     * @throws DBALException
     */
    public function getIngoingHierarchyRelationsForNodeAndSubgraph(
        NodeRelationAnchorPoint $childAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint
    ): array {
        $relations = [];
        foreach (
            $this->getDatabaseConnection()->executeQuery(
                'SELECT h.* FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                          WHERE h.childnodeanchor = :childAnchorPoint
                          AND h.contentstreamid = :contentStreamId
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'childAnchorPoint' => (string)$childAnchorPoint,
                    'contentStreamId' => (string)$contentStreamId,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                ]
            )->fetchAllAssociative() as $relationData
        ) {
            $relations[] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param NodeRelationAnchorPoint $childAnchorPoint
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePointSet|null $restrictToSet
     * @return HierarchyRelation[]
     * @throws DBALException
     */
    public function findIngoingHierarchyRelationsForNode(
        NodeRelationAnchorPoint $childAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $restrictToSet = null
    ): array {
        $relations = [];
        $query = 'SELECT h.* FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
    WHERE h.childnodeanchor = :childAnchorPoint
    AND h.contentstreamid = :contentStreamId';
        $parameters = [
            'childAnchorPoint' => (string)$childAnchorPoint,
            'contentStreamId' => (string)$contentStreamId
        ];
        $types = [];

        if ($restrictToSet) {
            $query .= '
    AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }
        foreach (
            $this->getDatabaseConnection()->executeQuery($query, $parameters, $types)
                ->fetchAllAssociative() as $relationData
        ) {
            $relations[$relationData['dimensionspacepointhash']] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param NodeRelationAnchorPoint $parentAnchorPoint
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePointSet|null $restrictToSet
     * @return HierarchyRelation[]
     * @throws DBALException
     */
    public function findOutgoingHierarchyRelationsForNode(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $restrictToSet = null
    ): array {
        $relations = [];
        $query = 'SELECT h.* FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
    WHERE h.parentnodeanchor = :parentAnchorPoint
    AND h.contentstreamid = :contentStreamId';
        $parameters = [
            'parentAnchorPoint' => (string)$parentAnchorPoint,
            'contentStreamId' => (string)$contentStreamId
        ];
        $types = [];

        if ($restrictToSet) {
            $query .= '
    AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }
        foreach (
            $this->getDatabaseConnection()->executeQuery($query, $parameters, $types)
                 ->fetchAllAssociative() as $relationData
        ) {
            $relations[$relationData['dimensionspacepointhash']] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $nodeAggregateId
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @return array|HierarchyRelation[]
     * @throws DBALException
     */
    public function findOutgoingHierarchyRelationsForNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): array {
        $relations = [];
        foreach (
            $this->getDatabaseConnection()->executeQuery(
                'SELECT h.* FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
     INNER JOIN ' . $this->tableNamePrefix . '_node n ON h.parentnodeanchor = n.relationanchorpoint
     WHERE n.nodeaggregateid = :nodeAggregateId
     AND h.contentstreamid = :contentStreamId
     AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)',
                [
                    'nodeAggregateId' => (string)$nodeAggregateId,
                    'contentStreamId' => (string)$contentStreamId,
                    'dimensionSpacePointHashes' => $dimensionSpacePointSet->getPointHashes()
                ],
                [
                    'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
                ]
            )->fetchAllAssociative() as $relationData
        ) {
            $relations[] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $nodeAggregateId
     * @param DimensionSpacePointSet|null $dimensionSpacePointSet
     * @return array|HierarchyRelation[]
     * @throws DBALException
     */
    public function findIngoingHierarchyRelationsForNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePointSet = null
    ): array {
        $relations = [];

        $query = 'SELECT h.* FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
            INNER JOIN ' . $this->tableNamePrefix . '_node n ON h.childnodeanchor = n.relationanchorpoint
            WHERE n.nodeaggregateid = :nodeAggregateId
            AND h.contentstreamid = :contentStreamId';
        $parameters = [
            'nodeAggregateId' => (string)$nodeAggregateId,
            'contentStreamId' => (string)$contentStreamId,
        ];
        $types = [];

        if ($dimensionSpacePointSet !== null) {
            $query .= '
                AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $dimensionSpacePointSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }

        foreach (
            $this->getDatabaseConnection()->executeQuery($query, $parameters, $types)
                ->fetchAllAssociative() as $relationData
        ) {
            $relations[] = $this->mapRawDataToHierarchyRelation($relationData);
        }

        return $relations;
    }

    /**
     * @return array<int,ContentStreamId>
     * @throws \Doctrine\DBAL\Driver\Exception|\Doctrine\DBAL\Exception
     */
    public function getAllContentStreamIdsAnchorPointIsContainedIn(
        NodeRelationAnchorPoint $nodeRelationAnchorPoint
    ): array {
        $contentStreamIds = [];
        foreach (
            $this->getDatabaseConnection()->executeQuery(
                'SELECT DISTINCT h.contentstreamid
                          FROM ' . $this->tableNamePrefix . '_hierarchyrelation h
                          WHERE h.childnodeanchor = :nodeRelationAnchorPoint',
                [
                    'nodeRelationAnchorPoint' => (string)$nodeRelationAnchorPoint,
                ]
            )->fetchAllAssociative() as $row
        ) {
            $contentStreamIds[] = ContentStreamId::fromString($row['contentstreamid']);
        }

        return $contentStreamIds;
    }

    /**
     * Finds all descendant node aggregate ids, indexed by dimension space point hash
     *
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $entryNodeAggregateId
     * @param DimensionSpacePointSet $affectedDimensionSpacePoints
     * @return array|NodeAggregateId[][]
     * @throws DBALException
     */
    public function findDescendantNodeAggregateIds(
        ContentStreamId $contentStreamId,
        NodeAggregateId $entryNodeAggregateId,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): array {
        $rows = $this->getDatabaseConnection()->executeQuery(
            '
            -- ProjectionContentGraph::findDescendantNodeAggregateIds

            WITH RECURSIVE nestedNodes AS (
                    -- --------------------------------
                    -- INITIAL query: select the root nodes
                    -- --------------------------------
                    SELECT
                       n.nodeaggregateid,
                       n.relationanchorpoint,
                       h.dimensionspacepointhash
                    FROM
                        ' . $this->tableNamePrefix . '_node n
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        on h.childnodeanchor = n.relationanchorpoint
                    WHERE n.nodeaggregateid = :entryNodeAggregateId
                    AND h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)

                UNION
                    -- --------------------------------
                    -- RECURSIVE query: do one "child" query step
                    -- --------------------------------
                    SELECT
                        c.nodeaggregateid,
                        c.relationanchorpoint,
                       h.dimensionspacepointhash
                    FROM
                        nestedNodes p
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        on h.parentnodeanchor = p.relationanchorpoint
                    INNER JOIN ' . $this->tableNamePrefix . '_node c
                        on h.childnodeanchor = c.relationanchorpoint
                    WHERE
                        h.contentstreamid = :contentStreamId
                        AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
            )
            select nodeaggregateid, dimensionspacepointhash from nestedNodes
            ',
            [
                'entryNodeAggregateId' => (string)$entryNodeAggregateId,
                'contentStreamId' => (string)$contentStreamId,
                'affectedDimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes()
            ],
            [
                'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAllAssociative();

        $nodeAggregateIds = [];
        foreach ($rows as $row) {
            $nodeAggregateIds[$row['nodeaggregateid']][$row['dimensionspacepointhash']]
                = NodeAggregateId::fromString($row['nodeaggregateid']);
        }

        return $nodeAggregateIds;
    }

    /**
     * @param array<string,string> $rawData
     */
    protected function mapRawDataToHierarchyRelation(array $rawData): HierarchyRelation
    {
        return new HierarchyRelation(
            NodeRelationAnchorPoint::fromString($rawData['parentnodeanchor']),
            NodeRelationAnchorPoint::fromString($rawData['childnodeanchor']),
            $rawData['name'] ? NodeName::fromString($rawData['name']) : null,
            ContentStreamId::fromString($rawData['contentstreamid']),
            DimensionSpacePoint::fromJsonString($rawData['dimensionspacepoint']),
            $rawData['dimensionspacepointhash'],
            (int)$rawData['position']
        );
    }

    /**
     * @return Connection
     */
    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}

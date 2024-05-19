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
use Doctrine\DBAL\Driver\Exception;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The read only content graph for use by the {@see DoctrineDbalContentGraphProjection}. This is the class for low-level operations
 * within the projection, where implementation details of the graph structure are known.
 *
 * This is NO PUBLIC API in any way.
 *
 * @internal
 */
class ProjectionContentGraph
{
    public function __construct(
        private readonly Connection $dbal,
        private readonly ContentGraphTableNames $tableNames,
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $childNodeAggregateId
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint of $childNodeAggregateId
     * @param DimensionSpacePoint|null $coveredDimensionSpacePoint the dimension space point of which relation we want
     *     to travel upwards. If not given, $originDimensionSpacePoint is used (though I am not fully sure this is
     *     correct)
     * @return NodeRecord|null
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findParentNode(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        ?DimensionSpacePoint $coveredDimensionSpacePoint = null
    ): ?NodeRecord {
        $params = [
            'contentStreamId' => $contentStreamId->value,
            'childNodeAggregateId' => $childNodeAggregateId->value,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint
                ? $coveredDimensionSpacePoint->hash
                : $originDimensionSpacePoint->hash
        ];
        $nodeRow = $this->dbal->executeQuery(
            'SELECT p.*, ph.contentstreamid, ph.subtreetags, dsp.dimensionspacepoint AS origindimensionspacepoint FROM ' . $this->tableNames->node() . ' p
 INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' ph ON ph.childnodeanchor = p.relationanchorpoint
 INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' ch ON ch.parentnodeanchor = p.relationanchorpoint
 INNER JOIN ' . $this->tableNames->node() . ' c ON ch.childnodeanchor = c.relationanchorpoint
 INNER JOIN ' . $this->tableNames->dimensionSpacePoints() . ' dsp ON p.origindimensionspacepointhash = dsp.hash
 WHERE c.nodeaggregateid = :childNodeAggregateId
 AND c.origindimensionspacepointhash = :originDimensionSpacePointHash
 AND ph.contentstreamid = :contentStreamId
 AND ch.contentstreamid = :contentStreamId
 AND ph.dimensionspacepointhash = :coveredDimensionSpacePointHash
 AND ch.dimensionspacepointhash = :coveredDimensionSpacePointHash',
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
        $nodeRow = $this->dbal->executeQuery(
            'SELECT n.*, h.subtreetags, dsp.dimensionspacepoint AS origindimensionspacepoint FROM ' . $this->tableNames->node() . ' n
 INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h ON h.childnodeanchor = n.relationanchorpoint
 INNER JOIN ' . $this->tableNames->dimensionSpacePoints() . ' dsp ON n.origindimensionspacepointhash = dsp.hash
 WHERE n.nodeaggregateid = :nodeAggregateId
 AND h.contentstreamid = :contentStreamId
 AND h.dimensionspacepointhash = :dimensionSpacePointHash',
            [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash
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
        $rows = $this->dbal->executeQuery(
            'SELECT DISTINCT n.relationanchorpoint FROM ' . $this->tableNames->node() . ' n
 INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateid = :nodeAggregateId
 AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
 AND h.contentstreamid = :contentStreamId',
            [
                'nodeAggregateId' => $nodeAggregateId->value,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'contentStreamId' => $contentStreamId->value,
            ]
        )->fetchAllAssociative();

        if (count($rows) > 1) {
            throw new \Exception(
                'TODO: I believe this shall not happen; but we need to think this through in detail if it does!!!'
            );
        }

        if (count($rows) === 1) {
            return NodeRelationAnchorPoint::fromInteger($rows[0]['relationanchorpoint']);
        }
        return null;
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
        $rows = $this->dbal->executeQuery(
            'SELECT DISTINCT n.relationanchorpoint FROM ' . $this->tableNames->node() . ' n
 INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateid = :nodeAggregateId
 AND h.contentstreamid = :contentStreamId',
            [
                'nodeAggregateId' => $nodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value,
            ]
        )->fetchAllAssociative();

        return array_map(
            fn($row) => NodeRelationAnchorPoint::fromInteger($row['relationanchorpoint']),
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
        $nodeRow = $this->dbal->executeQuery(
            'SELECT n.*, dsp.dimensionspacepoint AS origindimensionspacepoint FROM ' . $this->tableNames->node() . ' n
            INNER JOIN ' . $this->tableNames->dimensionSpacePoints() . ' dsp ON n.origindimensionspacepointhash = dsp.hash
 WHERE n.relationanchorpoint = :relationAnchorPoint',
            [
                'relationAnchorPoint' => $nodeRelationAnchorPoint->value,
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
            $succeedingSiblingRelation = $this->dbal->executeQuery(
                'SELECT h.* FROM ' . $this->tableNames->hierarchyRelation() . ' h
                          WHERE h.childnodeanchor = :succeedingSiblingAnchorPoint
                          AND h.contentstreamid = :contentStreamId
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'succeedingSiblingAnchorPoint' => $succeedingSiblingAnchorPoint->value,
                    'contentStreamId' => $contentStreamId->value,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                ]
            )->fetchAssociative();

            $succeedingSiblingPosition = (int)$succeedingSiblingRelation['position'];
            $parentAnchorPoint = NodeRelationAnchorPoint::fromInteger($succeedingSiblingRelation['parentnodeanchor']);

            $precedingSiblingData = $this->dbal->executeQuery(
                'SELECT MAX(h.position) AS position FROM ' . $this->tableNames->hierarchyRelation() . ' h
                          WHERE h.parentnodeanchor = :anchorPoint
                          AND h.contentstreamid = :contentStreamId
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash
                          AND h.position < :position',
                [
                    'anchorPoint' => $parentAnchorPoint->value,
                    'contentStreamId' => $contentStreamId->value,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                    'position' => $succeedingSiblingPosition
                ]
            )->fetchAssociative();
            $precedingSiblingPosition = $precedingSiblingData ? ($precedingSiblingData['position'] ?? null) : null;
            if (!is_null($precedingSiblingPosition)) {
                $precedingSiblingPosition = (int)$precedingSiblingPosition;
            }

            if (is_null($precedingSiblingPosition)) {
                $position = $succeedingSiblingPosition - DoctrineDbalContentGraphProjection::RELATION_DEFAULT_OFFSET;
            } else {
                $position = ($succeedingSiblingPosition + $precedingSiblingPosition) / 2;
            }
        } else {
            if (!$parentAnchorPoint) {
                /** @var array<string,mixed> $childHierarchyRelationData */
                $childHierarchyRelationData = $this->dbal->executeQuery(
                    'SELECT h.parentnodeanchor FROM ' . $this->tableNames->hierarchyRelation() . ' h
                      WHERE h.childnodeanchor = :childAnchorPoint
                      AND h.contentstreamid = :contentStreamId
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                    [
                        'childAnchorPoint' => $childAnchorPoint->value,
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]
                )->fetchAssociative();
                $parentAnchorPoint = NodeRelationAnchorPoint::fromInteger(
                    $childHierarchyRelationData['parentnodeanchor']
                );
            }
            $rightmostSucceedingSiblingRelationData = $this->dbal->executeQuery(
                'SELECT MAX(h.position) AS position FROM ' . $this->tableNames->hierarchyRelation() . ' h
                      WHERE h.parentnodeanchor = :parentAnchorPoint
                      AND h.contentstreamid = :contentStreamId
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'parentAnchorPoint' => $parentAnchorPoint->value,
                    'contentStreamId' => $contentStreamId->value,
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
            $this->dbal->executeQuery(
                'SELECT h.* FROM ' . $this->tableNames->hierarchyRelation() . ' h
                          WHERE h.parentnodeanchor = :parentAnchorPoint
                          AND h.contentstreamid = :contentStreamId
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'parentAnchorPoint' => $parentAnchorPoint->value,
                    'contentStreamId' => $contentStreamId->value,
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
            $this->dbal->executeQuery(
                'SELECT h.* FROM ' . $this->tableNames->hierarchyRelation() . ' h
                          WHERE h.childnodeanchor = :childAnchorPoint
                          AND h.contentstreamid = :contentStreamId
                          AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'childAnchorPoint' => $childAnchorPoint->value,
                    'contentStreamId' => $contentStreamId->value,
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
        $query = 'SELECT h.* FROM ' . $this->tableNames->hierarchyRelation() . ' h
    WHERE h.childnodeanchor = :childAnchorPoint
    AND h.contentstreamid = :contentStreamId';
        $parameters = [
            'childAnchorPoint' => $childAnchorPoint->value,
            'contentStreamId' => $contentStreamId->value
        ];
        $types = [];

        if ($restrictToSet) {
            $query .= '
    AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }
        foreach (
            $this->dbal->executeQuery($query, $parameters, $types)
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
        $query = 'SELECT h.* FROM ' . $this->tableNames->hierarchyRelation() . ' h
    WHERE h.parentnodeanchor = :parentAnchorPoint
    AND h.contentstreamid = :contentStreamId';
        $parameters = [
            'parentAnchorPoint' => $parentAnchorPoint->value,
            'contentStreamId' => $contentStreamId->value
        ];
        $types = [];

        if ($restrictToSet) {
            $query .= '
    AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }
        foreach (
            $this->dbal->executeQuery($query, $parameters, $types)
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
            $this->dbal->executeQuery(
                'SELECT h.* FROM ' . $this->tableNames->hierarchyRelation() . ' h
     INNER JOIN ' . $this->tableNames->node() . ' n ON h.parentnodeanchor = n.relationanchorpoint
     WHERE n.nodeaggregateid = :nodeAggregateId
     AND h.contentstreamid = :contentStreamId
     AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)',
                [
                    'nodeAggregateId' => $nodeAggregateId->value,
                    'contentStreamId' => $contentStreamId->value,
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

        $query = 'SELECT h.* FROM ' . $this->tableNames->hierarchyRelation() . ' h
            INNER JOIN ' . $this->tableNames->node() . ' n ON h.childnodeanchor = n.relationanchorpoint
            WHERE n.nodeaggregateid = :nodeAggregateId
            AND h.contentstreamid = :contentStreamId';
        $parameters = [
            'nodeAggregateId' => $nodeAggregateId->value,
            'contentStreamId' => $contentStreamId->value,
        ];
        $types = [];

        if ($dimensionSpacePointSet !== null) {
            $query .= '
                AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $dimensionSpacePointSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }

        foreach (
            $this->dbal->executeQuery($query, $parameters, $types)
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
            $this->dbal->executeQuery(
                'SELECT DISTINCT h.contentstreamid
                          FROM ' . $this->tableNames->hierarchyRelation() . ' h
                          WHERE h.childnodeanchor = :nodeRelationAnchorPoint',
                [
                    'nodeRelationAnchorPoint' => $nodeRelationAnchorPoint->value,
                ]
            )->fetchAllAssociative() as $row
        ) {
            $contentStreamIds[] = ContentStreamId::fromString($row['contentstreamid']);
        }

        return $contentStreamIds;
    }

    /**
     * @param array<string,string> $rawData
     */
    protected function mapRawDataToHierarchyRelation(array $rawData): HierarchyRelation
    {
        $dimensionspacepointRaw = $this->dbal->fetchOne('SELECT dimensionspacepoint FROM ' . $this->tableNames->dimensionSpacePoints() . ' WHERE hash = :hash', ['hash' => $rawData['dimensionspacepointhash']]);

        return new HierarchyRelation(
            NodeRelationAnchorPoint::fromInteger((int)$rawData['parentnodeanchor']),
            NodeRelationAnchorPoint::fromInteger((int)$rawData['childnodeanchor']),
            ContentStreamId::fromString($rawData['contentstreamid']),
            DimensionSpacePoint::fromJsonString($dimensionspacepointRaw),
            $rawData['dimensionspacepointhash'],
            (int)$rawData['position'],
            NodeFactory::extractNodeTagsFromJson($rawData['subtreetags']),
        );
    }
}

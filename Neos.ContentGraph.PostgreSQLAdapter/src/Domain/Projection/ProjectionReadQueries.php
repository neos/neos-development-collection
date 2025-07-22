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

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Query\ProjectionHypergraphQuery;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * FIXME ether rename this class or the ContentGraphProjection, since both names are confusing
 * The alternate reality-aware projection-time hypergraph for the PostgreSQL backend via Doctrine DBAL
 *
 * @internal
 */
final readonly class ProjectionReadQueries
{

    private ContentGraphTableNames $tableNames;

    public function __construct(
        private Connection $dbal,
        ContentRepositoryId $contentRepositoryId
    ) {
        $this->tableNames = ContentGraphTableNames::create($contentRepositoryId);
    }

    /**
     * @param NodeRelationAnchorPoint $relationAnchorPoint
     * @return NodeRecord|null
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findNodeRecordByRelationAnchorPoint(
        NodeRelationAnchorPoint $relationAnchorPoint
    ): ?NodeRecord {
        $tableNode = $this->tableNames->node();
        $parameters = [
            'relationAnchorPoint' => $relationAnchorPoint->value
        ];

        $result = $this->dbal->executeQuery(
            <<<SQL
                select n.*
                from $tableNode n
                where n.relationanchorpoint = :relationAnchorPoint
            SQL,
            $parameters
        )->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @throws \Exception
     */
    public function findNodeRecordByCoverage(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): ?NodeRecord {
        $query = ProjectionHypergraphQuery::create($contentStreamId, $this->tableNames);
        $query = $query->withDimensionSpacePoint($dimensionSpacePoint)
            ->withNodeAggregateId($nodeAggregateId);
        $result = $query->execute($this->dbal)->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @throws \Exception
     */
    public function findNodeRecordByOrigin(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): ?NodeRecord {
        $query = ProjectionHypergraphQuery::create($contentStreamId, $this->tableNames);
        $query = $query->withOriginDimensionSpacePoint($originDimensionSpacePoint);
        $query = $query->withNodeAggregateId($nodeAggregateId);

        $result = $query->execute($this->dbal)->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findParentNodeRecordByOrigin(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeAggregateId $childNodeAggregateId
    ): ?NodeRecord {
        $query = /** @lang PostgreSQL */
            'SELECT p.*
            FROM ' . $this->tableNames->node() . ' p
            JOIN ' . $this->tableNames->hierarchyRelation() . ' h ON h.parentnodeanchor = p.relationanchorpoint
            JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
            AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
            AND h.dimensionspacepointhash = :originDimensionSpacePointHash
            AND n.nodeaggregateid = :childNodeAggregateId';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'childNodeAggregateId' => $childNodeAggregateId->value
        ];

        $result = $this->dbal
            ->executeQuery($query, $parameters)
            ->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    public function findSucceedingSiblingNodeRecordByOrigin(): ?NodeRecord
    {
        //$query = /** @lang PostgreSQL */
        /*    'SELECT * FROM neos_contentgraph_node sn,
    (
        SELECT n.relationanchorpoint, h.childnodeanchors, h.contentstreamid, h.dimensionspacepointhash
            FROM neos_contentgraph_node n
            JOIN neos_contentgraph_hierarchyhyperrelation h ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND n.nodeaggregateid = :nodeAggregateId
    ) AS sh
    WHERE sn.nodeaggregateid != :nodeAggregateId' . $queryMode->renderCondition();

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'nodeAggregateId' => (string)$nodeAggregateId
        ];*/
        return null;
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findParentNodeRecordByCoverage(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $childNodeAggregateId
    ): ?NodeRecord {
        $query = /** @lang PostgreSQL */
            'SELECT p.*
            FROM ' . $this->tableNames->node() . '_node p
            JOIN ' . $this->tableNames->hierarchyRelation() . '_hierarchyhyperrelation h ON h.parentnodeanchor = p.relationanchorpoint
            JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
            AND h.dimensionspacepointhash = :coveredDimensionSpacePointHash
            AND n.nodeaggregateid = :childNodeAggregateId';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'childNodeAggregateId' => $childNodeAggregateId->value
        ];

        $result = $this->dbal
            ->executeQuery($query, $parameters)
            ->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @return array<int,NodeRecord>
     * @throws \Exception
     */
    public function findNodeRecordsForNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): array {
        $query = ProjectionHypergraphQuery::create($contentStreamId, $this->tableNames);
        $query = $query->withNodeAggregateId($nodeAggregateId);

        $result = $query->execute($this->dbal)->fetchAllAssociative();

        return array_map(function ($row) {
            return NodeRecord::fromDatabaseRow($row);
        }, $result);
    }

    /**
     * @return array|HierarchyRelationRecord[]
     * @throws DBALException
     */
    public function findIngoingHierarchyHyperrelationRecords(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $childNodeAnchor,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints = null
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            WHERE h.contentstreamid = :contentStreamId
            AND :childNodeAnchor = ANY(h.childnodeanchors)';
        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'childNodeAnchor' => $childNodeAnchor->value
        ];
        $types = [];

        if ($affectedDimensionSpacePoints) {
            $query .= '
            AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)';
            $parameters['affectedDimensionSpacePointHashes'] = $affectedDimensionSpacePoints->getPointHashes();
            $types['affectedDimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }

        $hierarchyHyperrelations = [];
        foreach ($this->dbal->executeQuery($query, $parameters, $types)->iterateAssociative() as $row) {
            $hierarchyHyperrelations[] = HierarchyRelationRecord::fromDatabaseRow($row);
        }

        return $hierarchyHyperrelations;
    }

    /**
     * @return array|HierarchyRelationRecord[]
     * @throws DBALException
     */
    public function findOutgoingHierarchyHyperrelationRecords(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $parentNodeAnchor,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints = null
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            WHERE h.contentstreamid = :contentStreamId
            AND h.parentnodeanchor = :parentNodeAnchor';
        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'parentNodeAnchor' => $parentNodeAnchor->value
        ];
        $types = [];

        if ($affectedDimensionSpacePoints) {
            $query .= '
            AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)';
            $parameters['affectedDimensionSpacePointHashes'] = $affectedDimensionSpacePoints->getPointHashes();
        }
        $types['affectedDimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;

        $hierarchyHyperrelations = [];
        foreach ($this->dbal->executeQuery($query, $parameters, $types)->iterateAssociative() as $row) {
            $hierarchyHyperrelations[] = HierarchyRelationRecord::fromDatabaseRow($row);
        }

        return $hierarchyHyperrelations;
    }

    /**
     * @return array|ReferenceRelationRecord[]
     * @throws DBALException
     */
    public function findOutgoingReferenceHyperrelationRecords(
        NodeRelationAnchorPoint $sourceNodeAnchor
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT r.*
            FROM ' . $this->tableNames->referenceRelation() . ' r
            WHERE r.sourcenodeanchor = :sourceNodeAnchor';

        $parameters = [
            'sourceNodeAnchor' => $sourceNodeAnchor->value
        ];

        $referenceHyperrelations = [];
        foreach ($this->dbal->executeQuery($query, $parameters)->iterateAssociative() as $row) {
            $referenceHyperrelations[] = ReferenceRelationRecord::fromDatabaseRow($row);
        }

        return $referenceHyperrelations;
    }

    /**
     * @throws DBALException
     */
    public function findHierarchyHyperrelationRecordByParentNodeAnchor(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $parentNodeAnchor
    ): ?HierarchyRelationRecord {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            WHERE h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND h.parentnodeanchor = :parentNodeAnchor';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'parentNodeAnchor' => $parentNodeAnchor->value
        ];

        $result = $this->dbal->executeQuery($query, $parameters)->fetchAssociative();

        return $result ? HierarchyRelationRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @throws DBALException
     */
    public function findHierarchyHyperrelationRecordByChildNodeAnchor(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $childNodeAnchor
    ): ?HierarchyRelationRecord {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            WHERE h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND :childNodeAnchor = ANY(h.childnodeanchors)';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'childNodeAnchor' => $childNodeAnchor->value
        ];

        $result = $this->dbal->executeQuery($query, $parameters)->fetchAssociative();

        return $result ? HierarchyRelationRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @return array|HierarchyRelationRecord[]
     * @throws DBALException
     */
    public function findHierarchyHyperrelationRecordsByChildNodeAnchor(
        NodeRelationAnchorPoint $childNodeAnchor
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            WHERE :childNodeAnchor = ANY(h.childnodeanchors)';

        $parameters = [
            'childNodeAnchor' => $childNodeAnchor->value
        ];

        $hierarchyRelationRecords = [];
        $result = $this->dbal->executeQuery($query, $parameters)->fetchAllAssociative();
        foreach ($result as $row) {
            $hierarchyRelationRecords[] = HierarchyRelationRecord::fromDatabaseRow($row);
        }

        return $hierarchyRelationRecords;
    }

    /**
     * @throws DBALException
     */
    public function findChildHierarchyHyperrelationRecord(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): ?HierarchyRelationRecord {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            JOIN ' . $this->tableNames->node() . ' n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamid = :contentStreamId
            AND n.nodeaggregateid = :nodeAggregateId
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'nodeAggregateId' => $nodeAggregateId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash
        ];

        $result = $this->dbal->executeQuery($query, $parameters)->fetchAssociative();

        return $result ? HierarchyRelationRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeRelationAnchorPoint $nodeRelationAnchorPoint
     * @return DimensionSpacePointSet
     * @throws DBALException
     */
    public function findCoverageByNodeRelationAnchorPoint(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $nodeRelationAnchorPoint
    ): DimensionSpacePointSet {
        $query = /** @lang PostgreSQL */
            'SELECT h.dimensionspacepoint
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            JOIN ' . $this->tableNames->node() . ' n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamid = :contentStreamId
            AND n.relationanchorpoint = :relationAnchorPoint';
        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'relationanchorpoint' => $nodeRelationAnchorPoint->value
        ];

        $dimensionSpacePoints = [];
        foreach ($this->dbal->executeQuery($query, $parameters)->fetchAllAssociative() as $row) {
            $dimensionSpacePoints[] = DimensionSpacePoint::fromJsonString($row['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $nodeAggregateId
     * @return DimensionSpacePointSet
     * @throws DBALException
     */
    public function findCoverageByNodeAggregateId(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): DimensionSpacePointSet {
        $query = /** @lang PostgreSQL */
            'SELECT h.dimensionspacepoint
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            JOIN ' . $this->tableNames->node() . ' n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamid = :contentStreamId
            AND n.nodeaggregateid = :nodeAggregateId';
        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'nodeAggregateId' => $nodeAggregateId->value
        ];

        $dimensionSpacePoints = [];
        foreach ($this->dbal->executeQuery($query, $parameters)->fetchAllAssociative() as $row) {
            $dimensionSpacePoints[] = DimensionSpacePoint::fromJsonString($row['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePointSet $dimensionSpacePoints
     * @param NodeAggregateId $originNodeAggregateId
     * @return array|RestrictionHyperrelationRecord[]
     * @throws DBALException
     */
    public function findOutgoingRestrictionRelations(
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $dimensionSpacePoints,
        NodeAggregateId $originNodeAggregateId
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT r.*
            FROM ' . $this->tableNames->subTreeTagsRelation() . ' r
            WHERE r.contentstreamid = :contentStreamId
            AND r.dimensionspacepointhash IN (:dimensionSpacePointHashes)
            AND r.originnodeaggregateid = :originNodeAggregateId';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes(),
            'originNodeAggregateId' => $originNodeAggregateId->value
        ];
        $types = [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];

        $restrictionRelationRecords = [];
        foreach (
            $this->dbal->executeQuery($query, $parameters, $types)
                ->fetchAllAssociative() as $row
        ) {
            $restrictionRelationRecords[] = RestrictionHyperrelationRecord::fromDatabaseRow($row);
        }

        return $restrictionRelationRecords;
    }

    /**
     * @return array|RestrictionHyperrelationRecord[]
     * @throws DBALException
     */
    public function findIngoingRestrictionRelations(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT r.*
            FROM ' . $this->tableNames->subTreeTagsRelation() . ' r
            WHERE r.contentstreamid = :contentStreamId
            AND r.dimensionspacepointhash = :dimensionSpacePointHash
            AND :nodeAggregateId = ANY(r.affectednodeaggregateids)';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'nodeAggregateId' => $nodeAggregateId->value
        ];

        $restrictionRelations = [];
        $rows = $this->dbal->executeQuery($query, $parameters)->fetchAllAssociative();
        foreach ($rows as $row) {
            $restrictionRelations[] = RestrictionHyperrelationRecord::fromDatabaseRow($row);
        }

        return $restrictionRelations;
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePointSet $dimensionSpacePoints
     * @param NodeAggregateId $nodeAggregateId
     * @return array|NodeAggregateIds[]
     * @throws DBALException
     */
    public function findDescendantNodeAggregateIds(
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $dimensionSpacePoints,
        NodeAggregateId $nodeAggregateId
    ): array {
        $query = /** @lang PostgreSQL */
            '
            -- ProjectionReadQueries::findDescendantNodeAggregateIds
            WITH RECURSIVE descendantNodes(nodeaggregateid, relationanchorpoint, dimensionspacepointhash) AS (
                    -- --------------------------------
                    -- INITIAL query: select the root nodes
                    -- --------------------------------
                    SELECT
                       n.nodeaggregateid,
                       n.relationanchorpoint,
                       h.dimensionspacepointhash
                    FROM ' . $this->tableNames->node() . ' n
                    INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
                        ON n.relationanchorpoint = ANY(h.childnodeanchors)
                    WHERE n.nodeaggregateid = :entryNodeAggregateId
                        AND h.contentstreamid = :contentStreamId
                        AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)

                UNION ALL
                    -- --------------------------------
                    -- RECURSIVE query: do one "child" query step
                    -- --------------------------------
                    SELECT
                        c.nodeaggregateid,
                        c.relationanchorpoint,
                        h.dimensionspacepointhash
                    FROM
                        descendantNodes p
                    INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
                        ON h.parentnodeanchor = p.relationanchorpoint
                    INNER JOIN ' . $this->tableNames->node() . ' c ON c.relationanchorpoint = ANY(h.childnodeanchors)
                    WHERE
                        h.contentstreamid = :contentStreamId
                        AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
            )
            SELECT nodeaggregateid, dimensionspacepointhash from descendantNodes';

        $parameters = [
            'entryNodeAggregateId' => $nodeAggregateId->value,
            'contentStreamId' => $contentStreamId->value,
            'affectedDimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes()
        ];

        $types = [
            'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];

        $rows = $this->dbal->executeQuery($query, $parameters, $types)
            ->fetchAllAssociative();
        $nodeAggregateIdsByDimensionSpacePoint = [];
        foreach ($rows as $row) {
            $nodeAggregateIdsByDimensionSpacePoint[$row['dimensionspacepointhash']]
            [$row['nodeaggregateid']]
                = NodeAggregateId::fromString($row['nodeaggregateid']);
        }

        return array_map(function (array $nodeAggregateIds) {
            return NodeAggregateIds::fromArray($nodeAggregateIds);
        }, $nodeAggregateIdsByDimensionSpacePoint);
    }

    public function countContentStreamCoverage(NodeRelationAnchorPoint $anchorPoint): int
    {
        $query = /** @lang PostgreSQL */
            'SELECT DISTINCT contentstreamid
            FROM ' . $this->tableNames->hierarchyRelation() . '
            WHERE :anchorPoint = ANY(childnodeanchors)';

        $parameters = [
            'anchorPoint' => $anchorPoint->value
        ];

        return (int)$this->dbal->executeQuery($query, $parameters)->rowCount();
    }
}

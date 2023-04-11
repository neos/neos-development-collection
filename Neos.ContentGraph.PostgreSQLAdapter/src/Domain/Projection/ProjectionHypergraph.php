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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Query\ProjectionHypergraphQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * The alternate reality-aware projection-time hypergraph for the PostgreSQL backend via Doctrine DBAL
 *
 * @internal
 */
final class ProjectionHypergraph
{
    public function __construct(
        private readonly PostgresDbalClientInterface $databaseClient,
        private readonly string $tableNamePrefix
    ) {
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
        $query = /** @lang PostgreSQL */
            'SELECT n.*
            FROM ' . $this->tableNamePrefix . '_node n
            WHERE n.relationanchorpoint = :relationAnchorPoint';

        $parameters = [
            'relationAnchorPoint' => (string)$relationAnchorPoint
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAssociative();

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
        $query = ProjectionHypergraphQuery::create($contentStreamId, $this->tableNamePrefix);
        $query =  $query->withDimensionSpacePoint($dimensionSpacePoint)
            ->withNodeAggregateId($nodeAggregateId);
        /** @phpstan-ignore-next-line @todo check actual return type */
        $result = $query->execute($this->getDatabaseConnection())->fetchAssociative();

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
        $query = ProjectionHypergraphQuery::create($contentStreamId, $this->tableNamePrefix);
        $query = $query->withOriginDimensionSpacePoint($originDimensionSpacePoint);
        $query = $query->withNodeAggregateId($nodeAggregateId);

        /** @phpstan-ignore-next-line @todo check actual return type */
        $result = $query->execute($this->getDatabaseConnection())->fetchAssociative();

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
            FROM ' . $this->tableNamePrefix . '_node p
            JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h ON h.parentnodeanchor = p.relationanchorpoint
            JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
            AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
            AND h.dimensionspacepointhash = :originDimensionSpacePointHash
            AND n.nodeaggregateid = :childNodeAggregateId';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'childNodeAggregateId' => (string)$childNodeAggregateId
        ];

        $result = $this->getDatabaseConnection()
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
            FROM ' . $this->tableNamePrefix . '_node p
            JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h ON h.parentnodeanchor = p.relationanchorpoint
            JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
            AND h.dimensionspacepointhash = :coveredDimensionSpacePointHash
            AND n.nodeaggregateid = :childNodeAggregateId';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'childNodeAggregateId' => (string)$childNodeAggregateId
        ];

        $result = $this->getDatabaseConnection()
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
        $query = ProjectionHypergraphQuery::create($contentStreamId, $this->tableNamePrefix);
        $query = $query->withNodeAggregateId($nodeAggregateId);

        /** @phpstan-ignore-next-line @todo check actual return type */
        $result = $query->execute($this->getDatabaseConnection())->fetchAllAssociative();

        return array_map(function ($row) {
            return NodeRecord::fromDatabaseRow($row);
        }, $result);
    }

    /**
     * @return array|HierarchyHyperrelationRecord[]
     * @throws DBALException
     */
    public function findIngoingHierarchyHyperrelationRecords(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $childNodeAnchor,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints = null
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            WHERE h.contentstreamid = :contentStreamId
            AND :childNodeAnchor = ANY(h.childnodeanchors)';
        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'childNodeAnchor' => (string)$childNodeAnchor
        ];
        $types = [];

        if ($affectedDimensionSpacePoints) {
            $query .= '
            AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)';
            $parameters['affectedDimensionSpacePointHashes'] = $affectedDimensionSpacePoints->getPointHashes();
            $types['affectedDimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }

        $hierarchyHyperrelations = [];
        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters, $types) as $row) {
            $hierarchyHyperrelations[] = HierarchyHyperrelationRecord::fromDatabaseRow($row);
        }

        return $hierarchyHyperrelations;
    }

    /**
     * @return array|HierarchyHyperrelationRecord[]
     * @throws DBALException
     */
    public function findOutgoingHierarchyHyperrelationRecords(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $parentNodeAnchor,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints = null
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            WHERE h.contentstreamid = :contentStreamId
            AND h.parentnodeanchor = :parentNodeAnchor';
        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'parentNodeAnchor' => (string)$parentNodeAnchor
        ];
        $types = [];

        if ($affectedDimensionSpacePoints) {
            $query .= '
            AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)';
            $parameters['affectedDimensionSpacePointHashes'] = $affectedDimensionSpacePoints->getPointHashes();
        }
        $types['affectedDimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;

        $hierarchyHyperrelations = [];
        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters, $types) as $row) {
            $hierarchyHyperrelations[] = HierarchyHyperrelationRecord::fromDatabaseRow($row);
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
            FROM ' . $this->tableNamePrefix . '_referencerelation r
            WHERE r.sourcenodeanchor = :sourceNodeAnchor';

        $parameters = [
            'sourceNodeAnchor' => (string)$sourceNodeAnchor
        ];

        $referenceHyperrelations = [];
        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters) as $row) {
            $referenceHyperrelations[] = ReferenceRelationRecord::fromDatabaseRow($row);
        }

        return $referenceHyperrelations;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     */
    public function findHierarchyHyperrelationRecordByParentNodeAnchor(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $parentNodeAnchor
    ): ?HierarchyHyperrelationRecord {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            WHERE h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND h.parentnodeanchor = :parentNodeAnchor';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'parentNodeAnchor' => (string)$parentNodeAnchor
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAssociative();

        return $result ? HierarchyHyperrelationRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     */
    public function findHierarchyHyperrelationRecordByChildNodeAnchor(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $childNodeAnchor
    ): ?HierarchyHyperrelationRecord {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            WHERE h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND :childNodeAnchor = ANY(h.childnodeanchors)';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'childNodeAnchor' => (string)$childNodeAnchor
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAssociative();

        return $result ? HierarchyHyperrelationRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @return array|HierarchyHyperrelationRecord[]
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     */
    public function findHierarchyHyperrelationRecordsByChildNodeAnchor(
        NodeRelationAnchorPoint $childNodeAnchor
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            WHERE :childNodeAnchor = ANY(h.childnodeanchors)';

        $parameters = [
            'childNodeAnchor' => (string)$childNodeAnchor
        ];

        $hierarchyRelationRecords = [];
        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative();
        foreach ($result as $row) {
            $hierarchyRelationRecords[] = HierarchyHyperrelationRecord::fromDatabaseRow($row);
        }

        return $hierarchyRelationRecords;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     */
    public function findChildHierarchyHyperrelationRecord(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): ?HierarchyHyperrelationRecord {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            JOIN ' . $this->tableNamePrefix . '_node n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamid = :contentStreamId
            AND n.nodeaggregateid = :nodeAggregateId
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'nodeAggregateId' => (string)$nodeAggregateId,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAssociative();

        return $result ? HierarchyHyperrelationRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeRelationAnchorPoint $nodeRelationAnchorPoint
     * @return DimensionSpacePointSet
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findCoverageByNodeRelationAnchorPoint(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $nodeRelationAnchorPoint
    ): DimensionSpacePointSet {
        $query = /** @lang PostgreSQL */
            'SELECT h.dimensionspacepoint
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            JOIN ' . $this->tableNamePrefix . '_node n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamid = :contentStreamId
            AND n.relationanchorpoint = :relationAnchorPoint';
        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'relationanchorpoint' => (string)$nodeRelationAnchorPoint
        ];

        $dimensionSpacePoints = [];
        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative() as $row) {
            $dimensionSpacePoints[] = DimensionSpacePoint::fromJsonString($row['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $nodeAggregateId
     * @return DimensionSpacePointSet
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findCoverageByNodeAggregateId(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): DimensionSpacePointSet {
        $query = /** @lang PostgreSQL */
            'SELECT h.dimensionspacepoint
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            JOIN ' . $this->tableNamePrefix . '_node n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamid = :contentStreamId
            AND n.nodeaggregateid = :nodeAggregateId';
        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'nodeAggregateId' => (string)$nodeAggregateId
        ];

        $dimensionSpacePoints = [];
        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative() as $row) {
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
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findOutgoingRestrictionRelations(
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $dimensionSpacePoints,
        NodeAggregateId $originNodeAggregateId
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT r.*
            FROM ' . $this->tableNamePrefix . '_restrictionhyperrelation r
            WHERE r.contentstreamid = :contentStreamId
            AND r.dimensionspacepointhash IN (:dimensionSpacePointHashes)
            AND r.originnodeaggregateid = :originNodeAggregateId';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'dimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes(),
            'originNodeAggregateId' => (string)$originNodeAggregateId
        ];
        $types = [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];

        $restrictionRelationRecords = [];
        foreach (
            $this->getDatabaseConnection()->executeQuery($query, $parameters, $types)
                ->fetchAllAssociative() as $row
        ) {
            $restrictionRelationRecords[] = RestrictionHyperrelationRecord::fromDatabaseRow($row);
        }

        return $restrictionRelationRecords;
    }

    /**
     * @return array|RestrictionHyperrelationRecord[]
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findIngoingRestrictionRelations(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT r.*
            FROM ' . $this->tableNamePrefix . '_restrictionhyperrelation r
            WHERE r.contentstreamid = :contentStreamId
            AND r.dimensionspacepointhash = :dimensionSpacePointHash
            AND :nodeAggregateId = ANY(r.affectednodeaggregateids)';

        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'nodeAggregateId' => (string)$nodeAggregateId
        ];

        $restrictionRelations = [];
        $rows = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative();
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
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findDescendantNodeAggregateIds(
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $dimensionSpacePoints,
        NodeAggregateId $nodeAggregateId
    ): array {
        $query = /** @lang PostgreSQL */ '
            -- ProjectionHypergraph::findDescendantNodeAggregateIds
            WITH RECURSIVE descendantNodes(nodeaggregateid, relationanchorpoint, dimensionspacepointhash) AS (
                    -- --------------------------------
                    -- INITIAL query: select the root nodes
                    -- --------------------------------
                    SELECT
                       n.nodeaggregateid,
                       n.relationanchorpoint,
                       h.dimensionspacepointhash
                    FROM ' . $this->tableNamePrefix . '_node n
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
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
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
                        ON h.parentnodeanchor = p.relationanchorpoint
                    INNER JOIN ' . $this->tableNamePrefix . '_node c ON c.relationanchorpoint = ANY(h.childnodeanchors)
                    WHERE
                        h.contentstreamid = :contentStreamId
                        AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
            )
            SELECT nodeaggregateid, dimensionspacepointhash from descendantNodes';

        $parameters = [
            'entryNodeAggregateId' => (string)$nodeAggregateId,
            'contentStreamId' => (string)$contentStreamId,
            'affectedDimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes()
        ];

        $types = [
            'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];

        $rows = $this->getDatabaseConnection()->executeQuery($query, $parameters, $types)
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
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation
            WHERE :anchorPoint = ANY(childnodeanchors)';

        $parameters = [
            'anchorPoint' => (string)$anchorPoint
        ];

        return (int)$this->getDatabaseConnection()->executeQuery($query, $parameters)->rowCount();
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->databaseClient->getConnection();
    }
}

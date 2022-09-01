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
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateIdentifier
    ): ?NodeRecord {
        $query = ProjectionHypergraphQuery::create($contentStreamIdentifier, $this->tableNamePrefix);
        $query =  $query->withDimensionSpacePoint($dimensionSpacePoint)
            ->withNodeAggregateIdentifier($nodeAggregateIdentifier);
        /** @phpstan-ignore-next-line @todo check actual return type */
        $result = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @throws \Exception
     */
    public function findNodeRecordByOrigin(
        ContentStreamId $contentStreamIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeAggregateId $nodeAggregateIdentifier
    ): ?NodeRecord {
        $query = ProjectionHypergraphQuery::create($contentStreamIdentifier, $this->tableNamePrefix);
        $query = $query->withOriginDimensionSpacePoint($originDimensionSpacePoint);
        $query = $query->withNodeAggregateIdentifier($nodeAggregateIdentifier);

        /** @phpstan-ignore-next-line @todo check actual return type */
        $result = $query->execute($this->getDatabaseConnection())->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findParentNodeRecordByOrigin(
        ContentStreamId $contentStreamIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeAggregateId $childNodeAggregateIdentifier
    ): ?NodeRecord {
        $query = /** @lang PostgreSQL */
            'SELECT p.*
            FROM ' . $this->tableNamePrefix . '_node p
            JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h ON h.parentnodeanchor = p.relationanchorpoint
            JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
            AND h.dimensionspacepointhash = :originDimensionSpacePointHash
            AND n.nodeaggregateidentifier = :childNodeAggregateIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateIdentifier
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
        SELECT n.relationanchorpoint, h.childnodeanchors, h.contentstreamidentifier, h.dimensionspacepointhash
            FROM neos_contentgraph_node n
            JOIN neos_contentgraph_hierarchyhyperrelation h ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND n.nodeaggregateidentifier = :nodeAggregateIdentifier
    ) AS sh
    WHERE sn.nodeaggregateidentifier != :nodeAggregateIdentifier' . $queryMode->renderCondition();

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier
        ];*/
        return null;
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findParentNodeRecordByCoverage(
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $childNodeAggregateIdentifier
    ): ?NodeRecord {
        $query = /** @lang PostgreSQL */
            'SELECT p.*
            FROM ' . $this->tableNamePrefix . '_node p
            JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h ON h.parentnodeanchor = p.relationanchorpoint
            JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND h.dimensionspacepointhash = :coveredDimensionSpacePointHash
            AND n.nodeaggregateidentifier = :childNodeAggregateIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateIdentifier
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
        ContentStreamId $contentStreamIdentifier,
        NodeAggregateId $nodeAggregateIdentifier
    ): array {
        $query = ProjectionHypergraphQuery::create($contentStreamIdentifier, $this->tableNamePrefix);
        $query = $query->withNodeAggregateIdentifier($nodeAggregateIdentifier);

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
        ContentStreamId $contentStreamIdentifier,
        NodeRelationAnchorPoint $childNodeAnchor,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints = null
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND :childNodeAnchor = ANY(h.childnodeanchors)';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
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
        ContentStreamId $contentStreamIdentifier,
        NodeRelationAnchorPoint $parentNodeAnchor,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints = null
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND h.parentnodeanchor = :parentNodeAnchor';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
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
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $parentNodeAnchor
    ): ?HierarchyHyperrelationRecord {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND h.parentnodeanchor = :parentNodeAnchor';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
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
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $childNodeAnchor
    ): ?HierarchyHyperrelationRecord {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                AND :childNodeAnchor = ANY(h.childnodeanchors)';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
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
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateIdentifier
    ): ?HierarchyHyperrelationRecord {
        $query = /** @lang PostgreSQL */
            'SELECT h.*
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            JOIN ' . $this->tableNamePrefix . '_node n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND n.nodeaggregateidentifier = :nodeAggregateIdentifier
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAssociative();

        return $result ? HierarchyHyperrelationRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @param ContentStreamId $contentStreamIdentifier
     * @param NodeRelationAnchorPoint $nodeRelationAnchorPoint
     * @return DimensionSpacePointSet
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findCoverageByNodeRelationAnchorPoint(
        ContentStreamId $contentStreamIdentifier,
        NodeRelationAnchorPoint $nodeRelationAnchorPoint
    ): DimensionSpacePointSet {
        $query = /** @lang PostgreSQL */
            'SELECT h.dimensionspacepoint
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            JOIN ' . $this->tableNamePrefix . '_node n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND n.relationanchorpoint = :relationAnchorPoint';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'relationanchorpoint' => (string)$nodeRelationAnchorPoint
        ];

        $dimensionSpacePoints = [];
        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative() as $row) {
            $dimensionSpacePoints[] = DimensionSpacePoint::fromJsonString($row['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    /**
     * @param ContentStreamId $contentStreamIdentifier
     * @param NodeAggregateId $nodeAggregateIdentifier
     * @return DimensionSpacePointSet
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findCoverageByNodeAggregateIdentifier(
        ContentStreamId $contentStreamIdentifier,
        NodeAggregateId $nodeAggregateIdentifier
    ): DimensionSpacePointSet {
        $query = /** @lang PostgreSQL */
            'SELECT h.dimensionspacepoint
            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
            JOIN ' . $this->tableNamePrefix . '_node n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND n.nodeaggregateidentifier = :nodeAggregateIdentifier';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier
        ];

        $dimensionSpacePoints = [];
        foreach ($this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative() as $row) {
            $dimensionSpacePoints[] = DimensionSpacePoint::fromJsonString($row['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    /**
     * @param ContentStreamId $contentStreamIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePoints
     * @param NodeAggregateId $originNodeAggregateIdentifier
     * @return array|RestrictionHyperrelationRecord[]
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findOutgoingRestrictionRelations(
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePoints,
        NodeAggregateId $originNodeAggregateIdentifier
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT r.*
            FROM ' . $this->tableNamePrefix . '_restrictionhyperrelation r
            WHERE r.contentstreamidentifier = :contentStreamIdentifier
            AND r.dimensionspacepointhash IN (:dimensionSpacePointHashes)
            AND r.originnodeaggregateidentifier = :originNodeAggregateIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'dimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes(),
            'originNodeAggregateIdentifier' => (string)$originNodeAggregateIdentifier
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
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateIdentifier
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT r.*
            FROM ' . $this->tableNamePrefix . '_restrictionhyperrelation r
            WHERE r.contentstreamidentifier = :contentStreamIdentifier
            AND r.dimensionspacepointhash = :dimensionSpacePointHash
            AND :nodeAggregateIdentifier = ANY(r.affectednodeaggregateidentifiers)';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier
        ];

        $restrictionRelations = [];
        $rows = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative();
        foreach ($rows as $row) {
            $restrictionRelations[] = RestrictionHyperrelationRecord::fromDatabaseRow($row);
        }

        return $restrictionRelations;
    }

    /**
     * @param ContentStreamId $contentStreamIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePoints
     * @param NodeAggregateId $nodeAggregateIdentifier
     * @return array|NodeAggregateIdentifiers[]
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findDescendantNodeAggregateIdentifiers(
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePoints,
        NodeAggregateId $nodeAggregateIdentifier
    ): array {
        $query = /** @lang PostgreSQL */ '
            -- ProjectionHypergraph::findDescendantNodeAggregateIdentifiers
            WITH RECURSIVE descendantNodes(nodeaggregateidentifier, relationanchorpoint, dimensionspacepointhash) AS (
                    -- --------------------------------
                    -- INITIAL query: select the root nodes
                    -- --------------------------------
                    SELECT
                       n.nodeaggregateidentifier,
                       n.relationanchorpoint,
                       h.dimensionspacepointhash
                    FROM ' . $this->tableNamePrefix . '_node n
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
                        ON n.relationanchorpoint = ANY(h.childnodeanchors)
                    WHERE n.nodeaggregateidentifier = :entryNodeAggregateIdentifier
                        AND h.contentstreamidentifier = :contentStreamIdentifier
                        AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)

                UNION ALL
                    -- --------------------------------
                    -- RECURSIVE query: do one "child" query step
                    -- --------------------------------
                    SELECT
                        c.nodeaggregateidentifier,
                        c.relationanchorpoint,
                        h.dimensionspacepointhash
                    FROM
                        descendantNodes p
                    INNER JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
                        ON h.parentnodeanchor = p.relationanchorpoint
                    INNER JOIN ' . $this->tableNamePrefix . '_node c ON c.relationanchorpoint = ANY(h.childnodeanchors)
                    WHERE
                        h.contentstreamidentifier = :contentStreamIdentifier
                        AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
            )
            SELECT nodeaggregateidentifier, dimensionspacepointhash from descendantNodes';

        $parameters = [
            'entryNodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'affectedDimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes()
        ];

        $types = [
            'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];

        $rows = $this->getDatabaseConnection()->executeQuery($query, $parameters, $types)
            ->fetchAllAssociative();
        $nodeAggregateIdentifiersByDimensionSpacePoint = [];
        foreach ($rows as $row) {
            $nodeAggregateIdentifiersByDimensionSpacePoint[$row['dimensionspacepointhash']]
                [$row['nodeaggregateidentifier']]
                = NodeAggregateId::fromString($row['nodeaggregateidentifier']);
        }

        return array_map(function (array $nodeAggregateIdentifiers) {
            return NodeAggregateIdentifiers::fromArray($nodeAggregateIdentifiers);
        }, $nodeAggregateIdentifiersByDimensionSpacePoint);
    }

    public function countContentStreamCoverage(NodeRelationAnchorPoint $anchorPoint): int
    {
        $query = /** @lang PostgreSQL */
            'SELECT DISTINCT contentstreamidentifier
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

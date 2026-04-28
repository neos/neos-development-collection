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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * FIXME ether rename this class or the ContentGraphProjection, since both names are confusing
 * The alternate reality-aware projection-time hypergraph for the PostgreSQL backend via Doctrine DBAL
 * CORRESPONDS TO {@see ProjectionContentGraph}
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
     * Find a node record by its coverage in a specific dimension space point.
     *
     * @throws DBALException
     */
    public function findNodeRecordByCoverage(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): ?NodeRecord {
        $result = $this->dbal->executeQuery(
            'SELECT DISTINCT n.*
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
            AND h.dimensionspacepointhash = :dimensionSpacePointHash
            AND n.nodeaggregateid = :nodeAggregateId',
            [
                'contentStreamId' => $contentStreamId->value,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                'nodeAggregateId' => $nodeAggregateId->value,
            ]
        )->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    /**
     * Find a node record by its origin dimension space point.
     *
     * @throws DBALException
     */
    public function findNodeRecordByOrigin(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): ?NodeRecord {
        $result = $this->dbal->executeQuery(
            'SELECT DISTINCT n.*
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
            AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
            AND h.dimensionspacepointhash = :originDimensionSpacePointHash
            AND n.nodeaggregateid = :nodeAggregateId',
            [
                'contentStreamId' => $contentStreamId->value,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'nodeAggregateId' => $nodeAggregateId->value,
            ]
        )->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    /**
     * @throws DBALException
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

    /**
     * Find all node records that belong to a node aggregate in any dimension.
     *
     * @return array<int,NodeRecord>
     * @throws DBALException
     */
    public function findNodeRecordsForNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): array {
        $rows = $this->dbal->executeQuery(
            'SELECT DISTINCT n.*
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
            AND n.nodeaggregateid = :nodeAggregateId',
            [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
            ]
        )->fetchAllAssociative();

        return array_map(
            static fn (array $row) => NodeRecord::fromDatabaseRow($row),
            $rows
        );
    }

    /**
     * @return array<int,HierarchyRelationRecord>
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
     * Find ingoing hierarchy relations for any node in a given node aggregate,
     * optionally filtered by dimension space points.
     * Unlike findIngoingHierarchyHyperrelationRecords, this searches by node aggregate ID
     * and returns both the hierarchy relation and the matched child anchor point.
     *
     * @return array<int, array{relation: HierarchyRelationRecord, childNodeAnchor: NodeRelationAnchorPoint}>
     * @throws DBALException
     */
    public function findIngoingHierarchyHyperrelationRecordsForNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints = null
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*, n.relationanchorpoint AS matched_child_anchor
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            JOIN ' . $this->tableNames->node() . ' n
                ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
            AND n.nodeaggregateid = :nodeAggregateId';
        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'nodeAggregateId' => $nodeAggregateId->value,
        ];
        $types = [];

        if ($affectedDimensionSpacePoints) {
            $query .= '
            AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)';
            $parameters['affectedDimensionSpacePointHashes'] = $affectedDimensionSpacePoints->getPointHashes();
            $types['affectedDimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }

        $results = [];
        foreach ($this->dbal->executeQuery($query, $parameters, $types)->iterateAssociative() as $row) {
            $matchedChildAnchor = NodeRelationAnchorPoint::fromInteger($row['matched_child_anchor']);
            unset($row['matched_child_anchor']);
            $results[] = [
                'relation' => HierarchyRelationRecord::fromDatabaseRow($row),
                'childNodeAnchor' => $matchedChildAnchor,
            ];
        }

        return $results;
    }

    /**
     * Find outgoing hierarchy relations for any node in a given node aggregate,
     * optionally filtered by dimension space points.
     *
     * @return array<int, array{relation: HierarchyRelationRecord, parentNodeAnchor: NodeRelationAnchorPoint}>
     * @throws DBALException
     */
    public function findOutgoingHierarchyHyperrelationRecordsForNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        ?DimensionSpacePointSet $affectedDimensionSpacePoints = null
    ): array {
        $query = /** @lang PostgreSQL */
            'SELECT h.*, n.relationanchorpoint AS matched_parent_anchor
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            JOIN ' . $this->tableNames->node() . ' n
                ON n.relationanchorpoint = h.parentnodeanchor
            WHERE h.contentstreamid = :contentStreamId
            AND n.nodeaggregateid = :nodeAggregateId';
        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'nodeAggregateId' => $nodeAggregateId->value,
        ];
        $types = [];

        if ($affectedDimensionSpacePoints) {
            $query .= '
            AND h.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)';
            $parameters['affectedDimensionSpacePointHashes'] = $affectedDimensionSpacePoints->getPointHashes();
            $types['affectedDimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }

        $results = [];
        foreach ($this->dbal->executeQuery($query, $parameters, $types)->iterateAssociative() as $row) {
            $matchedParentAnchor = NodeRelationAnchorPoint::fromInteger($row['matched_parent_anchor']);
            unset($row['matched_parent_anchor']);
            $results[] = [
                'relation' => HierarchyRelationRecord::fromDatabaseRow($row),
                'parentNodeAnchor' => $matchedParentAnchor,
            ];
        }

        return $results;
    }

    /**
     * @return array<int,HierarchyRelationRecord>
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
     * @return array<int,ReferenceRelationRecord>
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

    public function countContentStreamCoverage(NodeRelationAnchorPoint $anchorPoint): int
    {
        $query = /** @lang PostgreSQL */
            'SELECT COUNT(*) FROM (
                SELECT DISTINCT contentstreamid
                FROM ' . $this->tableNames->hierarchyRelation() . '
                WHERE :anchorPoint = ANY(childnodeanchors)
            ) sub';

        $parameters = [
            'anchorPoint' => $anchorPoint->value
        ];

        return (int)$this->dbal->executeQuery($query, $parameters)->fetchOne();
    }
}

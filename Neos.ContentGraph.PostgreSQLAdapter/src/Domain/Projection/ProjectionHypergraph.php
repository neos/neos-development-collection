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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\RecursionMode;
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
            'relationAnchorPoint' => $relationAnchorPoint->value
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
            'contentStreamId' => $contentStreamId->value,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'childNodeAggregateId' => $childNodeAggregateId->value
        ];

        $result = $this->getDatabaseConnection()
            ->executeQuery($query, $parameters)
            ->fetchAssociative();

        return $result ? NodeRecord::fromDatabaseRow($result) : null;
    }

    /**
     * Resolves a node's parent in another dimension space point
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findParentNodeRecordByOriginInDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $childNodeAggregateId
    ): ?NodeRecord {
        $query = /** @lang PostgreSQL */
            '
            /**
             * Second, find the node record with the same node aggregate identifier in the selected DSP
             */
            SELECT p.*
            FROM ' . $this->tableNamePrefix . '_node p
            JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation ph ON ph.parentnodeanchor = p.relationanchorpoint
            WHERE ph.contentstreamidentifier = :contentStreamIdentifier
            AND ph.dimensionspacepointhash = :coveredDimensionSpacePointHash
            AND p.nodeaggregateidentifier = (
                /**
                 * First, find the origin\'s parent node aggregate identifier
                 */
                SELECT orgp.nodeaggregateidentifier FROM ' . $this->tableNamePrefix . '_node orgp
                    JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation orgh
                        ON orgh.parentnodeanchor = orgp.relationanchorpoint
                    JOIN ' . $this->tableNamePrefix . '_node orgn
                        ON orgn.relationanchorpoint = ANY(orgh.childnodeanchors)
                WHERE orgh.contentstreamidentifier = :contentStreamIdentifier
                    AND orgh.dimensionspacepointhash = :originDimensionSpacePointHash
                    AND orgn.nodeaggregateidentifier = :childNodeAggregateId
            )';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'childNodeAggregateId' => $childNodeAggregateId->value
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
            'sourceNodeAnchor' => $sourceNodeAnchor->value
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
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'parentNodeAnchor' => $parentNodeAnchor->value
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
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'childNodeAnchor' => $childNodeAnchor->value
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
            'childNodeAnchor' => $childNodeAnchor->value
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
            'contentStreamId' => $contentStreamId->value,
            'nodeAggregateId' => $nodeAggregateId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAssociative();

        return $result ? HierarchyHyperrelationRecord::fromDatabaseRow($result) : null;
    }

    public function findParentHierarchyHyperrelationRecordByOriginInDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $childNodeAggregateId
    ): ?HierarchyHyperrelationRecord {
        $query = /** @lang PostgreSQL */
            '
            /**
             * Second, find the child relation of that parent node in the selected DSP
             */
            SELECT h.* FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
                JOIN ' . $this->tableNamePrefix . '_node n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamidentifier = :contentStreamId
            AND h.dimensionspacepointhash = :coveredDimensionSpacePointHash
            AND n.nodeaggregateidentifier = (
                /**
                 * First, find the node\'s origin parent node aggregate identifier
                 */
                SELECT orgp.nodeaggregateidentifier FROM ' . $this->tableNamePrefix . '_node orgp
                    JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation orgh
                        ON orgp.relationanchorpoint = orgh.parentnodeanchor
                    JOIN ' . $this->tableNamePrefix . '_node orgn ON orgn.relationanchorpoint = ANY (orgh.childnodeanchors)
                WHERE orgh.contentstreamidentifier = :contentStreamId
                    AND orgh.dimensionspacepointhash = :originDimensionSpacePointHash
                    AND orgn.nodeaggregateidentifier = :childNodeAggregateId
            )';

        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'childNodeAggregateId' => $childNodeAggregateId->value,
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAssociative();

        return $result ? HierarchyHyperrelationRecord::fromDatabaseRow($result) : null;
    }

    public function findSucceedingSiblingRelationAnchorPointsByOriginInDimensionSpacePoint(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $childNodeAggregateId
    ): NodeRelationAnchorPoints {
        $query = /** @lang PostgreSQL */
            '
            /**
             * Second, resolve the relation anchor point for each succeeding sibling in the selected DSP
             */
            SELECT sn.relationanchorpoint FROM ' . $this->tableNamePrefix . '_node sn
                JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation sh
                    ON sn.relationanchorpoint = ANY(sh.childnodeanchors)
            WHERE sh.contentstreamidentifier = :contentStreamIdentifier
                AND sh.dimensionspacepointhash = :coveredDimensionSpacePointHash
                AND sn.nodeaggregateidentifier IN (
                    SELECT nodeaggregateidentifier FROM
                    (
                        /**
                         * First, find the node aggregate identifiers of the origin\'s succeeding siblings,
                         * ordered by distance to it, ascending
                         */
                        SELECT sibn.nodeaggregateidentifier
                            FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation orgh
                            JOIN ' . $this->tableNamePrefix . '_node orgn
                                ON orgn.relationanchorpoint = ANY(orgh.childnodeanchors),
                                unnest(orgh.childnodeanchors) WITH ORDINALITY childnodeanchor
                            JOIN ' . $this->tableNamePrefix . '_node sibn ON sibn.relationanchorpoint = childnodeanchor
                        WHERE orgh.contentstreamidentifier = :contentStreamIdentifier
                            AND orgh.dimensionspacepointhash = :originDimensionSpacePointHash
                            AND orgn.nodeaggregateidentifier = :childNodeAggregateIdentifier
                            AND sibn.relationanchorpoint = ANY(orgh.childnodeanchors[
                                (array_position(orgh.childnodeanchors, orgn.relationanchorpoint))+1:
                            ])
                        ORDER BY ORDINALITY
                      ) AS relationdata
                )';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamId,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateId,
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative();

        return NodeRelationAnchorPoints::fromArray(
            array_map(
                fn (array $databaseRow): string => $databaseRow['relationanchorpoint'],
                $result
            )
        );
    }

    public function findTetheredChildNodeRecordsInOrigin(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeRelationAnchorPoint $relationAnchorPoint
    ): NodeRecords {
        $query = /** @lang PostgreSQL */
            'SELECT n.* FROM ' . $this->tableNamePrefix . '_node n
                JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE n.classification = :classification
                AND h.contentstreamidentifier = :contentStreamIdentifier
                AND h.dimensionspacepointhash = :originDimensionSpacePointHash
                AND h.parentnodeanchor = :parentNodeAnchor
            ';

        $parameters = [
            'classification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value,
            'contentStreamIdentifier' => (string)$contentStreamId,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'parentNodeAnchor' => $relationAnchorPoint->value,
        ];

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters)->fetchAllAssociative();

        return NodeRecords::fromDatabaseRows($result);
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
            'contentStreamId' => $contentStreamId->value,
            'nodeAggregateId' => $nodeAggregateId->value
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
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHashes' => $dimensionSpacePoints->getPointHashes(),
            'originNodeAggregateId' => $originNodeAggregateId->value
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
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            'nodeAggregateId' => $nodeAggregateId->value
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
            'entryNodeAggregateId' => $nodeAggregateId->value,
            'contentStreamId' => $contentStreamId->value,
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
            'anchorPoint' => $anchorPoint->value
        ];

        return (int)$this->getDatabaseConnection()->executeQuery($query, $parameters)->rowCount();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findDescendantAssignments(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $sourceDimensionSpacePoint,
        DimensionSpacePointSet $affectedCoveredDimensionSpacePoints,
        RecursionMode $recursionMode
    ): array {
        return $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */ '
WITH RECURSIVE parentrelation AS (
    /**
     * Initial query: find the proper parent relation to be restored to restore the selected node itself.
     * We need to find the node\'s parent\'s variant covering the target dimension space point
     * as well as an appropriate succeeding sibling
     */
    SELECT
        /* The parent node aggregate id, to be used for restriction relations */
        tarp.nodeaggregateidentifier AS parentnodeaggregateid,
        /* The parent relation anchor point, to be used for hierarchy relations */
        tarp.relationanchorpoint     AS parentrelationanchorpoint,
        /* The child node aggregat id, to be used for restriction relations */
        srcn.nodeaggregateidentifier AS childnodeaggregateid,
        /* The child relation anchor point, to be used for hierarchy relations */
        srcn.relationanchorpoint     AS childrelationanchorpoint,
        /* The dimension space point and hash, to be used for hierarchy relations */
        tarp.dimensionspacepoint,
        tarp.dimensionspacepointhash,
        /* The succeeding sibling relation anchor point, to be used for hierarchy relations */
        tars.relationanchorpoint     AS succeedingsiblingrelationanchorpoint,
        /* The order in which the children are to be arranged in newly created hierarchy relations */
        1::bigint                    AS ordinality,
        1::bigint                    AS level
    FROM cr_default_p_hypergraph_hierarchyhyperrelation srch
        JOIN cr_default_p_hypergraph_node srcn
             ON srcn.relationanchorpoint = ANY (srch.childnodeanchors)
        JOIN cr_default_p_hypergraph_node srcp
             ON srcp.relationanchorpoint = srch.parentnodeanchor
        /**
         * Join the target parent per dimension space point, i.e. the node covering the respective target DSP and
         * sharing the node aggregate ID with the source\'s parent
         */
        LEFT JOIN LATERAL (
            SELECT tarpn.nodeaggregateidentifier, tarpn.relationanchorpoint, tarph.dimensionspacepoint, tarph.dimensionspacepointhash
                FROM cr_default_p_hypergraph_node tarpn
                    JOIN cr_default_p_hypergraph_hierarchyhyperrelation tarph
                        ON tarpn.relationanchorpoint = ANY (tarph.childnodeanchors)
                WHERE tarph.contentstreamidentifier = :contentStreamId
                    AND tarph.dimensionspacepointhash IN (:dimensionSpacePointHashes)
                    AND tarpn.nodeaggregateidentifier = srcp.nodeaggregateidentifier
        ) tarp ON TRUE
        /**
         * Join the target succeeding sibling per dimension space point, i.e. the first child node of the target parent
         * which is in the list of succeeding siblings of the source node
         */
        LEFT JOIN LATERAL (
            SELECT tars.relationanchorpoint, tars.nodeaggregateidentifier
                FROM cr_default_p_hypergraph_node tars
                    JOIN cr_default_p_hypergraph_hierarchyhyperrelation tarsh
                        ON tars.relationanchorpoint = ANY (tarsh.childnodeanchors)
                WHERE tarsh.contentstreamidentifier = :contentStreamId
                    AND tarsh.parentnodeanchor = tarp.relationanchorpoint
                    AND tarsh.dimensionspacepointhash = tarp.dimensionspacepointhash
                    AND tars.nodeaggregateidentifier IN (
                        SELECT nodeaggregateidentifier
                        FROM cr_default_p_hypergraph_node
                             JOIN unnest(srch.childnodeanchors[(array_position(srch.childnodeanchors, srcn.relationanchorpoint))+1:]) WITH ORDINALITY AS T(childnodeanchor,index) ON relationanchorpoint = childnodeanchor
                        ORDER BY index
                        LIMIT 1
                    )
        ) tars ON true
            WHERE srcn.nodeaggregateidentifier = :nodeAggregateId
            AND srch.contentstreamidentifier = :contentStreamId
            AND srch.dimensionspacepointhash = :sourceDimensionSpacePointHash

    UNION ALL
        /**
         * Iteration query: find all descendant node and sibling node relation anchor points in the source dimension space point
         * Generally, nothing exists in any of the target DSPs yet, so we can just copy all hierarchy relations as they are.
         * The only exception are descendant nodes moved before deletion; They still may exist elsewhere and break the iteration.
         */
        SELECT parentrelation.childnodeaggregateid     AS parentnodeaggregateid,
               parentrelation.childrelationanchorpoint AS parentrelationanchorpoint,
               srcc.nodeaggregateidentifier            AS childnodeaggregateid,
               srcc.relationanchorpoint                AS childrelationanchorpoint,
               parentrelation.dimensionspacepoint,
               parentrelation.dimensionspacepointhash,
               /** we choose an arbitrary UUID to define that succeeding siblings are to be resolved from the results */
               :anchorPointForResultResolution         AS succeedingsiblingrelationanchorpoint,
               index                                   AS ordinality,
               parentrelation.level + 1                AS level
        FROM parentrelation
            JOIN cr_default_p_hypergraph_hierarchyhyperrelation srch
                ON srch.parentnodeanchor = parentrelation.childrelationanchorpoint
                    AND srch.dimensionspacepointhash = :sourceDimensionSpacePointHash
                    AND srch.contentstreamidentifier = :contentStreamId,
            unnest(srch.childnodeanchors) WITH ORDINALITY AS T(childnodeanchor,index)
            JOIN cr_default_p_hypergraph_node srcc
                ON srcc.relationanchorpoint = childnodeanchor
                ' . match ($recursionMode) {
                    RecursionMode::MODE_ALL_DESCENDANTS => '',
                    RecursionMode::MODE_ONLY_TETHERED_DESCENDANTS => 'AND srcc.classification = :classification',
                } . '
            /** Filter out moved nodes, i.e. descendant node aggregates already covering the target DSP */
            LEFT OUTER JOIN LATERAL (
                SELECT relationanchorpoint FROM cr_default_p_hypergraph_node n
                    JOIN cr_default_p_hypergraph_hierarchyhyperrelation h
                         ON n.relationanchorpoint = ANY (h.childnodeanchors)
                    WHERE n.nodeaggregateidentifier = srcc.nodeaggregateidentifier
                        AND h.dimensionspacepointhash = parentrelation.dimensionspacepointhash
                        AND h.contentstreamidentifier = :contentStreamId
            ) movednode ON TRUE
                WHERE movednode.relationanchorpoint IS NULL
) SELECT * FROM parentrelation ORDER BY level, ordinality
            ',
            [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'sourceDimensionSpacePointHash' => $sourceDimensionSpacePoint->hash,
                'dimensionSpacePointHashes' => $affectedCoveredDimensionSpacePoints->getPointHashes(),
                'anchorPointForResultResolution' => HypergraphProjection::ANCHOR_POINT_SORT_FROM_RESULT,
                'classification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
            ],
            [
                'dimensionSpacePoints' => Connection::PARAM_STR_ARRAY,
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAllAssociative();
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->databaseClient->getConnection();
    }
}

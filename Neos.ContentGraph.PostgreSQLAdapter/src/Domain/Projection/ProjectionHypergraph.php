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

    /**
     * Resolves a node's parent in another dimension space point
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findParentNodeRecordByOriginInDimensionSpacePoint(
        ContentStreamId $contentStreamIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $childNodeAggregateIdentifier
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
                    AND orgn.nodeaggregateidentifier = :childNodeAggregateIdentifier
            )';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
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
            SELECT h.* FROM neos_contentgraph_hierarchyhyperrelation h
                JOIN ' . $this->tableNamePrefix . '_node n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE h.contentstreamidentifier = :contentStreamIdentifier
            AND h.dimensionspacepointhash = :coveredDimensionSpacePointHash
            AND n.nodeaggregateidentifier = (
                /**
                 * First, find the node\'s origin parent node aggregate identifier
                 */
                SELECT orgp.nodeaggregateidentifier FROM neos_contentgraph_node orgp
                    JOIN neos_contentgraph_hierarchyhyperrelation orgh
                        ON orgp.relationanchorpoint = orgh.parentnodeanchor
                    JOIN neos_contentgraph_node orgn ON orgn.relationanchorpoint = ANY (orgh.childnodeanchors)
                WHERE orgh.contentstreamidentifier = :contentStreamIdentifier
                    AND orgh.dimensionspacepointhash = :originDimensionSpacePointHash
                    AND orgn.nodeaggregateidentifier = :childNodeAggregateIdentifier
            )';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamId,
            'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
            'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateId,
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
                'contentStreamId' => (string)$contentStreamId,
                'nodeAggregateId' => (string)$nodeAggregateId,
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

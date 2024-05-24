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
use Doctrine\DBAL\Exception as DbalException;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
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
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint of $childNodeAggregateId
     * @param DimensionSpacePoint|null $coveredDimensionSpacePoint the dimension space point of which relation we want
     *     to travel upwards. If not given, $originDimensionSpacePoint is used (though I am not fully sure if this is
     *     correct)
     */
    public function findParentNode(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        ?DimensionSpacePoint $coveredDimensionSpacePoint = null
    ): ?NodeRecord {
        $parentNodeStatement = <<<SQL
            SELECT
                p.*, ph.contentstreamid, ph.subtreetags, dsp.dimensionspacepoint AS origindimensionspacepoint
            FROM
                {$this->tableNames->node()} p
                INNER JOIN {$this->tableNames->hierarchyRelation()} ph ON ph.childnodeanchor = p.relationanchorpoint
                INNER JOIN {$this->tableNames->hierarchyRelation()} ch ON ch.parentnodeanchor = p.relationanchorpoint
                INNER JOIN {$this->tableNames->node()} c ON ch.childnodeanchor = c.relationanchorpoint
                INNER JOIN {$this->tableNames->dimensionSpacePoints()} dsp ON p.origindimensionspacepointhash = dsp.hash
            WHERE
                c.nodeaggregateid = :childNodeAggregateId
                AND c.origindimensionspacepointhash = :originDimensionSpacePointHash
                AND ph.contentstreamid = :contentStreamId
                AND ch.contentstreamid = :contentStreamId
                AND ph.dimensionspacepointhash = :coveredDimensionSpacePointHash
                AND ch.dimensionspacepointhash = :coveredDimensionSpacePointHash
        SQL;
        try {
            $nodeRow = $this->dbal->fetchAssociative($parentNodeStatement, [
                'contentStreamId' => $contentStreamId->value,
                'childNodeAggregateId' => $childNodeAggregateId->value,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash ?? $originDimensionSpacePoint->hash
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load parent node for content stream %s, child node aggregate id %s, origin dimension space point %s from database: %s', $contentStreamId->value, $childNodeAggregateId->value, $originDimensionSpacePoint->toJson(), $e->getMessage()), 1716475976, $e);
        }

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    public function findNodeInAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $coveredDimensionSpacePoint
    ): ?NodeRecord {
        $nodeInAggregateStatement = <<<SQL
            SELECT
                n.*, h.subtreetags, dsp.dimensionspacepoint AS origindimensionspacepoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
                INNER JOIN {$this->tableNames->dimensionSpacePoints()} dsp ON n.origindimensionspacepointhash = dsp.hash
            WHERE
                n.nodeaggregateid = :nodeAggregateId
                AND h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
        SQL;
        try {
            $nodeRow = $this->dbal->fetchAssociative($nodeInAggregateStatement, [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load node for content stream %s, aggregate id %s and covered dimension space point %s from database: %s', $contentStreamId->value, $nodeAggregateId->value, $coveredDimensionSpacePoint->toJson(), $e->getMessage()), 1716474165, $e);
        }

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    public function getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        ContentStreamId $contentStreamId
    ): ?NodeRelationAnchorPoint {
        $relationAnchorPointsStatement = <<<SQL
            SELECT
                DISTINCT n.relationanchorpoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                n.nodeaggregateid = :nodeAggregateId
                AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
                AND h.contentstreamid = :contentStreamId
        SQL;
        try {
            $relationAnchorPoints = $this->dbal->fetchFirstColumn($relationAnchorPointsStatement, [
                'nodeAggregateId' => $nodeAggregateId->value,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'contentStreamId' => $contentStreamId->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load node anchor points for content stream %s, node aggregate %s and origin dimension space point %s from database: %s', $contentStreamId->value, $nodeAggregateId->value, $originDimensionSpacePoint->toJson(), $e->getMessage()), 1716474224, $e);
        }

        if (count($relationAnchorPoints) > 1) {
            throw new \RuntimeException(sprintf('More than one node anchor point for content stream: %s, node aggregate id: %s and origin dimension space point: %s – this should not happen and might be a conceptual problem!', $contentStreamId->value, $nodeAggregateId->value, $originDimensionSpacePoint->toJson()), 1716474484);
        }
        return $relationAnchorPoints === [] ? null : NodeRelationAnchorPoint::fromInteger($relationAnchorPoints[0]);
    }

    /**
     * @return iterable<NodeRelationAnchorPoint>
     */
    public function getAnchorPointsForNodeAggregateInContentStream(
        NodeAggregateId $nodeAggregateId,
        ContentStreamId $contentStreamId
    ): iterable {
        $relationAnchorPointsStatement = <<<SQL
            SELECT
                DISTINCT n.relationanchorpoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                n.nodeaggregateid = :nodeAggregateId
                AND h.contentstreamid = :contentStreamId
        SQL;
        try {
            $relationAnchorPoints = $this->dbal->fetchFirstColumn($relationAnchorPointsStatement, [
                'nodeAggregateId' => $nodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load node anchor points for content stream %s and node aggregate id %s from database: %s', $contentStreamId->value, $nodeAggregateId->value, $e->getMessage()), 1716474706, $e);
        }

        return array_map(NodeRelationAnchorPoint::fromInteger(...), $relationAnchorPoints);
    }

    public function getNodeByAnchorPoint(NodeRelationAnchorPoint $nodeRelationAnchorPoint): ?NodeRecord
    {
        $nodeByAnchorPointStatement = <<<SQL
            SELECT
                n.*, dsp.dimensionspacepoint AS origindimensionspacepoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->dimensionSpacePoints()} dsp ON n.origindimensionspacepointhash = dsp.hash
            WHERE
                n.relationanchorpoint = :relationAnchorPoint
        SQL;
        try {
            $nodeRow = $this->dbal->fetchAssociative($nodeByAnchorPointStatement, [
                'relationAnchorPoint' => $nodeRelationAnchorPoint->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load node for anchor point %s from database: %s', $nodeRelationAnchorPoint->value, $e->getMessage()), 1716474765, $e);
        }

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

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
            $succeedingSiblingRelationStatement = <<<SQL
                SELECT
                    h.*
                FROM
                    {$this->tableNames->hierarchyRelation()} h
                WHERE
                    h.childnodeanchor = :succeedingSiblingAnchorPoint
                    AND h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
            SQL;
            try {
                /** @var array<string,mixed> $succeedingSiblingRelation */
                $succeedingSiblingRelation = $this->dbal->fetchAssociative($succeedingSiblingRelationStatement, [
                    'succeedingSiblingAnchorPoint' => $succeedingSiblingAnchorPoint->value,
                    'contentStreamId' => $contentStreamId->value,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                ]);
            } catch (DbalException $e) {
                throw new \RuntimeException(sprintf('Failed to load succeeding sibling relations for content stream %s, anchor point %s and dimension space point %s from database: %s', $contentStreamId->value, $succeedingSiblingAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716474854, $e);
            }

            $succeedingSiblingPosition = (int)$succeedingSiblingRelation['position'];
            $parentAnchorPoint = NodeRelationAnchorPoint::fromInteger($succeedingSiblingRelation['parentnodeanchor']);

            $precedingSiblingStatement = <<<SQL
                SELECT
                    MAX(h.position) AS position
                FROM
                    {$this->tableNames->hierarchyRelation()} h
                WHERE
                    h.parentnodeanchor = :anchorPoint
                    AND h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
                    AND h.position < :position
            SQL;
            try {
                $precedingSiblingData = $this->dbal->fetchAssociative($precedingSiblingStatement, [
                    'anchorPoint' => $parentAnchorPoint->value,
                    'contentStreamId' => $contentStreamId->value,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                    'position' => $succeedingSiblingPosition
                ]);
            } catch (DbalException $e) {
                throw new \RuntimeException(sprintf('Failed to load preceding sibling relations for content stream %s, anchor point %s and dimension space point %s from database: %s', $contentStreamId->value, $parentAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716474957, $e);
            }
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
                $childHierarchyRelationStatement = <<<SQL
                    SELECT
                        h.parentnodeanchor
                    FROM
                        {$this->tableNames->hierarchyRelation()} h
                    WHERE
                        h.childnodeanchor = :childAnchorPoint
                        AND h.contentstreamid = :contentStreamId
                        AND h.dimensionspacepointhash = :dimensionSpacePointHash
                SQL;
                try {
                    /** @var array<string,mixed> $childHierarchyRelationData */
                    $childHierarchyRelationData = $this->dbal->fetchAssociative($childHierarchyRelationStatement, [
                        'childAnchorPoint' => $childAnchorPoint->value,
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ]);
                } catch (DbalException $e) {
                    throw new \RuntimeException(sprintf('Failed to load child hierarchy relation for content stream %s, anchor point %s and dimension space point %s from database: %s', $contentStreamId->value, $childAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716475001, $e);
                }
                $parentAnchorPoint = NodeRelationAnchorPoint::fromInteger(
                    $childHierarchyRelationData['parentnodeanchor']
                );
            }
            $rightmostSucceedingSiblingRelationStatement = <<<SQL
                SELECT
                    MAX(h.position) AS position
                FROM
                    {$this->tableNames->hierarchyRelation()} h
                WHERE
                    h.parentnodeanchor = :parentAnchorPoint
                    AND h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
            SQL;
            try {
                $rightmostSucceedingSiblingRelationData = $this->dbal->fetchAssociative($rightmostSucceedingSiblingRelationStatement, [
                    'parentAnchorPoint' => $parentAnchorPoint->value,
                    'contentStreamId' => $contentStreamId->value,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                ]);
            } catch (DbalException $e) {
                throw new \RuntimeException(sprintf('Failed to right most succeeding relation for content stream %s, anchor point %s and dimension space point %s from database: %s', $contentStreamId->value, $parentAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716475046, $e);
            }

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
     * @return array<HierarchyRelation>
     */
    public function getOutgoingHierarchyRelationsForNodeAndSubgraph(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint
    ): array {
        $outgoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->tableNames->hierarchyRelation()} h
            WHERE
                h.parentnodeanchor = :parentAnchorPoint
                AND h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($outgoingHierarchyRelationsStatement, [
                'parentAnchorPoint' => $parentAnchorPoint->value,
                'contentStreamId' => $contentStreamId->value,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load outgoing hierarchy relations for content stream %s, parent anchor point %s and dimension space point %s from database: %s', $contentStreamId->value, $parentAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716475151, $e);
        }
        return array_map($this->mapRawDataToHierarchyRelation(...), $rows);
    }

    /**
     * @return array<HierarchyRelation>
     */
    public function getIngoingHierarchyRelationsForNodeAndSubgraph(
        NodeRelationAnchorPoint $childAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint
    ): array {
        $ingoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->tableNames->hierarchyRelation()} h
            WHERE
                h.childnodeanchor = :childAnchorPoint
                AND h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($ingoingHierarchyRelationsStatement, [
                'childAnchorPoint' => $childAnchorPoint->value,
                'contentStreamId' => $contentStreamId->value,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load ingoing hierarchy relations for content stream %s, child anchor point %s and dimension space point %s from database: %s', $contentStreamId->value, $childAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716475151, $e);
        }
        return array_map($this->mapRawDataToHierarchyRelation(...), $rows);
    }

    /**
     * @return array<string, HierarchyRelation> indexed by the dimension space point hash: ['<dimensionSpacePointHash>' => HierarchyRelation, ...]
     */
    public function findIngoingHierarchyRelationsForNode(
        NodeRelationAnchorPoint $childAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $restrictToSet = null
    ): array {
        $ingoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->tableNames->hierarchyRelation()} h
            WHERE
                h.childnodeanchor = :childAnchorPoint
                AND h.contentstreamid = :contentStreamId
        SQL;
        $parameters = [
            'childAnchorPoint' => $childAnchorPoint->value,
            'contentStreamId' => $contentStreamId->value
        ];
        $types = [];

        if ($restrictToSet) {
            $ingoingHierarchyRelationsStatement .= ' AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }
        try {
            $rows = $this->dbal->fetchAllAssociative($ingoingHierarchyRelationsStatement, $parameters, $types);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load ingoing hierarchy relations for content stream %s, child anchor point %s and dimension space points %s from database: %s', $contentStreamId->value, $childAnchorPoint->value, $restrictToSet?->toJson() ?? '[any]', $e->getMessage()), 1716476299, $e);
        }
        $relations = [];
        foreach ($rows as $row) {
            $relations[(string)$row['dimensionspacepointhash']] = $this->mapRawDataToHierarchyRelation($row);
        }
        return $relations;
    }

    /**
     *  @return array<string, HierarchyRelation> indexed by the dimension space point hash: ['<dimensionSpacePointHash>' => HierarchyRelation, ...]
     */
    public function findOutgoingHierarchyRelationsForNode(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePointSet $restrictToSet = null
    ): array {
        $outgoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->tableNames->hierarchyRelation()} h
            WHERE
                h.parentnodeanchor = :parentAnchorPoint
                AND h.contentstreamid = :contentStreamId
        SQL;
        $parameters = [
            'parentAnchorPoint' => $parentAnchorPoint->value,
            'contentStreamId' => $contentStreamId->value
        ];
        $types = [];

        if ($restrictToSet) {
            $outgoingHierarchyRelationsStatement .= ' AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }
        try {
            $rows = $this->dbal->fetchAllAssociative($outgoingHierarchyRelationsStatement, $parameters, $types);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load outgoing hierarchy relations for content stream %s, parent anchor point %s and dimension space points %s from database: %s', $contentStreamId->value, $parentAnchorPoint->value, $restrictToSet?->toJson() ?? '[any]', $e->getMessage()), 1716476573, $e);
        }
        $relations = [];
        foreach ($rows as $row) {
            $relations[(string)$row['dimensionspacepointhash']] = $this->mapRawDataToHierarchyRelation($row);
        }
        return $relations;
    }

    /**
     * @return array<HierarchyRelation>
     */
    public function findOutgoingHierarchyRelationsForNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): array {
        $outgoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->tableNames->hierarchyRelation()} h
                INNER JOIN {$this->tableNames->node()} n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE
                n.nodeaggregateid = :nodeAggregateId
                AND h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($outgoingHierarchyRelationsStatement, [
                'nodeAggregateId' => $nodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value,
                'dimensionSpacePointHashes' => $dimensionSpacePointSet->getPointHashes()
            ], [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load outgoing hierarchy relations for content stream %s, node aggregate id %s and dimension space points %s from database: %s', $contentStreamId->value, $nodeAggregateId->value, $dimensionSpacePointSet->toJson(), $e->getMessage()), 1716476690, $e);
        }
        return array_map($this->mapRawDataToHierarchyRelation(...), $rows);
    }

    /**
     * @return array<HierarchyRelation>
     */
    public function findIngoingHierarchyRelationsForNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePointSet = null
    ): array {
        $ingoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->tableNames->hierarchyRelation()} h
                INNER JOIN {$this->tableNames->node()} n ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                n.nodeaggregateid = :nodeAggregateId
                AND h.contentstreamid = :contentStreamId
        SQL;
        $parameters = [
            'nodeAggregateId' => $nodeAggregateId->value,
            'contentStreamId' => $contentStreamId->value,
        ];
        $types = [];
        if ($dimensionSpacePointSet !== null) {
            $ingoingHierarchyRelationsStatement .= ' AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $dimensionSpacePointSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }
        try {
            $rows = $this->dbal->fetchAllAssociative($ingoingHierarchyRelationsStatement, $parameters, $types);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load ingoing hierarchy relations for content stream %s, node aggregate id %s and dimension space points %s from database: %s', $contentStreamId->value, $nodeAggregateId->value, $dimensionSpacePointSet?->toJson() ?? '[any]', $e->getMessage()), 1716476743, $e);
        }
        if ($rows === false) {
            return [];
        }
        return array_map($this->mapRawDataToHierarchyRelation(...), $rows);
    }

    /**
     * @return array<ContentStreamId>
     */
    public function getAllContentStreamIdsAnchorPointIsContainedIn(
        NodeRelationAnchorPoint $nodeRelationAnchorPoint
    ): array {
        $contentStreamIdsStatement = <<<SQL
            SELECT
                DISTINCT h.contentstreamid
            FROM
                {$this->tableNames->hierarchyRelation()} h
            WHERE
                h.childnodeanchor = :nodeRelationAnchorPoint
        SQL;
        try {
            $contentStreamIds = $this->dbal->fetchFirstColumn($contentStreamIdsStatement, [
                'nodeRelationAnchorPoint' => $nodeRelationAnchorPoint->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load content stream ids for relation anchor point %s from database: %s', $nodeRelationAnchorPoint->value, $e->getMessage()), 1716478504, $e);
        }
        return array_map(ContentStreamId::fromString(...), $contentStreamIds);
    }

    /**
     * @param array<string,string> $rawData
     */
    private function mapRawDataToHierarchyRelation(array $rawData): HierarchyRelation
    {
        $dimensionSpacePointStatement = <<<SQL
            SELECT
                dimensionspacepoint
            FROM
                {$this->tableNames->dimensionSpacePoints()}
            WHERE
                hash = :hash
        SQL;
        try {
            $dimensionSpacePointJson = $this->dbal->fetchOne($dimensionSpacePointStatement, [
                'hash' => $rawData['dimensionspacepointhash']
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load dimension space point for hash %s from database: %s', $rawData['dimensionspacepointhash'], $e->getMessage()), 1716476830, $e);
        }

        return new HierarchyRelation(
            NodeRelationAnchorPoint::fromInteger((int)$rawData['parentnodeanchor']),
            NodeRelationAnchorPoint::fromInteger((int)$rawData['childnodeanchor']),
            ContentStreamId::fromString($rawData['contentstreamid']),
            DimensionSpacePoint::fromJsonString($dimensionSpacePointJson),
            $rawData['dimensionspacepointhash'],
            (int)$rawData['position'],
            NodeFactory::extractNodeTagsFromJson($rawData['subtreetags']),
        );
    }
}

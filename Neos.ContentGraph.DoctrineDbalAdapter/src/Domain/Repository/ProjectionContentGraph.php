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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayer;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationId;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\HierarchyRelationStatement;
use Neos\ContentGraph\DoctrineDbalAdapter\HierarchyRelationViewStatement;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

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
        private readonly HierarchyRelationStatement|HierarchyRelationViewStatement $hierarchyRelationStatement,
    ) {
    }

    /**
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint of $childNodeAggregateId
     * @param DimensionSpacePoint|null $coveredDimensionSpacePoint the dimension space point of which relation we want
     *     to travel upwards. If not given, $originDimensionSpacePoint is used (though I am not fully sure if this is
     *     correct)
     */
    public function findParentNode(
        ContentStreamLayers $contentStreamLayers,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        ?DimensionSpacePoint $coveredDimensionSpacePoint = null
    ): ?NodeRecord {
        $parentNodeStatement = <<<SQL
            SELECT
                p.*, ph.subtreetags, dsp.dimensionspacepoint AS origindimensionspacepoint
            FROM
                {$this->tableNames->node()} p
                INNER JOIN {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :coveredDimensionSpacePointHash')->toSql()} ph ON ph.childnodeanchor = p.relationanchorpoint
                INNER JOIN {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :coveredDimensionSpacePointHash')->toSql()} ch ON ch.parentnodeanchor = p.relationanchorpoint
                INNER JOIN {$this->tableNames->node()} c ON ch.childnodeanchor = c.relationanchorpoint
                INNER JOIN {$this->tableNames->dimensionSpacePoints()} dsp ON p.origindimensionspacepointhash = dsp.hash
            WHERE
                c.nodeaggregateid = :childNodeAggregateId
                AND c.origindimensionspacepointhash = :originDimensionSpacePointHash
        SQL;
        try {
            $nodeRow = $this->dbal->fetchAssociative($parentNodeStatement, [
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'childNodeAggregateId' => $childNodeAggregateId->value,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'coveredDimensionSpacePointHash' => $coveredDimensionSpacePoint->hash ?? $originDimensionSpacePoint->hash
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load parent node for content stream %s, child node aggregate id %s, origin dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $childNodeAggregateId->value, $originDimensionSpacePoint->toJson(), $e->getMessage()), 1716475976, $e);
        }

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    public function findNodeInAggregate(
        ContentStreamLayers $contentStreamLayers,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $coveredDimensionSpacePoint
    ): ?NodeRecord {
        $nodeInAggregateStatement = <<<SQL
            SELECT
                n.*, h.subtreetags, dsp.dimensionspacepoint AS origindimensionspacepoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h ON h.childnodeanchor = n.relationanchorpoint
                INNER JOIN {$this->tableNames->dimensionSpacePoints()} dsp ON n.origindimensionspacepointhash = dsp.hash
            WHERE
                n.nodeaggregateid = :nodeAggregateId
        SQL;
        try {
            $nodeRow = $this->dbal->fetchAssociative($nodeInAggregateStatement, [
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load node for content stream %s, aggregate id %s and covered dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $coveredDimensionSpacePoint->toJson(), $e->getMessage()), 1716474165, $e);
        }

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    public function getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        ContentStreamLayers $contentStreamLayers
    ): ?NodeRelationAnchorPoint {
        $relationAnchorPointsStatement = <<<SQL
            SELECT
                DISTINCT n.relationanchorpoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->hierarchyRelationStatement->toSql()} AS h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                n.nodeaggregateid = :nodeAggregateId
                AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
        SQL;
        try {
            $relationAnchorPoints = $this->dbal->fetchFirstColumn($relationAnchorPointsStatement, [
                'nodeAggregateId' => $nodeAggregateId->value,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load node anchor points for content stream %s, node aggregate %s and origin dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $originDimensionSpacePoint->toJson(), $e->getMessage()), 1716474224, $e);
        }

        if (count($relationAnchorPoints) > 1) {
            throw new \RuntimeException(sprintf('More than one node anchor point for content stream: %s, node aggregate id: %s and origin dimension space point: %s – this should not happen and might be a conceptual problem!', $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $originDimensionSpacePoint->toJson()), 1716474484);
        }
        return $relationAnchorPoints === [] ? null : NodeRelationAnchorPoint::fromInteger($relationAnchorPoints[0]);
    }

    /**
     * @return iterable<NodeRelationAnchorPoint>
     */
    public function getAnchorPointsForNodeAggregateInContentStream(
        NodeAggregateId $nodeAggregateId,
        ContentStreamLayers $contentStreamLayers
    ): iterable {
        $relationAnchorPointsStatement = <<<SQL
            SELECT
                DISTINCT n.relationanchorpoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$this->hierarchyRelationStatement->toSql()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                n.nodeaggregateid = :nodeAggregateId
        SQL;
        try {
            $relationAnchorPoints = $this->dbal->fetchFirstColumn($relationAnchorPointsStatement, [
                'nodeAggregateId' => $nodeAggregateId->value,
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load node anchor points for content stream %s and node aggregate id %s from database: %s', $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $e->getMessage()), 1716474706, $e);
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
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load node for anchor point %s from database: %s', $nodeRelationAnchorPoint->value, $e->getMessage()), 1716474765, $e);
        }

        return $nodeRow ? NodeRecord::fromDatabaseRow($nodeRow) : null;
    }

    public function determineHierarchyRelationPosition(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamLayers $contentStreamLayers,
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
                    {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h
                WHERE
                    h.childnodeanchor = :succeedingSiblingAnchorPoint
                LIMIT 1
            SQL;
            try {
                /** @var array<string,mixed> $succeedingSiblingRelation */
                $succeedingSiblingRelation = $this->dbal->fetchAssociative($succeedingSiblingRelationStatement, [
                    'succeedingSiblingAnchorPoint' => $succeedingSiblingAnchorPoint->value,
                    'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                ], [
                    'contentStreamLayers' => ArrayParameterType::INTEGER,
                ]);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to load succeeding sibling relations for content stream %s, anchor point %s and dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $succeedingSiblingAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716474854, $e);
            }

            if (!$succeedingSiblingRelation) {
                throw new \RuntimeException(
                    sprintf('Could not fetch succeeding sibling relation for anchor point: %s with dimensionSpacePointHash : %s', $succeedingSiblingAnchorPoint->value, $dimensionSpacePoint->hash),
                    1696405259
                );
            }

            $succeedingSiblingPosition = (int)$succeedingSiblingRelation['position'];
            $parentAnchorPoint = NodeRelationAnchorPoint::fromInteger($succeedingSiblingRelation['parentnodeanchor']);

            $precedingSiblingStatement = <<<SQL
                SELECT
                    h.position
                FROM
                    {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h
                WHERE
                    h.parentnodeanchor = :anchorPoint
                    AND h.position < :position
                -- select the MAX position
                ORDER BY h.position DESC
                LIMIT 1
            SQL;
            try {
                $precedingSiblingData = $this->dbal->fetchAssociative($precedingSiblingStatement, [
                    'anchorPoint' => $parentAnchorPoint->value,
                    'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                    'position' => $succeedingSiblingPosition
                ], [
                    'contentStreamLayers' => ArrayParameterType::INTEGER,
                ]);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to load preceding sibling relations for content stream %s, anchor point %s and dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $parentAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716474957, $e);
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
                        {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h
                    WHERE
                        h.childnodeanchor = :childAnchorPoint
                    LIMIT 1
                SQL;
                try {
                    /** @var array<string,mixed> $childHierarchyRelationData */
                    $childHierarchyRelationData = $this->dbal->fetchAssociative($childHierarchyRelationStatement, [
                        'childAnchorPoint' => $childAnchorPoint->value,
                        'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                        'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                    ], [
                        'contentStreamLayers' => ArrayParameterType::INTEGER,
                    ]);
                } catch (DBALException $e) {
                    throw new \RuntimeException(sprintf('Failed to load child hierarchy relation for content stream %s, anchor point %s and dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $childAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716475001, $e);
                }
                $parentAnchorPoint = NodeRelationAnchorPoint::fromInteger(
                    $childHierarchyRelationData['parentnodeanchor']
                );
            }
            $rightmostSucceedingSiblingRelationStatement = <<<SQL
                SELECT
                    h.position
                FROM
                    {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h
                WHERE
                    h.parentnodeanchor = :parentAnchorPoint
                -- select the MAX position
                ORDER BY h.position DESC
                LIMIT 1
            SQL;
            try {
                $rightmostSucceedingSiblingRelationData = $this->dbal->fetchAssociative($rightmostSucceedingSiblingRelationStatement, [
                    'parentAnchorPoint' => $parentAnchorPoint->value,
                    'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash
                ], [
                    'contentStreamLayers' => ArrayParameterType::INTEGER,
                ]);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to right most succeeding relation for content stream %s, anchor point %s and dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $parentAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716475046, $e);
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
        ContentStreamLayers $contentStreamLayers,
        DimensionSpacePoint $dimensionSpacePoint
    ): array {
        $outgoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h
            WHERE
                h.parentnodeanchor = :parentAnchorPoint
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($outgoingHierarchyRelationsStatement, [
                'parentAnchorPoint' => $parentAnchorPoint->value,
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load outgoing hierarchy relations for content stream %s, parent anchor point %s and dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $parentAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716475151, $e);
        }
        return array_map($this->mapRawDataToHierarchyRelation(...), $rows);
    }

    /**
     * @return array<HierarchyRelation>
     */
    public function getIngoingHierarchyRelationsForNodeAndSubgraph(
        NodeRelationAnchorPoint $childAnchorPoint,
        ContentStreamLayers $contentStreamLayers,
        DimensionSpacePoint $dimensionSpacePoint
    ): array {
        $ingoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h
            WHERE
                h.childnodeanchor = :childAnchorPoint
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($ingoingHierarchyRelationsStatement, [
                'childAnchorPoint' => $childAnchorPoint->value,
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load ingoing hierarchy relations for content stream %s, child anchor point %s and dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $childAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716475151, $e);
        }
        return array_map($this->mapRawDataToHierarchyRelation(...), $rows);
    }

    /**
     * @return array<string, HierarchyRelation> indexed by the dimension space point hash: ['<dimensionSpacePointHash>' => HierarchyRelation, ...]
     */
    public function findIngoingHierarchyRelationsForNode(
        NodeRelationAnchorPoint $childAnchorPoint,
        ContentStreamLayers $contentStreamLayers,
        ?DimensionSpacePointSet $restrictToSet = null
    ): array {
        $ingoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->hierarchyRelationStatement->toSql()} h
            WHERE
                h.childnodeanchor = :childAnchorPoint
        SQL;
        $parameters = [
            'childAnchorPoint' => $childAnchorPoint->value,
            'contentStreamLayers' => $contentStreamLayers->toIntArray()
        ];
        $types = [
            'contentStreamLayers' => ArrayParameterType::INTEGER,
        ];

        if ($restrictToSet) {
            $ingoingHierarchyRelationsStatement .= ' AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = ArrayParameterType::STRING;
        }
        try {
            $rows = $this->dbal->fetchAllAssociative($ingoingHierarchyRelationsStatement, $parameters, $types);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load ingoing hierarchy relations for content stream %s, child anchor point %s and dimension space points %s from database: %s', $contentStreamLayers->toDebugString(), $childAnchorPoint->value, $restrictToSet?->toJson() ?? '[any]', $e->getMessage()), 1716476299, $e);
        }
        $relations = [];
        foreach ($rows as $row) {
            $relations[(string)$row['dimensionspacepointhash']] = $this->mapRawDataToHierarchyRelation($row);
        }
        return $relations;
    }

    /**
     *  @return array<int, HierarchyRelation>
     */
    public function findOutgoingHierarchyRelationsForNode(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ContentStreamLayers $contentStreamLayers,
        ?DimensionSpacePointSet $restrictToSet = null
    ): array {
        $outgoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->hierarchyRelationStatement->toSql()} h
            WHERE
                h.parentnodeanchor = :parentAnchorPoint
        SQL;
        $parameters = [
            'parentAnchorPoint' => $parentAnchorPoint->value,
            'contentStreamLayers' => $contentStreamLayers->toIntArray()
        ];
        $types = [
            'contentStreamLayers' => ArrayParameterType::INTEGER,
        ];

        if ($restrictToSet) {
            $outgoingHierarchyRelationsStatement .= ' AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $restrictToSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = ArrayParameterType::STRING;
        }
        try {
            $rows = $this->dbal->fetchAllAssociative($outgoingHierarchyRelationsStatement, $parameters, $types);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load outgoing hierarchy relations for content stream %s, parent anchor point %s and dimension space points %s from database: %s', $contentStreamLayers->toDebugString(), $parentAnchorPoint->value, $restrictToSet?->toJson() ?? '[any]', $e->getMessage()), 1716476573, $e);
        }
        $relations = [];
        foreach ($rows as $row) {
            $relations[] = $this->mapRawDataToHierarchyRelation($row);
        }
        return $relations;
    }

    /**
     * @return array<HierarchyRelation>
     */
    public function findOutgoingHierarchyRelationsForNodeAggregate(
        ContentStreamLayers $contentStreamLayers,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): array {
        $outgoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash IN (:dimensionSpacePointHashes)')->toSql()} h
                INNER JOIN {$this->tableNames->node()} n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE
                n.nodeaggregateid = :nodeAggregateId
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($outgoingHierarchyRelationsStatement, [
                'nodeAggregateId' => $nodeAggregateId->value,
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'dimensionSpacePointHashes' => $dimensionSpacePointSet->getPointHashes()
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load outgoing hierarchy relations for content stream %s, node aggregate id %s and dimension space points %s from database: %s', $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $dimensionSpacePointSet->toJson(), $e->getMessage()), 1716476690, $e);
        }
        return array_map($this->mapRawDataToHierarchyRelation(...), $rows);
    }

    /**
     * @return array<HierarchyRelation>
     */
    public function findIngoingHierarchyRelationsForNodeAggregate(
        ContentStreamLayers $contentStreamLayers,
        NodeAggregateId $nodeAggregateId,
        ?DimensionSpacePointSet $dimensionSpacePointSet = null
    ): array {
        $ingoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$this->hierarchyRelationStatement->where($dimensionSpacePointSet !== null ? 'h.dimensionspacepointhash IN (:dimensionSpacePointHashes)' : '')->toSql()} h
                INNER JOIN {$this->tableNames->node()} n ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                n.nodeaggregateid = :nodeAggregateId
        SQL;
        $parameters = [
            'nodeAggregateId' => $nodeAggregateId->value,
            'contentStreamLayers' => $contentStreamLayers->toIntArray(),
            ...($dimensionSpacePointSet !== null ? ['dimensionSpacePointHashes' => $dimensionSpacePointSet->getPointHashes()] : []),
        ];
        $types = [
            'contentStreamLayers' => ArrayParameterType::INTEGER,
            ...($dimensionSpacePointSet !== null ? ['dimensionSpacePointHashes' => ArrayParameterType::STRING] : []),
        ];
        try {
            $rows = $this->dbal->fetchAllAssociative($ingoingHierarchyRelationsStatement, $parameters, $types);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load ingoing hierarchy relations for content stream %s, node aggregate id %s and dimension space points %s from database: %s', $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $dimensionSpacePointSet?->toJson() ?? '[any]', $e->getMessage()), 1716476743, $e);
        }
        return array_map($this->mapRawDataToHierarchyRelation(...), $rows);
    }

    public function getAllContentStreamLayersAnchorPointIsContainedIn(
        NodeRelationAnchorPoint $nodeRelationAnchorPoint
    ): ContentStreamLayers {
        $contentStreamLayersStatement = <<<SQL
            SELECT
                DISTINCT h.contentstreamlayer
            FROM
                -- using table instead of HierarchyRelationStatement because node rows can be shared for all layers 
                {$this->tableNames->hierarchyRelation()} h
            WHERE
                h.childnodeanchor = :nodeRelationAnchorPoint
        SQL;
        try {
            $contentStreamLayers = $this->dbal->fetchFirstColumn($contentStreamLayersStatement, [
                'nodeRelationAnchorPoint' => $nodeRelationAnchorPoint->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load content stream ids for relation anchor point %s from database: %s', $nodeRelationAnchorPoint->value, $e->getMessage()), 1716478504, $e);
        }
        return ContentStreamLayers::fromArray($contentStreamLayers);
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
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load dimension space point for hash %s from database: %s', $rawData['dimensionspacepointhash'], $e->getMessage()), 1716476830, $e);
        }

        return new HierarchyRelation(
            HierarchyRelationId::fromInt((int)$rawData['id']),
            ContentStreamLayer::fromInt((int)$rawData['contentstreamlayer']),
            NodeRelationAnchorPoint::fromInteger((int)$rawData['parentnodeanchor']),
            NodeRelationAnchorPoint::fromInteger((int)$rawData['childnodeanchor']),
            DimensionSpacePoint::fromJsonString($dimensionSpacePointJson),
            $rawData['dimensionspacepointhash'],
            (int)$rawData['position'],
            NodeFactory::extractNodeTagsFromJson($rawData['subtreetags']),
        );
    }
}

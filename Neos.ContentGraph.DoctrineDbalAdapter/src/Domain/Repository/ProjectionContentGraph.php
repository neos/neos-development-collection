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
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayer;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationId;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeAggregateIdCondition;
use Neos\ContentGraph\DoctrineDbalAdapter\SqlTableSubqueryFactory;
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
    private SqlTableSubqueryFactory $subqueries;

    public function __construct(
        private readonly Connection $dbal,
        private readonly ContentGraphTableNames $tableNames,
    ) {
        $this->subqueries = SqlTableSubqueryFactory::for($this->tableNames);
    }

    public function findParentNode(
        ContentStreamLayers $contentStreamLayers,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeRecord {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($childNodeAggregateId);
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($originDimensionSpacePoint->toDimensionSpacePoint())->withChildNodeAggregateIdPrefilter($nodeAggregateIdCondition);
        $parentNodeStatement = <<<SQL
            SELECT
                pn.*, ph.subtreetags, dsp.dimensionspacepoint AS origindimensionspacepoint
            FROM
                {$hierarchyStatement->toSql()} AS ph
                INNER JOIN {$this->tableNames->node()} pn ON ph.parentnodeanchor = pn.relationanchorpoint
                INNER JOIN {$this->tableNames->dimensionSpacePoints()} dsp ON pn.origindimensionspacepointhash = dsp.hash
            WHERE ph.childnodeanchor IN (
                SELECT cn.relationanchorpoint FROM {$this->tableNames->node()} cn
                    WHERE {$nodeAggregateIdCondition->toWhereSql('cn')}
                      AND cn.origindimensionspacepointhash = {$hierarchyStatement->getParameters()->getReference('dimensionSpacePointHash')}
            )
        SQL;
        try {
            $nodeRow = $this->dbal->fetchAssociative($parentNodeStatement, [
                ...$hierarchyStatement->getParameters()->toDbalValues(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalTypes(),
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
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($coveredDimensionSpacePoint)->withChildNodeAggregateIdPrefilter($nodeAggregateIdCondition);
        $nodeInAggregateStatement = <<<SQL
            SELECT
                n.*, h.subtreetags, dsp.dimensionspacepoint AS origindimensionspacepoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$hierarchyStatement->toSql()} h ON h.childnodeanchor = n.relationanchorpoint
                INNER JOIN {$this->tableNames->dimensionSpacePoints()} dsp ON n.origindimensionspacepointhash = dsp.hash
            WHERE
                {$nodeAggregateIdCondition->toWhereSql('n')}
        SQL;
        try {
            $nodeRow = $this->dbal->fetchAssociative($nodeInAggregateStatement, [
                ...$hierarchyStatement->getParameters()->toDbalValues(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalTypes(),
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
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withChildNodeAggregateIdPrefilter($nodeAggregateIdCondition);
        $relationAnchorPointsStatement = <<<SQL
            SELECT
                DISTINCT n.relationanchorpoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$hierarchyStatement->toSql()} AS h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                {$nodeAggregateIdCondition->toWhereSql('n')}
                AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
        SQL;
        try {
            $relationAnchorPoints = $this->dbal->fetchFirstColumn($relationAnchorPointsStatement, [
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->hash,
                ...$hierarchyStatement->getParameters()->toDbalValues(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalTypes(),
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
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withChildNodeAggregateIdPrefilter($nodeAggregateIdCondition);
        $relationAnchorPointsStatement = <<<SQL
            SELECT
                DISTINCT n.relationanchorpoint
            FROM
                {$this->tableNames->node()} n
                INNER JOIN {$hierarchyStatement->toSql()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                {$nodeAggregateIdCondition->toWhereSql('n')}
        SQL;
        try {
            $relationAnchorPoints = $this->dbal->fetchFirstColumn($relationAnchorPointsStatement, [
                ...$hierarchyStatement->getParameters()->toDbalValues(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalTypes(),
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
            $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withChildNodeRelationAnchor($succeedingSiblingAnchorPoint);
            // TODO add limit 1 to inner?
            $succeedingSiblingRelationStatement = <<<SQL
                SELECT
                    h.*
                FROM
                    {$hierarchyStatement->toSql()} h
                LIMIT 1
            SQL;
            try {
                /** @var array<string,mixed> $succeedingSiblingRelation */
                $succeedingSiblingRelation = $this->dbal->fetchAssociative($succeedingSiblingRelationStatement, [
                    ...$hierarchyStatement->getParameters()->toDbalValues(),
                ], [
                    ...$hierarchyStatement->getParameters()->toDbalTypes(),
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

            $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withParentNodeRelationAnchor($parentAnchorPoint);
            $precedingSiblingStatement = <<<SQL
                SELECT
                    h.position
                FROM
                    {$hierarchyStatement->toSql()} h
                WHERE
                    h.position < :position
                -- select the MAX position
                ORDER BY h.position DESC
                LIMIT 1
            SQL;
            try {
                $precedingSiblingData = $this->dbal->fetchAssociative($precedingSiblingStatement, [
                    'position' => $succeedingSiblingPosition,
                    ...$hierarchyStatement->getParameters()->toDbalValues(),
                ], [
                    ...$hierarchyStatement->getParameters()->toDbalTypes(),
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
                $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withChildNodeRelationAnchor($childAnchorPoint);
                $childHierarchyRelationStatement = <<<SQL
                    SELECT
                        h.parentnodeanchor
                    FROM
                        {$hierarchyStatement->toSql()} h
                    LIMIT 1
                SQL;
                try {
                    /** @var array<string,mixed> $childHierarchyRelationData */
                    $childHierarchyRelationData = $this->dbal->fetchAssociative($childHierarchyRelationStatement, [
                        ...$hierarchyStatement->getParameters()->toDbalValues(),
                    ], [
                        ...$hierarchyStatement->getParameters()->toDbalTypes(),
                    ]);
                } catch (DBALException $e) {
                    throw new \RuntimeException(sprintf('Failed to load child hierarchy relation for content stream %s, anchor point %s and dimension space point %s from database: %s', $contentStreamLayers->toDebugString(), $childAnchorPoint->value, $dimensionSpacePoint->toJson(), $e->getMessage()), 1716475001, $e);
                }
                $parentAnchorPoint = NodeRelationAnchorPoint::fromInteger(
                    $childHierarchyRelationData['parentnodeanchor']
                );
            }
            $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withParentNodeRelationAnchor($parentAnchorPoint);
            $rightmostSucceedingSiblingRelationStatement = <<<SQL
                SELECT
                    h.position
                FROM
                    {$hierarchyStatement->toSql()} h
                -- select the MAX position
                ORDER BY h.position DESC
                LIMIT 1
            SQL;
            try {
                $rightmostSucceedingSiblingRelationData = $this->dbal->fetchAssociative($rightmostSucceedingSiblingRelationStatement, [
                    ...$hierarchyStatement->getParameters()->toDbalValues(),
                ], [
                    ...$hierarchyStatement->getParameters()->toDbalTypes(),
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
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withParentNodeRelationAnchor($parentAnchorPoint);
        $outgoingHierarchyRelationsStatement = <<<SQL
            {$hierarchyStatement->toSql()}
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($outgoingHierarchyRelationsStatement, [
                ...$hierarchyStatement->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
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
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withChildNodeRelationAnchor($childAnchorPoint);
        $ingoingHierarchyRelationsStatement = <<<SQL
            {$hierarchyStatement->toSql()}
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($ingoingHierarchyRelationsStatement, [
                ...$hierarchyStatement->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
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
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withChildNodeRelationAnchor($childAnchorPoint);
        if ($restrictToSet !== null) {
            $hierarchyStatement = $hierarchyStatement->withDimensionSpacePoints($restrictToSet);
        }
        $ingoingHierarchyRelationsStatement = <<<SQL
            {$hierarchyStatement->toSql()}
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative(
                $ingoingHierarchyRelationsStatement,
                $hierarchyStatement->getParameters()->toDbalValues(),
                $hierarchyStatement->getParameters()->toDbalTypes()
            );
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
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withParentNodeRelationAnchor($parentAnchorPoint);
        if ($restrictToSet !== null) {
            $hierarchyStatement = $hierarchyStatement->withDimensionSpacePoints($restrictToSet);
        }
        $outgoingHierarchyRelationsStatement = <<<SQL
            {$hierarchyStatement->toSql()}
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative(
                $outgoingHierarchyRelationsStatement,
                $hierarchyStatement->getParameters()->toDbalValues(),
                $hierarchyStatement->getParameters()->toDbalTypes(),
            );
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
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoints($dimensionSpacePointSet)->withParentNodeAggregateIdPrefilter($nodeAggregateIdCondition);
        $outgoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$hierarchyStatement->toSql()} h
                INNER JOIN {$this->tableNames->node()} n ON h.parentnodeanchor = n.relationanchorpoint
            WHERE
                {$nodeAggregateIdCondition->toWhereSql('n')}
        SQL;
        try {
            $rows = $this->dbal->fetchAllAssociative($outgoingHierarchyRelationsStatement, [
                ...$hierarchyStatement->getParameters()->toDbalValues(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalTypes(),
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
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withChildNodeAggregateIdPrefilter($nodeAggregateIdCondition);
        if ($dimensionSpacePointSet) {
            $hierarchyStatement = $hierarchyStatement->withDimensionSpacePoints($dimensionSpacePointSet);
        }
        $ingoingHierarchyRelationsStatement = <<<SQL
            SELECT
                h.*
            FROM
                {$hierarchyStatement->toSql()} h
                INNER JOIN {$this->tableNames->node()} n ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                {$nodeAggregateIdCondition->toWhereSql('n')}
        SQL;
        $parameters = [
            ...$hierarchyStatement->getParameters()->toDbalValues(),
            ...$nodeAggregateIdCondition->getParameters()->toDbalValues(),
        ];
        $types = [
            ...$hierarchyStatement->getParameters()->toDbalTypes(),
            ...$nodeAggregateIdCondition->getParameters()->toDbalTypes(),
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

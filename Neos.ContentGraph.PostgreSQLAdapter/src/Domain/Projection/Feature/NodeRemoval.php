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

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoints;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceRelationRecord;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\RecursionMode;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateCoverageWasRestored;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;

/**
 * The node removal feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeRemoval
{
    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->transactional(function () use ($event) {
            $affectedRelationAnchorPoints = [];
            // first step: remove hierarchy relations
            foreach ($event->affectedCoveredDimensionSpacePoints as $dimensionSpacePoint) {
                $nodeRecord = $this->getProjectionHypergraph()->findNodeRecordByCoverage(
                    $event->getContentStreamId(),
                    $dimensionSpacePoint,
                    $event->getNodeAggregateId()
                );
                if (is_null($nodeRecord)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
                }

                /** @var HierarchyHyperrelationRecord $ingoingHierarchyRelation */
                $ingoingHierarchyRelation = $this->getProjectionHypergraph()
                    ->findHierarchyHyperrelationRecordByChildNodeAnchor(
                        $event->getContentStreamId(),
                        $dimensionSpacePoint,
                        $nodeRecord->relationAnchorPoint
                    );
                $ingoingHierarchyRelation->removeChildNodeAnchor(
                    $nodeRecord->relationAnchorPoint,
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
                );
                $this->removeFromRestrictions(
                    $event->getContentStreamId(),
                    $dimensionSpacePoint,
                    $event->getNodeAggregateId()
                );

                $affectedRelationAnchorPoints[] = $nodeRecord->relationAnchorPoint;

                $this->cascadeHierarchy(
                    $event->getContentStreamId(),
                    $dimensionSpacePoint,
                    $nodeRecord->relationAnchorPoint,
                    $affectedRelationAnchorPoints
                );
            }

            // second step: remove orphaned nodes
            $this->getDatabaseConnection()->executeStatement(
                /** @lang PostgreSQL */
                '
                WITH deletedNodes AS (
                    DELETE FROM ' . $this->tableNamePrefix . '_node n
                    WHERE n.relationanchorpoint IN (
                        SELECT relationanchorpoint FROM ' . $this->tableNamePrefix . '_node
                            LEFT JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
                                ON n.relationanchorpoint = ANY(h.childnodeanchors)
                        WHERE n.relationanchorpoint IN (:affectedRelationAnchorPoints)
                            AND h.contentstreamidentifier IS NULL
                    )
                    RETURNING relationanchorpoint
                )
                DELETE FROM ' . $this->tableNamePrefix . '_referencerelation r
                    WHERE sourcenodeanchor IN (SELECT relationanchorpoint FROM deletedNodes)
                ',
                [
                    'affectedRelationAnchorPoints' => $affectedRelationAnchorPoints
                ],
                [
                    'affectedRelationAnchorPoints' => Connection::PARAM_STR_ARRAY
                ]
            );
        });
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @param array<int,NodeRelationAnchorPoint> &$affectedRelationAnchorPoints
     */
    private function cascadeHierarchy(
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $nodeRelationAnchorPoint,
        array &$affectedRelationAnchorPoints
    ): void {
        $childHierarchyRelation = $this->getProjectionHypergraph()->findHierarchyHyperrelationRecordByParentNodeAnchor(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            $nodeRelationAnchorPoint
        );
        if ($childHierarchyRelation) {
            $childHierarchyRelation->removeFromDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

            foreach ($childHierarchyRelation->childNodeAnchors as $childNodeAnchor) {
                /** @var NodeRecord $nodeRecord */
                $nodeRecord = $this->getProjectionHypergraph()
                    ->findNodeRecordByRelationAnchorPoint($childNodeAnchor);
                $ingoingHierarchyRelations = $this->getProjectionHypergraph()
                    ->findHierarchyHyperrelationRecordsByChildNodeAnchor($childNodeAnchor);
                if (empty($ingoingHierarchyRelations)) {
                    ReferenceRelationRecord::removeFromDatabaseForSource(
                        $nodeRecord->relationAnchorPoint,
                        $this->getDatabaseConnection(),
                        $this->tableNamePrefix
                    );
                    $affectedRelationAnchorPoints[] = $nodeRecord->relationAnchorPoint;
                }
                $this->removeFromRestrictions(
                    $contentStreamIdentifier,
                    $dimensionSpacePoint,
                    $nodeRecord->nodeAggregateIdentifier
                );
                $this->cascadeHierarchy(
                    $contentStreamIdentifier,
                    $dimensionSpacePoint,
                    $nodeRecord->relationAnchorPoint,
                    $affectedRelationAnchorPoints
                );
            }
        }
    }

    /**
     * @param ContentStreamId $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeAggregateId $nodeAggregateIdentifier
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function removeFromRestrictions(
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateIdentifier
    ): void {
        foreach (
            $this->getProjectionHypergraph()->findIngoingRestrictionRelations(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $nodeAggregateIdentifier
            ) as $restrictionRelation
        ) {
            $restrictionRelation->removeAffectedNodeAggregateIdentifier(
                $nodeAggregateIdentifier,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
            );
        }
    }

    public function whenNodeAggregateCoverageWasRestored(NodeAggregateCoverageWasRestored $event): void
    {
        $this->transactional(function () use($event) {
            $dimensionSpacePointList = '\''
                . implode('\', \'', array_map(
                    fn (DimensionSpacePoint $dimensionSpacePoint):string => \json_encode($dimensionSpacePoint),
                    $event->affectedCoveredDimensionSpacePoints->points
                ))
                . '\'';
            $dimensionSpacePointHashList = '\''
                . implode('\', \'', $event->affectedCoveredDimensionSpacePoints->getPointHashes())
                . '\'';
            $descendantAssignments = $this->getDatabaseConnection()->executeQuery('
                /** @lang PostgreSQL */
                WITH RECURSIVE parentrelation AS (
    /**
     * Initial query: find the proper parent relation to be restored to restore the selected node itself.
     * We need to find the node\'s parent\'s variant covering the target dimension space point
     * as well as an appropriate succeeding sibling
     */
    SELECT tarp.nodeaggregateidentifier AS parentnodeaggregateid,
           tarp.relationanchorpoint     AS parentrelationanchorpoint,
           srcn.nodeaggregateidentifier as childnodeaggregateid,
           srcn.relationanchorpoint     AS childrelationanchorpoint,
           tarp.dimensionspacepointhash,
           tars.relationanchorpoint     AS succeedingsiblingrelationanchorpoint,
           1::bigint
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
                SELECT tarp.nodeaggregateidentifier, tarp.relationanchorpoint, tarph.dimensionspacepointhash
        FROM cr_default_p_hypergraph_node tarp
                 JOIN cr_default_p_hypergraph_hierarchyhyperrelation tarph
                      ON tarp.relationanchorpoint = ANY (tarph.childnodeanchors)
        WHERE tarph.contentstreamidentifier = \'cs-identifier\'
            AND tarph.dimensionspacepointhash IN
            (\'1041cc1fe1030c1a82ac24346f8c69a7\', \'67b30a9436c8470107f1b237a14dc638\')
            AND tarp.nodeaggregateidentifier = srcp.nodeaggregateidentifier
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
        WHERE tarsh.contentstreamidentifier = \'cs-identifier\'
            AND tarsh.parentnodeanchor = tarp.relationanchorpoint
            AND tarsh.dimensionspacepointhash = tarp.dimensionspacepointhash
            AND tars.nodeaggregateidentifier IN (SELECT nodeaggregateidentifier
                                               FROM cr_default_p_hypergraph_node
                                               WHERE relationanchorpoint = ANY (srch.childnodeanchors[
                (array_position(srch.childnodeanchors, srcn.relationanchorpoint)) +
                1:])
                                               LIMIT 1)
        ) tars ON true
    WHERE srcn.nodeaggregateidentifier = \'nody-mc-nodeface\'
            AND srch.contentstreamidentifier = \'cs-identifier\'
            AND srch.dimensionspacepointhash = \'328e0e1fad82abfe205b19a36153dc2f\'

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
           parentrelation.dimensionspacepointhash,
           /** we choose an arbitrary UUID to define that succeeding siblings are to be resolved from the results */
           \'00000000-0000-0000-0000-000000000000\' AS succeedingsiblingrelationanchorpoint,
           srch.ordinality
    FROM parentrelation
         JOIN cr_default_p_hypergraph_hierarchyhyperrelation srch
              ON srch.parentnodeanchor = parentrelation.childrelationanchorpoint
            AND srch.dimensionspacepointhash = \'328e0e1fad82abfe205b19a36153dc2f\'
            AND srch.contentstreamidentifier = \'cs-identifier\'
         JOIN cr_default_p_hypergraph_node srcc
              ON srcc.relationanchorpoint = ANY (srch.childnodeanchors)
        /** Filter out moved nodes, i.e. descendant node aggregates already covering the target DSP */
        LEFT OUTER JOIN LATERAL (
                SELECT relationanchorpoint FROM cr_default_p_hypergraph_node n
            JOIN cr_default_p_hypergraph_hierarchyhyperrelation h
                 ON n.relationanchorpoint = ANY (h.childnodeanchors)
            WHERE n.nodeaggregateidentifier = srcc.nodeaggregateidentifier
            AND h.dimensionspacepointhash = parentrelation.dimensionspacepointhash
            AND h.contentstreamidentifier = \'cs-identifier\'
         ) movednode ON TRUE,
         unnest(srch.childnodeanchors) WITH ORDINALITY childnodeanchor
        WHERE movednode.relationanchorpoint IS NULL

) SELECT * FROM parentrelation ORDER BY ordinality
            ',
            [
                'contentStreamId' => (string)$event->contentStreamId,
                'classification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value,
                'relationAnchorPoint' => '',//(string)$nodeRecord->relationAnchorPoint,
                'originDimensionSpacePointHash' => $event->sourceDimensionSpacePoint->hash,
                'dimensionSpacePoints' => array_map(
                    fn(DimensionSpacePoint $dimensionSpacePoint): string
                        => json_encode($dimensionSpacePoint, JSON_THROW_ON_ERROR),
                    $event->affectedCoveredDimensionSpacePoints->points
                ),
                'dimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints->getPointHashes()
            ],
            [
                'dimensionSpacePoints' => Connection::PARAM_STR_ARRAY,
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ],/*
            [
                'contentStreamId' => $event->contentStreamId,
                'nodeAggregateId' => $event->nodeAggregateId,
                'sourceDimensionSpacePointHash' => $event->sourceDimensionSpacePoint->hash
            ]*/)->fetchAllAssociative();

            foreach ($descendantAssignments as $descendantAssignment) {
                $this->getDatabaseConnection()->executeStatement(
                    'INSERT INTO ' . $this->tableNamePrefix . '_hierarchyhyperrelation'
                );
            }

            \Neos\Flow\var_dump($descendantAssignments);
            exit();
        });
        $nodeRecord = $this->projectionHypergraph->findNodeRecordByOrigin(
            $event->contentStreamId,
            $event->sourceDimensionSpacePoint,
            $event->nodeAggregateId
        );
        if (!$nodeRecord instanceof NodeRecord) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
        }

        // create or adjust the target parent's child hierarchy hyperrelations
        foreach ($event->affectedCoveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $hierarchyRelation
                = $this->projectionHypergraph->findParentHierarchyHyperrelationRecordByOriginInDimensionSpacePoint(
                    $event->contentStreamId,
                    $event->sourceDimensionSpacePoint,
                    $coveredDimensionSpacePoint,
                    $event->nodeAggregateId
                );

            if ($hierarchyRelation instanceof HierarchyHyperrelationRecord) {
                $succeedingSiblingCandidates = $this->projectionHypergraph
                    ->findSucceedingSiblingRelationAnchorPointsByOriginInDimensionSpacePoint(
                        $event->contentStreamId,
                        $event->sourceDimensionSpacePoint,
                        $coveredDimensionSpacePoint,
                        $event->nodeAggregateId
                    );
                $hierarchyRelation->addChildNodeAnchorAfterFirstCandidate(
                    $nodeRecord->relationAnchorPoint,
                    $succeedingSiblingCandidates,
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
                );
            } else {
                $parentNodeRecord = $this->projectionHypergraph->findParentNodeRecordByOriginInDimensionSpacePoint(
                    $event->contentStreamId,
                    $event->sourceDimensionSpacePoint,
                    $coveredDimensionSpacePoint,
                    $event->nodeAggregateId
                );
                if (!$parentNodeRecord instanceof NodeRecord) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(get_class($event));
                }

                (new HierarchyHyperrelationRecord(
                    $event->contentStreamId,
                    $parentNodeRecord->relationAnchorPoint,
                    $coveredDimensionSpacePoint,
                    new NodeRelationAnchorPoints(
                        $nodeRecord->relationAnchorPoint
                    )
                ))->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
            }
        }

        // cascade to all descendants
        $this->getDatabaseConnection()->executeStatement(
            /** @lang PostgreSQL */
            '
            /**
             * First, we collect all hierarchy relations to be copied in the restoration process.
             * These are the descendant relations in the origin to be used:
             * parentnodeanchor and childnodeanchors only, the rest will be changed
             */
            WITH RECURSIVE descendantNodes(relationanchorpoint) AS (
                /**
                 * Initial query: find all outgoing child node relations from the starting node in its origin;
                 * which ones are resolved depends on the recursion mode.
                 */
                SELECT
                    n.relationanchorpoint,
                    h.parentnodeanchor
                FROM ' . $this->tableNamePrefix . '_node n
                    JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
                        ON n.relationanchorpoint = ANY(h.childnodeanchors)
                WHERE h.parentnodeanchor = :relationAnchorPoint
                  AND h.contentstreamidentifier = :contentStreamIdentifier
                  AND h.dimensionspacepointhash = :originDimensionSpacePointHash
                  ' . ($event->recursionMode === RecursionMode::MODE_ONLY_TETHERED_DESCENDANTS
                ? ' AND n.classification = :classification'
                : '') . '

                UNION ALL
                /**
                 * Iteration query: find all outgoing tethered child node relations from the parent node in its origin;
                 * which ones are resolved depends on the recursion mode.
                 */
                SELECT
                    c.relationanchorpoint,
                    h.parentnodeanchor
                FROM
                    descendantNodes p
                    JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
                        ON h.parentnodeanchor = p.relationanchorpoint
                    JOIN ' . $this->tableNamePrefix . '_node c ON c.relationanchorpoint = ANY(h.childnodeanchors)
                WHERE h.contentstreamidentifier = :contentStreamIdentifier
                    AND h.dimensionspacepointhash = :originDimensionSpacePointHash
                    ' . ($event->recursionMode === RecursionMode::MODE_ONLY_TETHERED_DESCENDANTS
                ? ' AND c.classification = :classification'
                : '') . '
            )
            INSERT INTO ' . $this->tableNamePrefix . '_hierarchyhyperrelation
                SELECT
                    :contentStreamIdentifier AS contentstreamidentifier,
                    parentnodeanchor,
                    CAST(dimensionspacepoint AS json),
                    dimensionspacepointhash,
                    array_agg(relationanchorpoint) AS childnodeanchors
                FROM descendantNodes
                    /** Here we join the affected dimension space points to actually create the new edges */
                    JOIN (
                        SELECT unnest(ARRAY[:dimensionSpacePoints]) AS dimensionspacepoint,
                        unnest(ARRAY[:dimensionSpacePointHashes]) AS dimensionspacepointhash
                    ) dimensionSpacePoints ON true
                GROUP BY parentnodeanchor, dimensionspacepoint, dimensionspacepointhash
            ',
            [
                'contentStreamIdentifier' => (string)$event->contentStreamId,
                'classification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value,
                'relationAnchorPoint' => (string)$nodeRecord->relationAnchorPoint,
                'originDimensionSpacePointHash' => $event->sourceDimensionSpacePoint->hash,
                'dimensionSpacePoints' => array_map(
                    fn(DimensionSpacePoint $dimensionSpacePoint): string
                        => json_encode($dimensionSpacePoint, JSON_THROW_ON_ERROR),
                    $event->affectedCoveredDimensionSpacePoints->points
                ),
                'dimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints->getPointHashes()
            ],
            [
                'dimensionSpacePoints' => Connection::PARAM_STR_ARRAY,
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}

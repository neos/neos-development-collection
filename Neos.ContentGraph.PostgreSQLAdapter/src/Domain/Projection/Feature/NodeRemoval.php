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
            $this->getDatabaseConnection()->executeStatement('
                /**
                 * This provides a list of minimal hierarchy relation data to be copied: parent and child node anchors
                 * as well as their child node aggregate identifier to help determining the new siblings
                 * in the target dimension space point
                 */
                WITH RECURSIVE descendantRelations(parentnodeanchor, childnodeanchor, parentnodeaggregateid, nodeaggregateid, siblingnodeaggregateids) AS (
                    /**
                     * Initial query: find the node aggregate identifiers for the node,
                     * its parent and its succeeding siblings, if any
                     * in the dimension space point where the coverage is to be increased FROM
                     */
                    SELECT srch.parentnodeanchor,
                           src.relationanchorpoint AS childnodeanchor,
                           srcp.nodeaggregateidentifier AS parentnodeaggregateid,
                           src.nodeaggregateidentifier AS nodeaggregateid,
                           array (
                               SELECT nodeaggregateidentifier FROM cr_default_p_hypergraph_node
                               WHERE relationanchorpoint = ANY (srch.childnodeanchors[(array_position(srch.childnodeanchors, src.relationanchorpoint)) + 1:])
                           ) AS siblingnodeaggregateids
                    FROM cr_default_p_hypergraph_hierarchyhyperrelation srch
                        JOIN cr_default_p_hypergraph_node src ON src.relationanchorpoint = ANY (srch.childnodeanchors)
                        JOIN cr_default_p_hypergraph_node srcp ON srcp.relationanchorpoint = srch.parentnodeanchor
                    WHERE srch.contentstreamidentifier = \'' . $event->contentStreamId . '\'
                        AND srch.dimensionspacepointhash = \'' . $event->sourceDimensionSpacePoint->hash . '\'
                        AND src.nodeaggregateidentifier = \'' . $event->nodeAggregateId . '\'
                    UNION ALL
                        /**
                         * Iteration query: find all descendant node and sibling node aggregate identifiers
                         * in the dimension space point where the coverage is to be increased FROM.
                         */
                        SELECT ch.parentnodeanchor,
                               c.relationanchorpoint AS childnodeanchor,
                               p.nodeaggregateid AS parentnodeaggregateid,
                               c.nodeaggregateidentifier AS nodeaggregateid,
                               array (
                                   SELECT nodeaggregateidentifier FROM cr_default_p_hypergraph_node
                                   WHERE relationanchorpoint = ANY (ch.childnodeanchors[(array_position(ch.childnodeanchors, c.relationanchorpoint)) + 1:])
                               ) AS siblingnodeaggregateids
                        FROM descendantRelations p
                            JOIN cr_default_p_hypergraph_hierarchyhyperrelation ch ON ch.parentnodeanchor = p.childnodeanchor
                            JOIN cr_default_p_hypergraph_node c ON c.relationanchorpoint = ANY(ch.childnodeanchors)
                        WHERE ch.contentstreamidentifier = \'' . $event->contentStreamId . '\'
                            AND ch.dimensionspacepointhash = \'' . $event->sourceDimensionSpacePoint->hash . '\'
                ) SELECT
                      parentrelationanchors.relationanchorpoint as parentanchor,
                      parentnodeanchor as sourceparentanchor,
                      --parentnodeaggregateid,
                      childrelationanchors.relationanchorpoint as childanchor,
                      childnodeanchor as sourcechildanchor,
                      --nodeaggregateid as childnodeaggregateid,
                      siblingrelationanchors.relationanchorpoint as succeedingsiblinganchor,
                      --siblingnodeaggregateids,
                      dimensionSpacePoints.dimensionspacepointhash,
                      dimensionSpacePoints.dimensionspacepoint
                  FROM descendantRelations
                    /**
                     * Here we join the affected dimension space points to extend the fetched hierarchy relation data
                     * by dimensionspacepoint and dimensionspacepointhash
                     */
                    JOIN (
                        SELECT unnest(ARRAY[' . $dimensionSpacePointList . ']) AS dimensionspacepoint,
                               unnest(ARRAY[' . $dimensionSpacePointHashList . ']) AS dimensionspacepointhash
                    ) dimensionSpacePoints ON true
                    /**
                     * Resolve parent node anchors for each affected dimension space points, may be null
                     */
                    LEFT JOIN (
                        SELECT relationanchorpoint, dimensionspacepointhash, nodeaggregateidentifier
                        FROM cr_default_p_hypergraph_node tgtp
                            JOIN cr_default_p_hypergraph_hierarchyhyperrelation tgtph
                            ON tgtp.relationanchorpoint = ANY(tgtph.childnodeanchors)
                        WHERE tgtph.contentstreamidentifier = \'' . $event->contentStreamId . '\'
                    ) parentrelationanchors
                        ON parentrelationanchors.dimensionspacepointhash = dimensionSpacePoints.dimensionspacepointhash
                        AND parentrelationanchors.nodeaggregateidentifier = parentnodeaggregateid
                    /**
                     * Resolve child node anchors for each affected dimension space points, may be null
                     */
                    LEFT JOIN (
                        SELECT relationanchorpoint, dimensionspacepointhash, nodeaggregateidentifier
                        FROM cr_default_p_hypergraph_node tgt
                        JOIN cr_default_p_hypergraph_hierarchyhyperrelation tgth
                            ON tgt.relationanchorpoint = ANY(tgth.childnodeanchors)
                        WHERE tgth.contentstreamidentifier = \'' . $event->contentStreamId . '\'
                    ) childrelationanchors
                        ON childrelationanchors.dimensionspacepointhash = dimensionSpacePoints.dimensionspacepointhash
                        AND childrelationanchors.nodeaggregateidentifier = nodeaggregateid
                    /**
                     * Resolve primary available succeeding sibling node anchors for each affected dimension space points, may be null
                     */
                    LEFT JOIN (
                        SELECT relationanchorpoint, dimensionspacepointhash, nodeaggregateidentifier
                        FROM cr_default_p_hypergraph_node tgtsib
                            JOIN cr_default_p_hypergraph_hierarchyhyperrelation tgtsibh
                            ON tgtsib.relationanchorpoint = ANY(tgtsibh.childnodeanchors)
                        WHERE tgtsibh.contentstreamidentifier = \'' . $event->contentStreamId . '\'
                    ) siblingrelationanchors
                        ON siblingrelationanchors.dimensionspacepointhash = dimensionSpacePoints.dimensionspacepointhash
                        AND siblingrelationanchors.nodeaggregateidentifier IN (
                            SELECT siblingnodeaggregateid
                                FROM unnest(siblingnodeaggregateids)
                                WITH ORDINALITY siblingnodeaggregateid
                                LIMIT 1
                        )

            ');
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
